<?php
/**
 * Class NongdanAction
 *
 */
#use core\dao\FinanceQueueModel;
use core\dao\supervision\NongdanModel;
use core\service\user\UserService;
use core\service\supervision\NongdanService;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionService;
use core\enum\UserAccountEnum;
use NCFGroup\Common\Library\Idworker;


class NongdanAction extends CommonAction
{

    public $level_list = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function addPromotions()
    {
        try {
            $userId = intval($_GET['id']) ?  intval($_GET['id']) : 0;
            if (empty($userId))
            {
                throw new \Exception('用户id不能为空');
            }
            $userSrv = new UserService();
            $supervisionUserService = new SupervisionService();
            $supervisionUserInfo = $supervisionUserService->svInfo($userId, true);
            if (!$supervisionUserInfo['isSvUser'])
            {
                throw new \Exception('用户尚未开通网贷P2P账户');
            }
            $userInfo = UserService::getUserById($userId);
            $userInfo = array(
                'userId' => $userId,
                'username' => $userInfo['user_name'],
                'realname' => $userInfo['real_name'],
                'account'  => UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_SUPERVISION][$supervisionUserInfo['userPurpose']],
                'svBalance'  => $supervisionUserInfo['svBalance'],
            );
            $this->assign('userInfo', $userInfo);
        } catch (\Exception $e) {
            $this->assign('errorMsg', $e->getMessage());
        }
        $this->display('add_promotion');
    }

    public function doAddPromotions()
    {
        $result = ['errCode' => 0];
        try {
            $db = \libs\db\Db::getInstance('firstp2p', 'master');
            $db->startTrans();

            $receiverName= !empty($_POST['receiverName']) ? trim($_POST['receiverName']) : '';
            if (empty($receiverName))
            {
                throw new \Exception('转入用户名称不能为空');
            }

            $payerName = !empty($_POST['payerName']) ? trim($_POST['payerName']) : '';
            if (empty($payerName))
            {
                throw new \Exception('转出用户名称不能为空');
            }

            $money = !empty($_POST['money']) ? floatval($_POST['money']) : 0;
            if (empty($money) || $money <= 0)
            {
                throw new \Exception('金额不能为小于0的数');
            }

            $memo = !empty($_POST['memo']) ? trim($_POST['memo']) : '';
            $type = !empty($_POST['type']) ? intval($_POST['type']) : 0;
            if (empty($type))
            {
                throw new \Exception('划转类型不正确');
            }

            // 检查转出方用户信息
            $payUserId = NongdanService::checkAccount($payerName, NongdanService::ACCOUNT_PAYER, $money);
            //检查转入用户信息
            $receiveUserId = NongdanService::checkAccount($receiverName);
            // 发起人信息
            $adminInfo = es_session::get(md5(conf("AUTH_KEY")));

            // 获取表名
            $tableName = NongdanModel::instance()->tableName();
            // 通过检查，保存数据
            $nongdanRecord = [];
            $nongdanRecord['receive_user_id'] = $receiveUserId;
            $nongdanRecord['into_name']     = $receiverName;
            $nongdanRecord['pay_user_id']   = $payUserId;
            $nongdanRecord['out_name']      = $payerName;
            $nongdanRecord['money']         = $money*100;
            $nongdanRecord['type']          = $type;
            $nongdanRecord['apply_user']    = $adminInfo['adm_name'];
            $nongdanRecord['create_time']   = time();
            $nongdanRecord['info']          = $memo;
            $nongdanRecord['out_order_id']  = Idworker::instance()->getId();
            $db->autoExecute($tableName, $nongdanRecord, 'INSERT');
            $affRows = $db->affected_rows();
            if ($affRows < 0)
            {
                throw new \Exception('申请失败，请重试');
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            $result = ['errCode' => -1, 'errMsg' => $e->getMessage()];
        }
        echo json_encode($result);
    }

    public function index()
    {
        $status = intval($_REQUEST['status']);
        $auth_action = [];
        $is_auth_a = $this->is_have_action_auth(MODULE_NAME, 'step1');
        if ($is_auth_a && in_array($status, [0, NongdanModel::STATUS_WAIT_STEP1])) {
            $auth_action[] = array('n' => 'A角色', 'p' => 'step1', 'r' => 'refuse1');
        }
        $is_auth_b = $this->is_have_action_auth(MODULE_NAME, 'step2');
        if ($is_auth_b && in_array($status, [0, NongdanModel::STATUS_WAIT_STEP2])) {
            $auth_action[] = array('n' => 'B角色', 'p' => 'step2', 'r' => 'refuse2');
        }
        $this->assign('auth_action', $auth_action);
        set_time_limit(0);
        $_REQUEST['listRows'] = isset($_REQUEST['listRows']) ? intval($_REQUEST['listRows']) : 200;
        $type = intval($_REQUEST['type']);
        // $dealId = intval($_REQUEST['deal_id']);
        if ($status === 0) {
            unset($_REQUEST['status']);
        }

        if ($type === 0) {
            unset($_REQUEST['type']);
        }

        // if ($dealId) {
        //     $map[DB_PREFIX . 'nongdan.deal_load_id'] = $dealId;
        // }
        $apply_time_start = $apply_time_end = 0;
        if (!empty($_REQUEST['apply_time_start'])) {
            $apply_time_start = strtotime($_REQUEST['apply_time_start']);
            $map['create_time'] = array('egt', $apply_time);
        }

        $into_num = trim($_REQUEST['into_num']);
        if($into_num) {
            $map['into_name'] = userNumToUserName($into_num);
        }
        $out_num = trim($_REQUEST['out_num']);
        if($out_num) {
            $map['out_name'] = userNumToUserName($out_num);
        }

        if (!empty($_REQUEST['apply_time_end'])) {
            $apply_time_end = strtotime($_REQUEST['apply_time_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $apply_time_start, $apply_time_end));
        }

        $this->assign("default_map", $map);
        parent::index();
    }

    /**
     * A角色审核 第一步
     * @actionlock
     */
    public function step1()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("Nongdan")->where("id = $id")->find();
            if ($info['status'] != 1) {
                if ($ajax) {
                    $this->ajaxReturn('该记录状态不是[A角色待审核]，无法处理', '', 1);
                    continue;
                }
                $this->ajaxReturn('该记录状态不是[A角色待审核]，无法处理', '', 1);
            }
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $data = array();
            $data['update_time'] = time();
            $data['admin'] = $adm_session['adm_name'];
            $data['log'] = date("Y-m-d H:i:s") . " A角色批准:" . $adm_session['adm_name'];
            $data['status'] = 2;
            $rs = M("Nongdan")->where('id=' . $id)->data($data)->save();
            if ($rs) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('批准成功！', '', 1);
            }
        }
        //foreach
        $this->ajaxReturn('批准成功！', '', 1);
        exit;
    }

    /**
     * B角色审核 第二步 也是审核通过
     * @actionLock
     */
    public function step2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误11！', '', 1);
            }
            $info = M("Nongdan")->where("id = $id")->find();
            if ($info['status'] != 2) {
                if ($ajax) {
                    $this->ajaxReturn('该记录状态不是[B角色待审核]，无法处理', '', 1);
                    continue;
                }
                $this->ajaxReturn('该记录状态不是[B角色待审核]，无法处理', '', 1);
            }
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if ($info['admin'] == $adm_session['adm_name']) {
                if ($ajax) {
                    $this->ajaxReturn('同一个管理员不能进行操作！', '', 1);
                    continue;
                }
                $this->ajaxReturn('同一个管理员不能进行操作！', '', 1);
            }
            $finance = new NongdanModel();
            $finance_info = $finance->find($id);
            $log_info = $info['log'] . "<br>" . date("Y-m-d H:i:s") . " B角色批准:" . $adm_session['adm_name'];
            $sql = "UPDATE firstp2p_nongdan SET `status` = '3', `log` = '{$log_info}', `update_time` = '".time()."', `admin` = '".$adm_session['adm_name']."' WHERE `id`='{$id}' AND `status` = 2 ";
            try {
                $finance->db->query($sql);
                if($finance->db->affected_rows() <= 0){
                    throw new Exception('数据更新失败,请刷新后重试！');
                }
            } catch (\Exception $e) {
                $this->ajaxReturn($e->getMessage(), '', 1);
            }
            if ($ajax) {
                continue;
            }
            //转账的返回
            if ($re && $info['type'] == 1) {
                $this->ajaxReturn($re, '', 1);
            }
        }
        //foreach
        $this->ajaxReturn('批准成功！', '', 1);
        exit;
    }

    /**
     * A角色拒绝
     * @actionlock
     */
    public function refuse1()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        $status = getRequestInt("status");
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("Nongdan")->where("id = $id")->find();
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if ($info['status'] != 1) {
                if ($ajax) {
                    $this->ajaxReturn('该记录状态不是[A角色待审核]，无法处理', '', 1);
                    continue;
                }
                $this->ajaxReturn('该记录状态不是[A角色待审核]，无法处理', '', 1);
            }
            if ($info['admin'] == $adm_session['adm_name']) {
                if ($ajax) {
                    $this->ajaxReturn('同一个管理员不能进行操作！', '', 1);
                    continue;
                }
                $this->ajaxReturn('同一个管理员不能进行操作！', '', 1);
            }
            $finance = new NongdanModel();
            $finance_info = $finance->find($id);
            $finance_info->update_time = time();
            $finance_info->admin = $adm_session['adm_name'];
            $finance_info->log = date("Y-m-d H:i:s") . " A角色拒绝:" . $adm_session['adm_name'];
            $finance_info->status = -1;
            //开始事务
            $GLOBALS['db']->startTrans();
            try {
                $finance_info->save();
                $r = $this->Failed($info);
                if ($r == false) {
                    throw new Exception('事务数据更新错误！');
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $this->ajaxReturn('参数错误！事务操作refuse1', '', 1);
            }
            if ($ajax) {
                continue;
            }
            $this->ajaxReturn('操作成功！', '', 1);
        }
        //foreach
        $this->ajaxReturn('操作成功！', '', 1);
        exit;
    }

    /**
     *  B角色拒绝
     * @actionlock
     */
    public function refuse2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("Nongdan")->where("id = $id")->find();
            if ($info['status'] != 2) {
                if ($ajax) {
                    $this->ajaxReturn('该记录状态不是[B角色待审核]，无法处理', '', 1);
                    continue;
                }
                $this->ajaxReturn('该记录状态不是[B角色待审核]，无法处理', '', 1);
            }
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $finance = new NongdanModel();
            $finance_info = $finance->find($id);
            $finance_info->update_time = time();
            $finance_info->admin = $adm_session['adm_name'];
            $finance_info->log = date("Y-m-d H:i:s") . " B角色拒绝:" . $adm_session['adm_name'];
            $finance_info->status = -1;
            //开始事务
            $GLOBALS['db']->startTrans();
            try {
                $finance_info->save();
                $r = $this->Failed($info);
                if ($r == false) {
                    throw new Exception('事务数据更新错误！');
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $this->ajaxReturn('参数错误！事务操作refuse2', '', 1);
            }
            if ($ajax) {
                continue;
            }
            $this->ajaxReturn('操作成功！', '', 1);
        }
        //foreach
        $this->ajaxReturn('操作成功！', '', 1);
        exit;
    }

    /**
     * 更新用户资金
     * @param $info
     */
    private function update_money($info, $is_ajax = 0)
    {
        set_time_limit(0);
        //只能同 B角色待审核的状态 才可以继续
        if($info['status'] != 2){
            return false;
        }
        return true;
    }
    /**
     * 转账失败
     * @param $info
     */
    protected function Failed($info)
    {
        if ($info['type'] == 1) { //转账
            $sendUser = UserService::getUserByName($info['out_name']);
            $money = $info['money'];
            // TODO finance? 后台 转账失败 转出用户账户回款 | 不同步
            $accountId = AccountService::getUserAccountId($sendUser['id'], $sendUser['user_purpose']);
            $note = '您的账户向会员' . $info['into_name'] . '的账户转入金额' . $money . '元' . ' ' . $info['info'];
            AccountService::changeMoney($accountId, $money, "转账申请失败", $note, AccountEnum::MONEY_TYPE_LOCK_REDUCE);
            return true;
        }
        return true;
    }

    /**
     * 查询相关字段
     */
    protected function form_index_list(&$list)
    {
        //$coupon_level_service = new \core\service\CouponLevelService();
        $this->level_list = []; //$coupon_level_service->getAllLevels();
        foreach ($list as &$item) {
            // 查询优惠券短码
            $item['into_user_names'] = $this->get_username($item['into_name'], $item, $this->level_list);
            $item['out_user_names'] = $this->get_username($item['out_name']);
        }
    }

    /**
     * 格式化 输出name
     * @param $user1
     * @param null $row
     * @return string
     */
    private function get_username($user1, $row = null, $level_list = null)
    {
            if ($level_list == null) {
                $str = '<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $user1 . '">' . $user1 . '</a>';
            } else {
                $str = '<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $user1 . '">' . $user1 . '</a>';
            }
            if ($row['attach_name']) {
                $userInfo = UserService::getUserByName($row['attach_name']);
                $user_info['coupon_level_id'] = !empty($userInfo['coupon_level_id']) ? $userInfo['coupon_level_id'] : 0;
                $leve_str = $level_list[$user_info['coupon_level_id']]['group_name'] . " - " . $level_list[$user_info['coupon_level_id']]['level'];
                $str .= '&nbsp;<br>&nbsp;推&nbsp;<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $row['attach_name'] . '">' . $row['attach_name'] . '</a>（' . $leve_str . '）';
            }
            if ($row['agency_name']) {
                $str .= '&nbsp;<br>&nbsp;机&nbsp;<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $row['agency_name'] . '">' . $row['agency_name'] . '</a>';
            }
            return $str;
    }

    /**
     * csv数据导入
     */
    public function do_import()
    {
        //文件检查
        if ($_FILES['upfile']['error'] == 4) {
            $this->error("请选择文件！");
            exit;
        }
        if ($_FILES['upfile']['type'] != 'text/csv' && $_FILES['upfile']['type'] != 'application/vnd.ms-excel') {
            $this->error("请上传csv格式的文件！");
            exit;
        }
        set_time_limit(0);
        $max_line_num = 10000;
        ini_set('memory_limit', '2G');
        $file_line_num = count(file($_FILES['upfile']['tmp_name']));
        if ($file_line_num > $max_line_num + 1) {
            $this->error("处理的数据不能超过{$max_line_num}行");
        }

        //读取csv数据
        $row_no = 1;
        $row_head_array = array('转入账户会员名称', '转入账户会员姓名', '转出账户会员名称','转出账户会员姓名', '转账金额', '业务类型', '标的编号','备注');
        $list = array();
        $errorMsg = []; //记录错误行
        if (($handle = fopen($_FILES['upfile']['tmp_name'], "r")) !== FALSE) {
            while (($row = fgetcsv($handle)) !== FALSE) {
                if ($row_no == 1) { //第一行标题，检查标题行
                    if (count($row) != count($row_head_array)) {
                        $this->error("第一行标题不正确！");
                        exit;
                    }
                    for ($i = 0; $i < count($row_head_array); $i++) {
                        $row[$i] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$i])));
                        if ($row[$i] != $row_head_array[$i]) {
                            $this->error("第" . ($i + 1) . "列标题不正确！应为'{$row_head_array[$i]}'");
                            exit;
                        }
                    }
                } else { //数据
                    $item = array();
                    $item['into_user_name']  = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[0])));
                    $item['into_real_name']  = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[1])));
                    $item['out_user_name']   = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[2])));
                    $item['out_real_name']   = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[3])));
                    $item['money']      = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[4])));
                    $item['typeName']   = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[5])));
                    $item['dealId']     = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[6])));
                    $item['info']       = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[7])));
                    //检查数据
                    try {
                        $item['type'] = array_search($item['typeName'], NongdanModel::$typeDesc);
                        // var_dump($item['type']);
                        NongdanService::checkAccount($item['out_user_name'], NongdanService::ACCOUNT_PAYER, $item['money'], $item['type'], $item['out_real_name']);
                        NongdanService::checkAccount($item['into_user_name'], NongdanService::ACCOUNT_RECEIVER, $item['money'], $item['type'], $item['into_real_name']);
                        NongdanService::checkType($item['typeName']);
                        NongdanService::checkDeal($item['dealId'], $item['type'], $item['out_user_name']);
                        unset($item['typeName']);
                    } catch (\Exception $e) {
                        $message = $e->getMessage();
                        $errorMsg[] = "第{$row_no}行, {$message}";
                        $row_no++;
                        continue;
                    }
                    $list[] = $item;
                }
                $row_no++;
            }
            fclose($handle);
            @unlink($_FILES['upfile']['tmp_name']);
        }

        if (empty($list)) {
            $this->error("导入数据为空！");
            exit;
        }

        //导入
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $adm_id = intval($adm_session['adm_id']);

        $row_no = 2;
        $GLOBALS['db']->startTrans();
        foreach ($list as $item) {
            //检查用户名
            $out_user_name = UserService::getUserByName(trim($item['out_user_name']));
            $into_user_name = UserService::getUserByName(trim($item['into_user_name']));
            if (empty($out_user_name) || empty($into_user_name)) {
                $GLOBALS['db']->rollback();
                $this->error("第{$row_no}行'{$row_head_array[0]}'或'{$row_head_array[1]}'不正确！");
                exit;
            }
            //入记录
            $nongdan_model = new NongdanModel();
            $nongdan_model['out_order_id'] = Idworker::instance()->getId();
            $nongdan_model['out_name'] = $item['out_user_name'];
            $nongdan_model['pay_user_id'] = $out_user_name['id'];
            $nongdan_model['into_name'] = $item['into_user_name'];
            $nongdan_model['receive_user_id'] = $into_user_name['id'];
            $nongdan_model['money'] = $item['money']*100;
            $nongdan_model['info'] = $item['info'];
            $nongdan_model['type'] = $item['type'];
            $nongdan_model['create_time'] = time();
            $nongdan_model['apply_user'] = $adm_name;
            $nongdan_model['deal_id'] = $item['dealId'];
            $rs1 = $nongdan_model->insert();
            $row_no++;
        }
        $rs = $GLOBALS['db']->commit();
        if ($rs) {
            if (!empty($errorMsg)) {
                $this->error("导入失败！<br />" . implode('<br />', $errorMsg));
                exit;
            }
            $this->success("导入成功，导入数据" . count($list) . "条！");
        } else {
            $GLOBALS['db']->rollback();
            $this->error("导入失败！");
        }
    }

    /**
     * changlu  2014年7月22日
     * 导出数据
     */
    public function export()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '300M');

        $datatime = date("YmdHis", time());
        $file_name = 'nongdan_' . $datatime;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');

        $into_name = trim($_REQUEST['into_name']);
        $out_name = trim($_REQUEST['out_name']);
        $deal_id = trim($_REQUEST['deal_id']);
        $apply_user = trim($_REQUEST['apply_user']);
        $type = intval($_REQUEST['type']);
        $status = $_REQUEST['status'];

        $into_num = trim($_REQUEST['into_num']);
        if($into_num && empty($into_name)) {
            $into_name = userNumToUserName($into_num);
        }
        $out_num = trim($_REQUEST['out_num']);
        if($out_num && empty($out_name)) {
            $out_name = userNumToUserName($out_num);
        }

        // $deal_s = intval($_REQUEST['deal_id_s']);
        // $deal_e = intval($_REQUEST['deal_id_e']);

        //是否从备份库
        $db = $GLOBALS['db'];
        $from_backup = intval($_REQUEST['backup']);
        if ($from_backup) {
            $db = $GLOBALS['db']::getInstance('firstp2p_deleted', 'slave');
        }
        // if ($type == 2) { //优惠码结算
        //     $head = array('编号', '审核状态', '审批记录', '申请人', '申请时间', '投资记录ID', '成交时间', '投资人会员名称', '投资金额', '借款标题', '期限', '推荐会员名称', '机构会员名称', '优惠券短码', '投资人返点金额', '投资人返点比例金额', '投资人返点比例', '推荐人返点金额', '推荐人返点比例金额', '推荐人返点比例', '机构返点金额', '机构返点比例金额', '机构返点比例',);
        //     $sql = 'SELECT f.id as id,f.status as status,f.log as log,apply_user,f.create_time as apply_time,f.deal_load_id as deal_load_id,pay_time,consume_user_id,deal_load_money,project_name,c.deal_id as deal_id,refer_user_id,agency_user_id,short_alias,rebate_amount,rebate_ratio_amount,rebate_ratio,referer_rebate_amount,referer_rebate_ratio_amount,referer_rebate_ratio,agency_rebate_amount,agency_rebate_ratio_amount,agency_rebate_ratio FROM `firstp2p_nongdan` AS f LEFT JOIN `firstp2p_coupon_log` AS c ON f.`coupon_id` = c.`id` WHERE type = 2 ';
        //     if ($into_name) {
        //         $sql .= " AND into_name = '{$into_name}' ";
        //     }
        //     if ($out_name) {
        //         $sql .= " AND out_name = '{$out_name}' ";
        //     }
        //     if ($apply_user) {
        //         $sql .= " AND apply_user = '{$apply_user}' ";
        //     }
        //     if ($deal_s && $deal_e) {
        //         $sql .= " AND f.deal_load_id BETWEEN '{$deal_s}' AND '{$deal_e}' ";
        //     } else {
        //         if ($deal_s) {
        //             $sql .= " AND f.deal_load_id = '{$deal_s}' ";
        //         }
        //         if ($deal_e) {
        //             $sql .= " AND f.deal_load_id = '{$deal_e}' ";
        //         }
        //     }
        //     if ($status) {
        //         $sql .= " AND f.status = '{$status}' ";
        //     }
        //     $apply_sql = '';
        //     $apply_time_start = $apply_time_end = 0;
        //     if (!empty($_REQUEST['apply_time_start'])) {
        //         $apply_time_start = strtotime($_REQUEST['apply_time_start']);
        //         $apply_sql = " AND creat_time >= '{$apply_time_start}' ";
        //     }

        //     if (!empty($_REQUEST['apply_time_end'])) {
        //         $apply_time_end = strtotime($_REQUEST['apply_time_end']);
        //         $apply_sql = " AND create_time BETWEEN '{$apply_time_start}' AND '{$apply_time_end}'";
        //     }
        //     if (!empty($apply_sql)) {
        //         $sql .= $apply_sql;
        //     }

        //     //echo $sql;exit;
        //     $list = $db->getAll($sql);
        //     $status_arr = array('-1' => '拒绝', '1' => 'a角色待审核', '2' => 'b角色待审核', '3' => '审核通过');
        //     foreach ($list as $k => $v) {
        //         $deal_id = $v['deal_id'];
        //         if ($deal_id) {
        //             $deal_info = M('Deal')->where('id=' . $deal_id)->find();
        //             $v['project_name'] = $deal_info['name'];
        //             $v['deal_id'] = $deal_info['repay_time'];
        //         }
        //         $v['status'] = $status_arr[$v['status']];
        //         $consume_user_id = $v['consume_user_id'];
        //         if ($consume_user_id) {
        //             $consume_user = M("User")->where('id=' . $consume_user_id)->find();
        //             $v['consume_user_id'] = $consume_user['user_name'];
        //         }
        //         $refer_user_id = $v['refer_user_id'];
        //         if ($refer_user_id) {
        //             $refer_user = M("User")->where('id=' . $refer_user_id)->find();
        //             $v['refer_user_id'] = $refer_user['user_name'];
        //         }
        //         if ($v['agency_user_id']) {
        //             $agency_user = M("User")->where('id=' . $v['agency_user_id'])->find();
        //             $v['agency_user_id'] = $agency_user['user_name'];
        //         }
        //         if ($v['apply_time']) {
        //             $v['apply_time'] = to_date($v['apply_time']);
        //         }
        //         if ($v['pay_time']) {
        //             $v['pay_time'] = to_date($v['pay_time']);
        //         }
        //         $list[$k] = $v;
        //     }
        // }
        // else { //会员转账
            $head = array('转入账户会员名称', '转出账户会员名称', '转账金额', '业务类型', '标的编号', '申请人', '申请时间', '备注');
            $sql = "SELECT into_name,out_name,money,type,deal_id,apply_user,create_time,info FROM `firstp2p_nongdan` WHERE 1=1 ";
            if ($into_name) {
                $sql .= " AND into_name = '{$into_name}' ";
            }
            if ($out_name) {
                $sql .= " AND out_name = '{$out_name}' ";
            }
            if ($apply_user) {
                $sql .= " AND apply_user = '{$apply_user}' ";
            }
            if ($status) {
                $sql .= " AND `status` = '{$status}' ";
            }
            if ($type) {
                $sql .= " AND `type` = '{$type}' ";
            }
            if ($deal_id) {
                $sql .= " AND `deal_id` = '{$deal_id}' ";
            }
            $apply_sql = '';
            $apply_time_start = $apply_time_end = 0;
            if (!empty($_REQUEST['apply_time_start'])) {
                $apply_time_start = strtotime($_REQUEST['apply_time_start']);
                $apply_sql = " AND creat_time >= '{$apply_time_start}' ";
            }

            if (!empty($_REQUEST['apply_time_end'])) {
                $apply_time_end = strtotime($_REQUEST['apply_time_end']);
                $apply_sql = " AND create_time BETWEEN '{$apply_time_start}' AND '{$apply_time_end}'";
            }
            if (!empty($apply_sql)) {
                $sql .= $apply_sql;
            }

            $list = $db->getAll($sql);
            foreach ($list as $k => $v) {
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $v['type'] = NongdanModel::$typeDesc[$v['type']];
                $v['money'] = bcdiv($v['money'], 100, 2);
                $list[$k] = $v;
            }
        // }
        $fp = fopen('php://output', 'a');
        foreach ($head as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $head);

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportNongdan',
                'analyze' => $sql
            )
        );

        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        foreach ($list as $arr) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            foreach ($arr as $k => $v) {
                $arr[$k] = iconv("utf-8", "gbk//IGNORE", $v);
            }
            fputcsv($fp, $arr);
        }
        exit;
    }
}
