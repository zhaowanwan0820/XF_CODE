<?php
/**
 +------------------------------------------------------------------------------
 * Controller Class
 +------------------------------------------------------------------------------
 * @category    Controller
 * @package     Action
 * @subpackage  User
 * @author      wangshijie@ucfgroup.com
 * @version     $Id: UserRegisterBatchAction.class.php  2014-08-3 012:08:56
 +------------------------------------------------------------------------------
 */

vendor("phpexcel.PHPExcel");
use core\service\user\BOFactory;
use core\service\UserTagService;
use libs\idno\CommonIdnoVerify;
use libs\utils\PaymentApi;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
class UserRegisterBatchAction extends CommonAction {

    private $idTypes = array(0 => '身份证', 1 => '香港身份证', 2 => '澳门身份证', 3 => '台湾身份证');
    private $comment = array (
        '1000' => '注册成功',
        '1001' => '手机号码错误',
        '1002' => '身份证号码错误',
        '1003' => '用户注册信息已存在',
        '1004' => '身份认证-数据错误',
        '1005' => '身份认证-系统错误',
        '1006' => '身份认证-服务异常',
        '1007' => '姓名与身份证号不匹配',
        '1008' => '身份认证-账号没有找到',
        '1009' => '插入数据库失败',
        '1010' => '先锋开户失败',
        '1011' => '转账失败',
        '1012' => '发送短信失败',
        '1013' => '更新先锋账号失败',
        '1014' => '银行卡已存在',
        '1015' => '支付绑定银行卡失败',
        '1016' => '银行卡绑定失败',
        '1017' => '银行卡只能为数字',
    );
    private $export = array (
        'name' => '姓名',
        'mobile' => '手机号',
        'email' => '邮箱地址',
        'group_id' => '用户组ID',
        'idtype' => '证件类型',
        'idno' => '证件号码',
        'branch' => '支行信息',
        'acno' => '银行卡号',
        'transfer_ac' => '转出账户',
        'transfer_money' => '转账金额',
        'transfer_comment' => '转账备注',
        'status' => '状态',
        //'invite' => '推荐人优惠码'
        );

    private $import = array (
        'name' => '姓名',
        'mobile' => '手机号',
        'idtype' => '证件类型',
        'idno' => '证件号码',
        'email' => '邮箱地址',
        'bank' => '银行名称',
        'branch' => '支行信息',
        'acno' => '银行卡号',
        'group_id' => '所属网站',
        'coupon_level_id' => '会员等级ID',
        'transfer_ac' => '转出账户',
        'transfer_money' => '转账金额',
        'transfer_comment' => '转账备注',
        'invite' => '推荐人优惠码'
    );
    /**
     * @desc   init
     * @access public
     */
    public function __construct() {
        parent::__construct();
        $this->assign('idTypes', $this->idTypes);
        $this->assign('comment', $this->comment);
    }

    /**
     * @desc   display page and search result page
     * @access pulic
     */
    public function index() {
        if (isset($_REQUEST['status'])) {
            $_REQUEST['batchno'] = '';
            $this->_list(M('RegisterBatchLog'), $this->_getMap());
        }
        $this->display();
    }

    /**
     * 快速增加一个用户
     */
    public function add() {

        $this->assign("group_list", M("UserGroup")->findAll());//用户组列表
        $this->assign("bank_list", M("Bank")->findAll());//银行列表
        $this->assign('transfer_ac', 'jg_dflh');//默认转账账户
        $this->assign('transfer_money', '20');//默认转账金额
        $this->assign('transfer_comment', '注册奖励');//默认转账备注信息
        $this->assign('invite', 'FH2GYP');//默认邀请人
        $this->assign('group_id', 2);//默认邀请人
        if (!empty($_POST)) {
            if (isset($_POST['name'])) {
                $_POST['name'] = trim($_POST['name']);
            }
            if ($_POST['transfer_ac']) {
                $transfer_ac = trim($_POST['transfer_ac']);
                $_POST['transfer_ac'] = M('User')->where("user_name='{$transfer_ac}'")->getField('id');
            }
            $result = $this->transaction_data(array($_POST), true);
            if ($result == '1000') {
                $this->success($this->comment[$result]);
            } else {
                $this->error($this->comment[$result]);
            }
        }
        $this->display();
    }

