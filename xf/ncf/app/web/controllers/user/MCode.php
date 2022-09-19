<?php

/**
 * 获取手机验证码
 * @author 晓安<zhaoxiaoan@ucfgroup.com>
 *
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Block;
use libs\utils\Logger;
use libs\utils\Risk;
use core\service\risk\RiskServiceFactory;
use core\service\sms\MobileCodeService;
use core\service\coupon\CouponService;

class MCode extends BaseAction {

    /**
     * H5不需要图形验证码策略暂时去掉
     */
    //private $_isH5 = false;

    public function init() {
        //open.firstp3p.com 成为开发者
        if ($this->checkAllow()) {
            echo htmlspecialchars($_GET['jsonpCallback']) . '(';

            if (empty($_COOKIE['FSESSID'])) {
                echo json_encode(array('code' => 1, 'message' => '您还未登录或者登录已经过期'));
                return false;
            }

            if (empty($GLOBALS['user_info']['mobile'])) {
                echo json_encode(array('code' => 1, 'message' => '未找到用户的手机号码'));
                return false;
            }

            $_POST['active'] = 1;
            $_POST['type'] = 13;
            $_POST['mobile'] = $GLOBALS['user_info']['mobile'];

            return true;
        }

        //禁止 get 提交
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return false;
        }
    }

    public function invoke() {
        $form = new Form('post');
        $form->rules = array(
            'mobile' => array('filter' => 'reg', "message" => "手机号码格式错误", "option" => array("regexp" => "/^1[3456789]\d{9}$/")),
            'type' => array('filter' => 'int', 'message' => '参数错误'),
            'isrsms' => array('filter' => 'int', 'message' => '参数错误'),
            'captcha' => array('filter' => 'string', 'message' => '参数错误'),
            'active' => array("filter" => "string"),
            'invite' => array('filter' => 'string'),
            'client_id' => array('filter' => 'string'),
            'password' => array('filter' => 'length', 'message' => '请输入6-20个字符', "option" => array("optional" => true, "min" => 6, "max" => 20)),
            'smLoginToken' => array('filter' => 'string'),
            'idno' => array('filter' => 'string'),
            'vname' => array('filter' => 'string'),
        );

        $errno = 0;
        $errmsg = '';
        if (!empty($_POST['country_code']) && isset($GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]) && $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['is_show']){
            $form->rules['mobile'] =  array(
                'filter' => 'reg',
                "message" => "手机格式错误",
                "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['regex']}/")
            );
        }

        if (!$form->validate()) {
            $errno = -1;
            $errmsg = '手机号格式错误';
            echo json_encode(array('code' => $errno, 'message' => $errmsg));
            setLog(array('errno' => $errno, 'errmsg' => $errmsg));
            return;
        }
        $type = $form->data['type'];
        $this->client_id = $form->data['client_id'];

        // 状态码请参考service
        $rspnMsg = false;
        //referer 封禁
        $arr = explode("\\", __CLASS__);
        $forbidRefererRet = forbidReferer(true, array_pop($arr), $this->forbidOtherRefer());

        $debugMsg = __CLASS__ . "|" . $_SERVER['HTTP_USER_AGENT'] . "|" . json_encode($forbidRefererRet) . "|" . json_encode($form->data);
        $active = intval($form->data['active']);
        $captcha = $form->data['captcha'];

        //记录短信接口调用次数
        if($type == 16){
            //校验smLoginToken
            $smLoginToken = \es_session::get('smLoginToken');
            if( empty($smLoginToken) || $form->data['smLoginToken'] != $smLoginToken ){
                //非法请求
                $errno = -13;
                $errmsg = '请求超时，请刷新页面';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                echo $rspnMsg;
                return;
            }
            Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WEB', 'SM_LOGIN_INTERFACE_START',json_encode($form->data))));
            //同步，进行安全监测
            $form->data['username'] = $form->data['mobile'];
            RiskServiceFactory::instance(Risk::BC_LOGIN)->check($form->data,Risk::SYNC);
            if(!empty($_SESSION['risk_login_frequent'])){
                Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WEB', 'SM_LOGIN_INTERFACE_RISK_FREQUENT',json_encode($form->data))));
                $errno = -13;
                $errmsg = '手机号码发送频率超过限制，请稍后再试';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                echo $rspnMsg;
                return;
            }
            if(!empty($_SESSION['risk_login_illegal'])){
                Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WEB', 'SM_LOGIN_INTERFACE_RISK_ILLEGAL',json_encode($form->data))));
                $errno = -13;
                $errmsg = '设备指纹非法';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                echo $rspnMsg;
                return;
            }
        }
        do {
            // 验证token
            if (!$this->check_token() && $active == 0) {
                $errno = -3;
                $errmsg = '系统繁忙，请稍后重试';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                break;
            }

            if ($forbidRefererRet['forbid']) {
                $errno = -2;
                $errmsg = 'illegal request';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                break;
            }

            if (!in_array($type, array(1, 2, 9, 11, 12, 13, 14,16,17))) {
                $errno = -1;
                $errmsg = '参数错误';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                break;
            }


            if (in_array($type, array(1, 11, 16))) {
                //手机短信登录，验证码
                $vname = empty($form->data['vname']) ? 'verify' : $form->data['vname'];
                $verify = \es_session::get($vname);
                if($type == 16 && empty($verify)){
                    //do nothing
                }else{
                    if (empty($captcha)) {
                        $errno = -9;
                        $errmsg = '图形验证码不能为空';
                        $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                        break;
                    }
                    if (md5($captcha) !== $verify) {
                        $errno = -10;
                        $errmsg = '图形验证码不正确';
                        $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                        break;
                    }
                }
            }
            //检测手机发送频率
            if($type == 16){
                //首先检测每秒
                $mobile = $form->data['mobile'];
                if (Block::check('CLIENT_REGISTER_USER_CODE_SECOND', $mobile,true)===false){
                    $errno = -11;
                    $errmsg = '发送频率超过分钟限制，请稍后再试';
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    break;
                }
            }

            /**
             * @abstract增加调用短信接口的频率限制
             * @author yutao
             * @date 2015-02-27
             */
            $isCheckIPFrequency = $this->isCheckIPFrequency();
            $mobile = $form->data['mobile'];
            if ($isCheckIPFrequency) {
                $ip = get_client_ip();
                $check_ip_minute_result = Block::check('SEND_SMS_IP_MINUTE', $ip, false);
                if ($check_ip_minute_result === false) {
                    $errno = -11;
                    $errmsg = '发送频率超过分钟限制，请稍后再试';
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    break;
                }
                $check_ip_day_result = Block::check('SEND_SMS_IP_TODAY', $ip, false);
                if ($check_ip_day_result === false) {
                    $errno = -12;
                    $errmsg = '发送频率超过当天限制，请稍后再试';
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    setLog(array('mcode_ip' => $ip));
                    break;
                }
            }

            $check_phone_hour_result = Block::check('SEND_SMS_PHONE_HOUR', $mobile, false);
            if ($check_phone_hour_result === false) {
                $errno = -13;
                $errmsg = '手机号码发送频率超过限制，请稍后再试';
                $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                break;
            }
            // 如果参数中传入了邀请码。则先对邀请码进行检验,H5页面逻辑中发送短信钱校验，可以避免短信误发。
            $invite = $form->data['invite'];
            if(isset($form->data['invite']) && !empty($invite)) {
                $coupon = CouponService::checkCoupon($invite);
                if($coupon === false || $coupon['coupon_disable']){
                    $errno = -14;
                    $errmsg = $GLOBALS['lang']['COUPON_DISABLE'];
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    break;
                }
            }

            //密码检查
            //基本规则判断
            $len = isset($form->data['password']) ? strlen($form->data['password']) : 0;
            if ($len > 0) {
                $mobile = $form->data['mobile'];
                $password = $form->data['password'];
                $password = stripslashes($password);
                \FP::import("libs.common.dict");
                $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
                $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
                if ($base_rule_result){
                    $errno = -20;
                    $errmsg = $base_rule_result['errorMsg'];
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    break;
                }
                //黑名单判断,禁用密码判断
                $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
                if ($forbid_black_result) {
                    $errno = -21;
                    $errmsg = $forbid_black_result['errorMsg'];
                    $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
                    break;
                }
            }
            if($type != 16) {
                $mobileCodeObj = new MobileCodeService();
                $errno = $mobileCodeObj->isSend($form->data['mobile'], $type, $isCheckIPFrequency);
                if ($errno != 1) {
                    $err = $mobileCodeObj->getError($errno);
                    $errmsg = $err['message'];
                    // 哈哈财神 传入已经存在的网信用户
                    if (isset($_REQUEST['client_id']) && '6d03d1ab2ac33258fb1b5fcf' == $_REQUEST['client_id'] && false !== strpos($errmsg, "重复")) {
                        $err['message'] = "您的账户已激活，请直接验证。如有问题请拨打400-110-0025";
                    }
                    $rspnMsg = json_encode($err);
                    break;
                }
            }
        } while (false);

        // 通过的验证码只能给一个手机用
        $onceSessionName = sprintf("mcodeUseOnce_%s",$captcha);
        $usedMobile = $form->data['mobile'];
        // 获取特定session标识
        $verifyMobileOnce = \es_session::get($onceSessionName);
        // 如果为空，就是首次发送。
        if(empty($verifyMobileOnce) || empty($captcha)){
            // 成功的。
            \es_session::set($onceSessionName,$usedMobile);
            // 如果特定验session不为空。且手机号与特定session不一致，则认为是同一个验证码被耍，干掉
        }elseif($verifyMobileOnce != $usedMobile){
            $errno = -15;
            $errmsg = '图片验证码过期，请重试';
            $rspnMsg = json_encode(array('code' => $errno, 'message' => $errmsg));
        }

        Logger::debug($debugMsg . "|" . json_encode($rspnMsg));
        setLog(array('errno' => $errno, 'errmsg' => $errmsg));
        if ($rspnMsg !== false) {
            echo $rspnMsg;
            return;
        }

        $isrsms = empty($form->data['isrsms']) ? false : true;

        $s_type = $type;
        if (!empty($_POST['sms_type']) && $_POST['sms_type'] == 1) {
            $s_type = 4;
        }
        $country_code = empty($_POST['country_code']) ? 'cn' : trim($_POST['country_code']);

        $idno = null;
        if ($s_type == 2) {
            $idno = $form->data['idno'];
        }
        $mobileCodeObj = new MobileCodeService();
        return $mobileCodeObj->sendVerifyCode($form->data['mobile'], 1, $isrsms, $s_type, $country_code, $idno);
    }

    /**
     * 验证表单令牌
     * 为了重新发送不调用app中的check_token方法
     * @author yutao
     * @param string $token_id
     * @return number 返回1为通过，0为失败
     */
    private function check_token($token_id = '') {
        $_REQUEST['token_id'] =  isset($_REQUEST['token_id']) ? $_REQUEST['token_id'] : false;
        $token_id = empty($token_id) ? $_REQUEST['token_id'] : $token_id;
        $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : false;
        if (empty($token_id) || empty($token)) {
            return 0;
        }
        $k = 'ql_token_' . $token_id;
        if (isset($_SESSION[$k]) && $token == $_SESSION[$k]) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 是否检查IP频率限制
     * @return boolean [description]
     */
    public function isCheckIPFrequency ()
    {
        if (empty($this->client_id)) return true;

        $conf = app_conf('REGISTER_CHECK_IP_FREQUENCY');
        if (empty($conf)) return true;

        $confs = explode('|', $conf);
        foreach ($confs as $line) {
            list(,$clientID, $startTime, $endTime) = explode('::', $line);

            if ($this->client_id === trim($clientID)) {
                // check time format
                $timeFormat = '#\d{4}/\d{2}/\d{2} \d{2}:\d{2}#';
                if (!preg_match($timeFormat, $startTime)) return true;
                if (!preg_match($timeFormat, $endTime)) return true;

                $noCheckMaxTime = intval(app_conf('REGISTER_NOCHECK_FREQUENCY_MAX_TIME')); // hour
                if ($noCheckMaxTime == 0) $noCheckMaxTime = 48;

                $startTime = strtotime($startTime);
                $endTime = strtotime($endTime);
                $now = time();
                $noCheckMaxTime *= 3600;

                $endTime = min($endTime, $startTime + $noCheckMaxTime);
                if ($now >= $startTime && $now <= $endTime) return false;
                return true;
            }
        }

        return true;
    }

    public function _after_invoke() {
        if ($this->checkAllow()) {
            echo  ');';
        }
    }

    private function forbidOtherRefer() {
        $allowReferReg = app_conf('MCODE_ALLOW_REGEX');
        if (empty($allowReferReg)) {
            return true;
        }

        return !preg_match($allowReferReg, $_SERVER['HTTP_REFERER']);
    }

    private function checkAllow() {
        return isset($_GET['jsonpCallback']) && preg_match('~wangxinlicai(local)?|firstp2p(local)?|ncfwx(local)?\.com~', $_SERVER['HTTP_REFERER']);
    }

}
