<?php
/**
 * 合同中心-合同列表
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;
use libs\web\Form;
use web\controllers\BaseAction;
//ini_set('display_errors', 1);
use libs\idno\CommonIdnoVerify;
//error_reporting(E_ALL);
use core\dao\UserGroupModel;
use core\service\user\BOFactory;
use core\dao\BankModel;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
class RegisterAssistant extends BaseAction {

    public function init() {
        if (isset($_POST['user_name'])) {
            $_POST['user_name'] = htmlspecialchars(trim($_POST['user_name']));
        }
        if (isset($_POST['bank_id'])) {
            $_POST['bank_id'] = intval($_POST['bank_id']);
        }
        if (isset($_POST['acno'])) {
            $_POST['acno'] = htmlspecialchars(trim($_POST['acno']));
        }
        if (isset($_POST['mobile'])) {
            $_POST['mobile'] = htmlspecialchars(trim($_POST['mobile']));
        }
        if (isset($_POST['email'])) {
            $_POST['email'] = htmlspecialchars(trim($_POST['email']));
        }
        if (isset($_POST['invite'])) {
            $_POST['invite'] = htmlspecialchars(trim($_POST['invite']));
        }
        if (isset($_POST['mobile'])) {
            $_POST['mobile'] = htmlspecialchars(trim($_POST['mobile']));
        }

    }

    public function invoke() {
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            return $this->show_tips(\libs\utils\PaymentApi::maintainMessage(), '温馨提示', 0, '', '/');
        }

        $whiteList = explode(',', app_conf('O2O_INVITERS'));
        if (!in_array($GLOBALS['user_info']['id'], $whiteList)) {
            $this->show_error('无权访问');
        }


        $cs = new \core\service\CouponService;
        $coupon = $cs->getOneUserCoupon($GLOBALS['user_info']['id']);
        $this->tpl->assign('invite', $coupon['short_alias']);
        if (!empty($_POST)) {
            try {
                if (empty($_POST['user']) && empty($_POST['mobile'])) {
                    throw new \Exception('用户名称或者手机号必须填写一项');
                }
                if ($coupon['short_alias'] != trim($_POST['invite'])) {
                    throw new \Exception('优惠码不能修改');
                }
            }
            catch (\Exception $e) {
                $this->show_error('开户信息不完全，请重新填写:'.$e->getMessage());
            }
            $user = $_POST;
            $user['user'] = $user['user_name'] ? $user['user_name'] :"H".$user['mobile'];
            $user['passwd'] = $this->_getPasswd();
            $user['created_at'] = get_gmtime();
            $user['id_type'] = 1;//默认全部为身份证
            $user['referer'] = DeviceEnum::DEVICE_WEB;//记录来源为批量注册
            $user['referer_user_id'] = $GLOBALS['user_info']['id'];
            $user['invite_code'] = $user['invite'];
            //使用公安部接口验证身份证号与名称，更改为上海爱金认证（2016-04-26）
            //$resultId = array('code' => 0);
            $idno = new CommonIdnoVerify();
            $idno_checkResult = 0;
            if (app_conf('ID5_VALID')) {
                $resultId = $idno->checkIdno($user['name'], $user['idno']);
                $idno_checkResult = $resultId['code'];
                //这段代码貌似没用，$result都没定义
                /*
                 if ($resultId['code'] != 0) {
                    $codes = array('-998' => '1004', '-999' => '1004', '-810' => '1005', '-820' => '1005', '-200' => '1006',
                                     '-210' => '1006', '-100' => '1007', '-110' => '1008');
                    $idno_checkResult = $codes[$result['code']];
                }
                */
            }
            $GLOBALS['db']->startTrans();
            try {
                if ($idno_checkResult != 0) {
                    throw new \Exception('实名认证失败');
                }
                $user['status'] = $idno_checkResult == 0 ? 1 : 0;

                $userInfo = $this->_getRegisterInfo($user);

                $GLOBALS['db']->autoExecute('firstp2p_user', $userInfo, 'INSERT');
                $affected_rows = $GLOBALS['db']->affected_rows();
                $id = $GLOBALS['db']->insert_id();
                if ($affected_rows <= 0) {
                    throw new \Exception('用户注册失败');
                }
                // 开户
                if (intval($user['bank']) > 0 && $user['acno'] != '') {
                    $bank_info = $GLOBALS['db']->get_slave()->getRow("SELECT id,name,short_name FROM firstp2p_bank WHERE `id`='{$user['bank']}'");
                    $user['bank_id'] = $bank_info['bank'];
                    $user['bank'] = $bank_info['name'];
                    $user['bank_short_name'] = $bank_info['short_name'];
                }
                if (!empty($user['acno']))  {
                    if (!preg_match('/^\d+$/',$user['acno'])) {
                        throw new \Exception('银行卡号必须为连续的数字');
                    }
                    $GLOBALS['db']->autoExecute('firstp2p_user_bankcard', $this->_getUserBankData($user, $id), 'INSERT');
                    $affected_rows = $GLOBALS['db']->affected_rows();
                    if ($affected_rows <= 0) {
                        throw new \Exception('银行卡信息保存失败');
                    }
                }
                //到先锋支付开户,使用payment的register方法(失败1010)
                if (app_conf('PAYMENT_ENABLE')) {
                    //$service = new PaymentService;
                    //$rs = $service->register($id);
                    $registerParam = array();
                    $registerParam['userId'] = $id; //用户id
                    $registerParam['realName'] = $user['name']; //真实姓名
                    $registerParam['cardType'] = '01'; //01-身份证,//02-港澳台
                    $registerParam['phoneNo'] = $user['mobile']; //证件号
                    $registerParam['cardNo'] = $user['idno'];
                    $registerParam['userType'] = '0'; //所有新实名注册的用户均为新用户
                    $result = \libs\utils\PaymentApi::instance()->request("register",$registerParam);
                    if ($result['respCode'] == '00'&&($result['status'] == '00'||$result['status'] == '31')) {
                        $user['payment_user_id'] = intval($result['userId']);
                        $GLOBALS['db']->autoExecute('firstp2p_user', array('payment_user_id' => intval($result['userId'])), 
                            'UPDATE', " id = '{$id}'");
                        $affected_rows = $GLOBALS['db']->affected_rows();
                        if ($affected_rows <= 0) {
                            throw new \Exception("更新先锋账号失败", 1013);
                        }
                    } else {
                        throw new \Exception ("开户检测没有通过", 1010);
                    }
                }
                //发送短信(失败1012)
                if (app_conf('SMS_ON') == 1) {
                    $res = \SiteApp::init()->sms->send($user['mobile'], "{$user['user']},{$user['passwd']}", $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']['TPL_SMS_FIRSTP2P_ACCOUNT'], 0);
                } else {
                    $res = array('status' => 1);
                }
                //支付绑定银行卡
                $payment_service = new \core\service\PaymentService;
                $payment_rs = $payment_service->bankcardSync($id, $this->_getBankcardData($id, $user['acno'], $user['bank_short_name'], $user['bank']));
                if (!$payment_rs) {
                    $user['status'] = '1015';
                }

                $GLOBALS['db']->commit();
                $this->show_success('操作开户成功');
            }
            catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $this->show_error($e->getMessage());

            }
            return;
        }

        $this->tpl->assign("bank_list", BankModel::instance()->findAll());
        $this->tpl->assign ( "inc_file", 'web/views/account/registerAssistant.html' );
        $this->template = "web/views/account/frame.html";
    }

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
                'refer_user_id'        => $user['referer_user_id'],
                'invite_code'        => $user['invite_code'],
        );
        return $userInfo;
    }
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
    private function _getSex($id) {
        $pos = strlen($id) == 15 ? 14 : 16;
        return substr($id, $pos, 1) % 2;
    }
    private function _getPasswd($length = 8) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $strlen = strlen($chars) - 1;
        $str = '';
        for ($i=0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, $strlen), 1);
        }
        return $str;
    }
    private function _getUserBankData($user, $id) {

        $bankId = 0;
        if (isset($user['bank_id']) && is_numeric($user['bank_id'])) {
            $bankId = $user['bank_id'];
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


}