    /**
     * @desc   import data from xlsx
     * @access pulic
     */
    public function import(){
        if (empty($_FILES['file']['tmp_name'])){
            $this->error('上传的文件不能为空');
        }

        $data = $this->_getDataFromExcel();
        $this->transaction_data($data);
    }

    /**
     * 处理数据
     */
    private function transaction_data($data, $single = false) {
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            $this->error(\libs\utils\PaymentApi::maintainMessage());
            return;
        }
        //更改为上海爱金认证（2016-04-26）
        //$idno = new IdnoVerify();
        $idno = new CommonIdnoVerify();
        $batchno = intval(M("RegisterBatchLog")->max('batchno') + 1);
        $bonus_service = new \core\service\BonusService;
        $coupon_service = new \core\service\CouponService;
        $payment_service = new \core\service\PaymentService;
        $transfer_service = new \core\service\TransferService;
        $webBO = BOFactory::instance('web');
        foreach ($data as $user) {
            $user['user'] = $user['user_name'] ? $user['user_name'] :$webBO->genUsername('H');
            $user['status'] = $this->_checkData($user, $idno);
            $user['passwd'] = $this->_getPasswd();
            $user['created_at'] = get_gmtime();
            $user['batchno'] = $batchno;
            $user['id_type'] = 1;//默认全部为身份证
            $user['referer'] = DeviceEnum::DEVICE_WEB;//记录来源为批量注册
            if ($user['status'] == '1000') {
                $userInfo = $this->_getRegisterInfo($user);
                M("User")->startTrans();
                try {
                    if ($user['invite'] != '') {//优惠码
                        $coupon = $coupon_service->checkCoupon($user['invite']);
                        if (intval($coupon['refer_user_id']) >= 0) {
                            $userInfo['invite_code'] = $user['invite'];
                            $userInfo['refer_user_id'] = $coupon['refer_user_id'];
                        }
                    }
                    $id = M("User")->add($userInfo);
                    if (!$id) {
                        throw new Exception('插入用户表失败', '1009');
                    }
                    if ($userInfo['refer_user_id']) {
                        $coupon_service->regCoupon($id, $user['invite']);
                    }
                    $bonus_service->bind($id, $user['mobile']);

                    /* if ($user['idtype'] != 0) {//TODO 港澳台身份认证
                        if (M("User")->add($this->getHMTInfo($user)));
                    } */
                    if ($single && intval($user['bank']) > 0 && $user['acno'] != '') {
                        $bank_info = M("Bank")->where("`id`='{$user['bank']}'")->Field('id, name, short_name')->find();
                        $user['bank_id'] = $bank_info['bank'];
                        $user['bank'] = $bank_info['name'];
                        $user['bank_short_name'] = $bank_info['short_name'];
                    }
                    if (!empty($user['acno']))  {
                        if (!preg_match('/^\d+$/',$user['acno'])) {
                            throw new \Exception('银行卡号必须为连续的数字' . $user['acno'], '1017');
                        }
                        if (!M("UserBankcard")->add($this->_getUserBankData($user, $id))) {
                            throw new Exception("绑定银行卡失败", '1016');
                        }
                    }
                    //到先锋支付开户,使用payment的register方法(失败1010)
                    if (app_conf('PAYMENT_ENABLE')) {
                        try {
                            //$service = new PaymentService;
                            //$rs = $service->register($id);
                            $registerParam = array();
                            $registerParam['userId'] = $id; //用户id
                            $registerParam['realName'] = $user['name']; //真实姓名
                            $registerParam['cardType'] = '01'; //01-身份证,//02-港澳台
                            $registerParam['phoneNo'] = $user['mobile']; //证件号
                            $registerParam['cardNo'] = $user['idno'];
                            $registerParam['userType'] = '0'; //所有新实名注册的用户均为新用户
                            $result = PaymentApi::instance()->request("register",$registerParam);
                            if ($result['respCode'] == '00'&&($result['status'] == '00'||$result['status'] == '31')) {
                                $user['payment_user_id'] = intval($result['userId']);
                                if (!M('User')->save(array('payment_user_id' => intval($result['userId'])), array('where' => "`id`=$id"))) {
                                    throw new Exception("更新先锋账号失败", 1013);
                                }
                            } else {
                                throw new Exception ("开户检测没有通过", 1010);
                            }
                        } catch (Exception $e) {
                            $user['status'] = 1010;
                            throw new Exception($e->getMessage(), $e->getCode());
                        }
                    }
                    //发送短信(失败1012)
                    if (app_conf('SMS_ON') == 1) {
                        $res = \SiteApp::init()->sms->send($user['mobile'], "{$user['mobile']},{$user['passwd']}", $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']['TPL_SMS_FIRSTP2P_ACCOUNT'], 0);
                    } else {
                        $res = array('status' => 1);
                    }
                    if ($res['status'] != 1) {
                        $user['status'] = '1012';
                    }
                    M("User")->commit();
                    //支付绑定银行卡
                    if (app_conf('PAYMENT_ENABLE')) {
                        try {
                            $payment_rs = $payment_service->bankcardSync($id, $this->_getBankcardData($id, $user['acno'], $user['bank_short_name'], $user['bank']));
                            if (!$payment_rs) {
                                throw new \Exception('1015');
                            }
                        } catch (Exception $e) {
                            $user['status'] = $e->getMessage();
                        }
                    }
                    //转账(失败1011)
                    if ($user['transfer_ac'] && $user['transfer_money']) {
                        try {
                            if ($user['transfer_money'] >= 0 && $user['transfer_money'] <= 30) {
                                $transfer_rs = $transfer_service->transferById($user['transfer_ac'], $id, $user['transfer_money'], '注册返利', '新用户注册', '注册返利', '新用户注册',  'REGISTER');
                            }
                            if (!$transfer_rs) {
                                $user['status'] = '1011';
                            }
                        } catch (Exception $e) {
                            $user['status'] = '1011';
                        }
                    }

                } catch (Exception $e) {
                    $user['status'] = $e->getCode();
                    M("user")->rollback();
                }
            }
            // 注册优惠码
            $couponLogService = new \core\service\CouponLogService();
            $couponLogService->changeRegShortAlias($user['payment_user_id'], $user['invite']);
            $last_status = $user['status'];
            $user['created_at'] = date("YmdHis", $user['created_at']);
            $user['idtype'] = 0;//日志表中身份证类型
            M("RegisterBatchLog")->add($user);// 日志入库
        }
        if ($single) {
            return $last_status;
        }
        $this->_list(M("RegisterBatchLog"), "`batchno`={$batchno}");
        $this->assign('batchno', $batchno);
        $this->display('index');
    }

