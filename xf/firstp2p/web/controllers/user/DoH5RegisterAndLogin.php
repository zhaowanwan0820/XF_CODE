<?php
/**
 * @author yutao
 * @abstract H5版本的注册
 * @date 2015-03-30
 * ----------------------------------------------------------------------------------------
 * @修改人:  刘振鹏
 * @修改时间:2015-07-29
 * @修改内容:
 *  1、新加函数getAuthorizeCode,用于获取oauth的code值
 *  2、新增通过RPC调用王世杰的活动结果
 * @修改背景:
 *  新手礼包注册流程优化
 * @产品:高田
 * ----------------------------------------------------------------------------------------
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use core\service\LogRegLoginService;
use core\service\bonus;
use libs\utils\Logger;
use core\service\bonus\Event;
use core\service\BonusService;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Monitor;

require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";

class DoH5RegisterAndLogin extends BaseAction {

    private $_errorCode = 0;
    private $_errorMsg = null;

    public function init() {
	    $loginResult = array("errorCode" => 0, "errorMsg" => '');
        $this->form = new Form('post');
        $this->form->rules = array(
            'password' => array('filter' => 'length', 'message' => '请输入6-20个字符', "option" => array("min" => 6, "max" => 20)),
            'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字", "option" => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}$/")),
            'captcha' => array('filter' => 'string'),
            'code' => array('filter' => 'string'),
            'cn' => array('filter' => 'string'),
            'event_id' => array('filter' => 'string'),
            'event_data' => array('filter' => 'string'),
            'oapi_uri' => array('filter' => 'string'),
            'oapi_sign' => array('filter' => 'string'),
            'from_platform' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $loginResult['errorCode'] = -1;
            $loginResult['errorMsg'] = $this->form->getErrorMsg();
            echo json_encode($loginResult);
            return false;
        }
    }

    public function invoke() {
		$loginResult = array("errorCode" => 0, "errorMsg" => '');
        $this->form->data['cn'] = $this->getCN();
        $aliasInfo = $this->rpc->local('CouponService\getShortAliasFormMobilOrAlias', array(trim($this->form->data['cn'])));
        $this->form->data['cn'] = $aliasInfo['alias'];
        $cEuid = \es_cookie::get('euid');
        $this->form->data['euid'] = !empty($cEuid) ? $cEuid : $_GET['euid'] ;

        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER,Risk::PF_WEB,DeviceEnum::DEVICE_WAP)->check($this->form->data,Risk::SYNC);
        //风控，设备命中黑名单
        if ($ret === false) {
            $loginResult['errorCode'] = -1;
            $loginResult['errorMsg']  = "注册异常";
            setLog($loginResult);
            Monitor::add('REGISTER_FAIL');
            die(json_encode($loginResult));
        }

        //校验验证码
        if (empty($this->form->data['captcha'])) {
            $loginResult['errorCode'] = -2;
            $loginResult['errorMsg']  = "验证码不能为空";
            setLog($loginResult);
            Monitor::add('REGISTER_FAIL');
            die(json_encode($loginResult));
        }

        //校验client_id
        if(!isset($_REQUEST['client_id']) || !isset($_REQUEST['response_type'])){
            $loginResult['errorCode'] = -3;
            $loginResult['errorMsg'] = "client_id和response_type必须设置";
            setLog($loginResult);
            Monitor::add('REGISTER_FAIL');
            die(json_encode($loginResult));
        }

        //校验response_type
        if($_REQUEST['response_type'] !== 'code'){
            $loginResult['errorCode'] = -4;
            $loginResult['errorMsg'] = "获取oauth的类型不正确";
            setLog($loginResult);
            Monitor::add('REGISTER_FAIL');
            die(json_encode($loginResult));
        }

        // 是否开启验证码效验
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($this->form->data['mobile']));
            if ($vcode != $this->form->data['code']) {
                $loginResult['errorCode'] = -5;
                $loginResult['errorMsg'] = "验证码错误";
                setLog($loginResult);
                echo json_encode($loginResult);
                Monitor::add('REGISTER_FAIL');
                return false;
            }
        }

        //add by shijie //增加IP限制
        $check_ip_minute_result = intval(\SiteApp::init()->cache->get('cash_bonus_user_register_'.get_client_ip()));
        if ($check_ip_minute_result > intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_TIMES'))) {
            $loginResult['errorCode'] = -6;
            $loginResult['errorMsg'] = '您提交注册的频率太快，请休息'.intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_TIMES')).'分钟后再试。';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($loginResult);
            Monitor::add('REGISTER_FAIL');
            return;
        }

        //密码检查
        //基本规则判断
        $len = strlen($this->form->data['password']);
        $mobile = $this->form->data['mobile'];
        $password = $this->form->data['password'];
        $password = stripslashes($password);
        \FP::import("libs.common.dict");
        $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
        $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
        if ($base_rule_result){
            $loginResult['errorCode'] = -9;
            $loginResult['errorMsg'] = $base_rule_result['errorMsg'];
            echo json_encode($loginResult);
            Monitor::add('REGISTER_FAIL');
            return;
        }
        //黑名单判断,禁用密码判断
        $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
        if ($forbid_black_result) {
            $loginResult['errorCode'] = -10;
            $loginResult['errorMsg'] = $forbid_black_result['errorMsg'];
            echo json_encode($loginResult);
            Monitor::add('REGISTER_FAIL');
            return;
        }

        //add by shijie end //增加
        $bo = BOFactory::instance('web');
        $this->form->data['mobile']   = trim($this->form->data['mobile']);
        $userInfo = array('password' => $this->form->data['password'], 'mobile' => $this->form->data['mobile']);

        $turn_on_invite = app_conf('TURN_ON_INVITE');
        if ($turn_on_invite == '1') {
            $coupon = $this->rpc->local('CouponService\checkCoupon', array($this->form->data['cn']));
            if ($coupon !== FALSE) {
                $userInfo['invite_code'] = $this->form->data['cn'];
                $userInfo['refer_user_id'] = $coupon['refer_user_id'];
            } else if (!empty($this->form->data['invite'])) {

                $log = array(
                    'type' => 'invite_code_error',
                    'host' => $_SERVER['HTTP_HOST'],
                    'code' => $this->form->data['cn'],
                    'path' => __FILE__,
                    'function' => 'doRegister',
                    'time' => time(),
                );
                $destination = APP_ROOT_PATH . "log/logger/invite_code_error-" . date('y_m') . ".log";
                Logger::wLog(var_export($log, TRUE), Logger::INFO, Logger::FILE, $destination);
            }
        }

        //$userInfo['referer'] = $this->form->data['from_platform']; //记录来源H5端
        $userInfo['referer'] = DeviceEnum::DEVICE_WAP;
        $upResult = $bo->insertInfo($userInfo, true);
        if ($upResult['status'] < 0) {
            $loginResult['errorCode'] = -7;
            $loginResult['errorMsg'] = current($upResult['data']);
        } else {
            $ip = get_client_ip();
            $check_ip_minute_result = intval(\SiteApp::init()->cache->get('cash_bonus_user_register_'.$ip)) + 1;
            \SiteApp::init()->cache->set('cash_bonus_user_register_'.$ip,$check_ip_minute_result,intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_MINITES'))*60);

            //注册成功
            $logRegLoginService = new LogRegLoginService();
            //注册track_id
            $track_id = \es_session::get('track_id');
            if(empty($track_id)){
                $track_id = hexdec(\libs\utils\Logger::getLogId());
                \es_session::set('track_id', $track_id);
            }
            //euid如果非空，则进行track_on
            $isEuid = false;
            $cEuid = \es_cookie::get('euid');
            if(!empty($_GET['euid']) || !empty($cEuid)){
                $isEuid = true;
            }
            if($isEuid || in_array(strtoupper($this->form->data['cn']), get_adunion_order_coupon())) {
                \es_session::set('track_on', 1);
                \es_session::set('ad_invite_code', $this->form->data['cn']);
            }

            //$this->rpc->local('AdunionDealService\triggerAdRecord', array($upResult['user_id'], 1)); //广告联盟
            $userInfo = array('user_name' => $userInfo['mobile'], 'password' => $userInfo['password']);
            $ret = $bo->doLogin($userInfo, '');
            if ($ret['code'] == 0) {
                $event_data = array();
                if($this->form->data['event_id']){
                    $eventObj   = new Event();
                    $event_data = $eventObj->trigger($upResult['user_id'], $userInfo['user_name'], $this->form->data['event_id'], $_REQUEST['event_data'], $this->form->data['cn']);
                }
                //oauth接口调用
                $oauth_code = $this->getAuthorizeCode();
                $loginResult['errorCode'] = 0;
                $loginResult['errorMsg']  = '';
                $loginResult['data']      = array('oauth_code' => $oauth_code, 'event_data' => $event_data);

                /*开放平台——聚财项目*/
                if($this->form->data['oapi_sign'] == 'moss' && !empty($this->form->data['oapi_uri'])){
                    $loginResult['data']['oapi_uri'] = $this->form->data['oapi_uri'];
                    $loginResult['data']['oapi_status'] = 1;
                    $loginResult['data']['oapi_sign'] = 1;
                }

                $this->rpc->local('RegisterService\afterRegister', array());
                RiskServiceFactory::instance(Risk::BC_REGISTER)->notify(array('userId'=>$upResult['user_id']), $this->form->data);
            } else {
                $loginResult['errorCode'] = -8;
                $loginResult['errorMsg'] = '登录失败，请重新登录';
            }
        }
        setLog($loginResult);
        echo json_encode($loginResult);
    }

    /**
     * @获取oauth的code值
     * @param  void
     * @return string
     */
    private function getAuthorizeCode()
    {
        $oauth = new \PDOOAuth2();

        $params = $oauth->getAuthorizeParams();
        $res = $oauth->finishClientAuthorization(true, $params, false);
        if(empty($res)) return false;

        return $res['query']['code'];
    }

    /**
     * 获取邀请码
     * @return [type] [description]
     */
    private function getCN()
    {
        if (!empty($this->form->data['cn']))
            return $this->form->data['cn'];
        return BonusService::getReferCN($this->form->data['mobile']);
    }
}