    /**
     * 支付绑定银行卡数据
     */
    private function _getBankcardData($user_id, $acno, $short_name, $bank_name) {
        return array(
            //'merchantId' => '',
            'userId' => $user_id,
            'cardNo' => $acno,
            'bankCode' => strval($short_name) ? $short_name : 'ICBC',
            'bankName' => strval($bank_name),
            'cardType' => 1,
            'province' => '',
            'city' => '',
            'branchBankId' => '',
            'branchBankName' => '',
            'businessType' => '1'
        );

    }

    /**
     * 转账操作
     */
    private function transfer_money($id, $transfer_user_id, $money, $message = '注册返利', $note = '注册返利') {
        return 1000;
    }

    /**
     *@desc   export data from db
     *@access pulic
     */
    public function export($start = 3) {

        //新建
        $excel = new PHPExcel();
        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();
        //设值头信息
        $num = ord('A');
        $data = M("RegisterBatchLog")->where($this->_getMap())->select(array('field' => array_keys($this->export)));
        foreach($data as $item){
            $ordA = ord('A');
            foreach ($this->export as $field => $name) {
                $value = $item[$field];
                if ($field == 'idtype') {
                    $value = $this->idTypes[$item[$field]];
                }
                if ($field == 'status') {
                    $value = $this->comment[$item[$field]];
                }
                $sheet->setCellValueExplicit(chr($ordA++).$start, $value, PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $start++;
        }
        $num = ord("A");
        $colCount = count($this->export);
        //设置头信息
        $sheet->mergeCells('A1:'.chr($num + $colCount - 1).'1');
        $sheet->setCellValue('A1', '用户列表'); //设置列名称
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //设置居中
        foreach ($this->export as $name) {
            $col = chr($num++);
            $sheet->getColumnDimension($col)->setAutoSize(true); //设置自动列宽
            $sheet->setCellValue($col.'2', $name); //设置列名称
            $sheet->getStyle($col.'2')->getFont()->setBold(true); //设置粗体
            $sheet->getStyle($col.'2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //设置居中
        }

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportuser',
                'analyze' => $this->_getMap()
                )
        );



        $sheet->setTitle('用户列表');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="users_'.date("Y-m-d_His").'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     *@desc
     *@access
     */
    public function openw() {
        $this->display('nodeshow');
    }

    /**
     * 输出模板文件
     */
    public function download() {
        Header("Location: static/admin/Common/register_template_new.xlsx");
        exit();
    }

    /**
     * 获取查询条件
     * @return array
     */
    private function _getMap() {
        $map = array();//$this->_search();
        if ($_REQUEST['batchno']) {
            return array('batchno' => array('eq',$_REQUEST['batchno']));
        }
        if ($_REQUEST['ids']) {
            return array('id' => array('in',explode(',', $_REQUEST['ids'])));
        }
        if ($_REQUEST['name']) {
            $map['name'] =  array('eq',$_REQUEST['name']);
        }
        if ($_REQUEST['mobile']) {
            $map['mobile'] = array('eq',$_REQUEST['mobile']);
        }
        $begin = $end = false;
        if ($_REQUEST['begin']) {
            $begin = $map['created_at'] = array('gt',date("YmdHis", strtotime($_REQUEST['begin'])));
        }
        if ($_REQUEST['end']) {
            $end = $map['created_at'] = array('lt',date("YmdHis", strtotime($_REQUEST['end'])));
        }
        if ($begin && $end) {
            $map['created_at'] = array($begin, $end);
        }
        if ($_REQUEST['status'] == 1) {
            $map['status'] = array('eq', 1000);;
        } elseif ($_REQUEST['status'] == 2) {
            $map['status'] = array('neq', 1000);
        }
        return $map;
    }

    /**
     * 从excel中获取数据
     * @return array
     */
    public function _getDataFromExcel() {

        if (empty($_FILES['file'])) {
            return array();
        }

        try {
            $fileType = PHPExcel_IOFactory::identify($_FILES['file']['tmp_name']);
            $reader = PHPExcel_IOFactory::createReader($fileType);
            $excel = $reader->load($_FILES['file']['tmp_name']);

            $sheet = $excel->getSheet(0); //第一个工作簿
            $rowCount = $sheet->getHighestRow(); //行数
            $data = array();
            for($currentRow = 3; $currentRow <= $rowCount; $currentRow++) {
                $row = array();
                $num = ord('A');
                foreach ($this->import as $field1 => $name) {
                    $row[$field1] = trim((string)$sheet->getCell(chr($num++).$currentRow)->getValue());
                }
                if (!$row['name'] || !$row['mobile'] || !$row['idno']) {
                    break;
                }
                $row['idtype'] = intval(array_search($row['idtype'], $this->idTypes));
                $data[] = $row;
            }
            return $data;
        } catch (Exception $e) {
           return array();
        }
    }

    /**
     * @desc data validate
     * @param array $user
     * @return int
     */
    private function _checkData($user, $idno) {

        if (!$this->_validatePhone($user['mobile'])) {
            return '1001';
        }
        // 身份证号采用加密存储，统一使用大写的X后缀
        $user['idno'] = strtoupper(trim($user['idno']));
        if (!$this->_validateId($user['idno'])) {
            return '1002';
        }
        //检查用户注册信息是否已经存在
        if (M("User")->where("`user_name`='{$user['user']}' OR `mobile`='{$user['mobile']}' OR `idno`='{$user['idno']}'")->getField('user_name')) {
            return '1003';
        }

        if (!empty($user['acno']) && M('UserBankcard')->where("`bankcard`='{$user['acno']}'")->getField('id')) {
            return '1014';
        }
        //使用公安部接口验证身份证号与名称，更改为上海爱金认证（2016-04-26）
        //$resultId = array('code' => 0);
        if (app_conf('ID5_VALID')) {
            $resultId = $idno->checkIdno($user['name'], $user['idno']);
            if ($resultId['code'] != 0) {
                $codes = array('-110' => '1006', '-111' => '1006', '-71' => '1006', '-53' => '1006', '-72' => '1006',
                                 '-31' => '1006', '-60' => '1004', '-66' => '1004', '-90' => '1006', '-200' => '1007', '-300' => '1008');
                return $codes[$resultId['code']];
            }
        }
        return '1000';
    }

    /**
     * @desc   format user data for insert db
     * @param  array $user
     * @return array $userInfo
     */
    private function _getRegisterInfo($user) {
        list( $year, $month, $day ) = $this->_getBirthDayByidNo($user['idno']);
        $coupon_level_id = $user['coupon_level_id'] ? intval($user['coupon_level_id']) : 1;
        $user['coupon_level_id'] = $coupon_level_id > 0 ? $coupon_level_id : 1;
        $userInfo = array (
                'user_name'              => $user['user'], // 会员名称，为附件中手机号加前缀
                'user_pwd'               => BOFactory::instance('web')->compilePassword($user['passwd']), // 随机生成，规则同修改密码，大小写数字的组合
                'idno'                   => $user['idno'],
                'real_name'              => $user['name'],
                'group_id'               => intval($user['group_id']), // 用户组id
                'is_effect'              => 1, // 帐户状态，1为有效果，0为无效
                'create_time'            => $user['created_at'], // 创建时间
                'updaet_time'            => $user['created_at'], // 创建时间
                // 'site_id' => '', //首次登录的分站ID
                'email'                  => $user['email'], // 邮箱地址 默认为空
                'mobile'                 => $user['mobile'], // 手机
                'mobilepassed'           => 1, // 手机认证
                // 'level_id' => '01', //信用等级
                'is_staff'               => 0, // 是否内部员工
                'channel_pay_factor'     => 1.0000, // 返利系数
                'coupon_level_id'        => $user['coupon_level_id'], // 会员等级
                'coupon_level_valid_end' => $user['created_at'] + 20 * 365 * 24 * 60 * 60,
                'force_new_passwd'       => 1,
                'sex'                    => $this->_getSex($user['idno']),
                'byear'                  => intval($year),
                'bmonth'                 => intval($month),
                'bday'                   => intval($day),
                'idcardpassed'           => 1,
                'idcardpassed_time'      => $user['created_at'],
                'referer'                => $user['referer'],
                'country_code'           => 'cn',
        );
        return $userInfo;
    }

    /**
     * @desc   format bank data for insert db
     * @param  array $user
     * @param  int $id
     * @return array
     */
    private function _getUserBankData($user, $id) {

        if (isset($user['bank_id']) && is_numeric($user['bank_id'])) {
            $bankId = $user['bank_id'];
        } else {
            $bankId = M("Bank")->where("`name`='{$user['bank']}'")->getField('id');
        }
        $bankInfo = array(
            'bank_id'   => intval($bankId),
            'bankcard' => $user['acno'],
            'bankzone'  => strval($user['branch']),
            'user_id'  => $id,
            'status'    => 1,
            'card_name' => $user['name'],
            'card_type' => 0,
            'region_lv1'=> 0,
            'region_lv2'=> 0,
            'region_lv3'=> 0,
            'region_lv4'=> 0,
            'verify_status' => 1,
            'create_time' => get_gmtime(),
        );
        return $bankInfo;
    }

    /**
     * @desc   get birth by identify number
     * @param  string $idno
     * @return array
     */
    private function _getBirthDayByidNo($idno) {
        $year = $month = $day = 0;
        $len = strlen($idno);
        if ($len == 15) {
            $year  = intval("19".substr($idno, 6, 2));
            $month = intval(substr($idno, 8, 2));
            $day   = intval (substr($idno, 10, 2));
        } elseif ($len == 18) {
            $year  = intval(substr($idno, 6, 4));
            $month = intval(substr($idno, 10, 2));
            $day   = intval(substr($idno, 12, 2));
        }
        return array((int)$year, (int)$month, (int)$day);
    }

    /**
     * @desc   get sex by identify number
     * @param  string $id
     * @return number
     */
    private function _getSex($id) {
        $pos = strlen($id) == 15 ? 14 : 16;
        return substr($id, $pos, 1) % 2;
    }

    /**
     * @desc   validate phone number
     * @param  string $number
     * @return boolean
     */
    private function _validatePhone($number) {
        if (!$number) {
            return false;
        }
        return preg_match('#^1[37][\d]{9}$|14^[0-9]\d{8}|^15[0-9]\d{8}$|^18[0-9]\d{8}$#', $number) ? true : false;
    }

    /**
     * @desc   rand passwd
     * @param  number $length
     * @return string
     */
    private function _getPasswd($length = 8) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $strlen = strlen($chars) - 1;
        $str = '';
        for ($i=0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, $strlen), 1);
        }
        return $str;
    }

    /**
     * @desc   validate identiy number
     * @param  string $idno
     * @return boolean
     */
    private function _validateId($idno) {
        if (!preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $idno)) {
            return false;
        }
        return true;
    }

    private function _validateBank($card) {

    }


}
