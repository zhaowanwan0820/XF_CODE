<?php

/**
 * 短信验证码验证登陆
 *
 */

namespace openapi\controllers\user;

use core\service\MsgConfigService;
use openapi\controllers\BaseAction;
use libs\web\Form;
use core\service\user\BOFactory;
use core\service\LogRegLoginService;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Block;
use libs\utils\Monitor;
use libs\lock\LockFactory;
use core\service\BonusService;
use core\service\bonus\Event;
use core\service\bonus;
use libs\utils\Logger;

require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";


class SmDoH5Login extends BaseAction
{
    private $_error = null;
    private $_flag = false;
    private $_isH5 = true;


    //协议弹窗分站clientId
    private static $_clientID = array(
            '房贷' => 'bb469276d5eb331f2cb7c451',
            '典当' => '24cf469b079e3b94ed5a71b9',
            '金融1号' => '4f853a4df204ffcd00924517',
            '艺金融' => '5610e2cd133cd29ecf8e32ee',
            '哈哈' => '6d03d1ab2ac33258fb1b5fcf',
            '荣信汇' => '8365f78859915a7db00e37c6',
            );

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
                'code'=> array('filter'=>'string'),
                'mobile'=>array('filter'=>'string'),
                'country_code' => array('filter' => 'string'),
                'agreement' => array('filter' => 'string'),
                //兼容登陆
                "verify" => array("filter" => "reg", "message" => '验证码错误', "option" => array("regexp" => "/^[0-9a-zA-Z]{4,10}$/", 'optional' => true)),
                "redirect_uri" => array("filter" => 'string'),
                "response_type" => array("filter" => 'string'),
                "scope" => array("filter" => 'string'),
                "state" => array("filter" => 'string'),
                "from_register" => array("filter" => 'string'),
                'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字", 'option' => array("regexp" => "/^1[3456789]\d{9}$/", 'optional' => true)),
                //	"account" => array("filter" => [$this, "getAccount"], "message" => '用户名不能为空'),
                //兼容注册
                'captcha' => array('filter' => 'string'),
                'event_id' => array('filter' => 'string'),
                'event_data' => array('filter' => 'string'),
                'oapi_uri' => array('filter' => 'string'),
                'oapi_sign' => array('filter' => 'string'),
                'smLoginToken' => array('filter' => 'string'),
                );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        $this->form->validate();
        if (!$this->form->validate()) {
            $loginResult['errorCode'] = -1;
            $loginResult['errorMsg'] = $this->form->getErrorMsg();
            echo json_encode($loginResult);
            exit;
        }
    }
    //原来dologin  register不需要检验
    public function authCheck() {
        return true;
    }

    public function invoke()
    {
	$this->form->data['account'] = $data['mobile'];
        $data = $this->form->data;
        $user_info['mobile']=$data['mobile'];

        //检测来源是否调用WebDoLogin进行校验后，调用此接口
        $smLoginToken = \es_session::get('smLoginToken');
        if( empty($smLoginToken) || $data['smLoginToken'] != $smLoginToken ){
            $loginResult['errorCode'] = -1;
            $loginResult['errorMsg'] = "请求超时，请刷新页面";
            echo json_encode($loginResult);
            exit;
        }

        //检测用户输入验证码错误信息
        $ip = get_client_ip();
        $check_ip_minute_result = Block::check('SM_LOGIN_CODE_VERIFY_RV_CN_IP', $ip,false);
        $check_phone_minute_result = Block::check('SM_LOGIN_CODE_VERIFY_RV_CN_PHONE', $data['mobile'],false);
        if($check_ip_minute_result === false || $check_phone_minute_result == false) {
            $loginResult['errorCode'] = -1;
            $loginResult['errorMsg'] = "短信验证错误次数太多，请稍后再试";
            echo json_encode($loginResult);
            exit;
        }

        //短信验证码校验
        if ($data['code']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($user_info['mobile']));
            if (empty($vcode) || $vcode != $data['code']) {
                setLog(array('restrict_vcode_verify'=>0));
                echo json_encode(array(
                            'errorCode' => 1,
                            'errorMsg' => '短信校验错误',
                            ));
                exit;
            } else {
                $this->rpc->local('MobileCodeService\delMobileCode', array($data['mobile']));//删除短信验证码
                //判断用户是否注册过，是：登陆逻辑，否：注册逻辑
                $country_code = $data['country_code'];
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$data['country_code']]['code'];
                $data['mobile_code'] = $mobile_code;
                $userInfo = $this->getUserInfo($data);
                if (empty($userInfo)) {
                    //未注册逻辑
                    $this->doH5Register($data);
		    return;
                }
                //登陆逻辑
                $this->doH5Login($userInfo, $data);
                return;
            }
        }

    }


    //用户信息
    private function getUserInfo($data){
        $country_code = $data['country_code'];
        $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$data['country_code']]['code'];
        $condition = "mobile = '".$data['mobile']."'";
        if ($country_code && $mobile_code) {
            $condition = $condition." and country_code = '".$country_code."'";
        }
        $user_data = $this->rpc->local('UserService\getUserByCondition', array($condition));
        if(!empty($user_data)){
            $userInfo = $user_data->getRow();
            return $userInfo;
        }
        return NULL;
    }

    //注册流程
    private function doH5Register($data){
        $loginResult = array("errorCode" => 0, "errorMsg" => '');
        $this->form->data['cn'] = $this->getCN();
        //邀请码
        $aliasInfo = $this->rpc->local('CouponService\getShortAliasFormMobilOrAlias', array(trim($this->form->data['cn'])));
        $this->form->data['cn'] = $aliasInfo['alias'];
        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER,Risk::PF_WEB,DeviceEnum::DEVICE_WAP)->check($this->form->data,Risk::SYNC);
        //设备命中风控黑名单
        if ($ret === false) {
            $loginResult['errorCode'] = -1;
            $loginResult['errorMsg']  = "注册异常";
            setLog($loginResult);
            Monitor::add('REGISTER_FAIL');
            die(json_encode($loginResult));
        }

        //校验验证码,需要判断是否携带验证码
        $verify = \es_session::get('verify');
        if(!empty($verify)){
            \es_session::set('verify', 'xxx removeVerify xxx');
            $captcha = $data['captcha'];
            if (md5($captcha) !== $verify) {
                $loginResult['errorCode'] = -2;
                $loginResult['errorMsg']  = "图形验证码错误";
                setLog($loginResult);
                Monitor::add('REGISTER_FAIL');
                die(json_encode($loginResult));
            }
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

        //add by shijie //增加IP限制
        $check_ip_minute_result = intval(\SiteApp::init()->cache->get('cash_bonus_user_register_'.get_client_ip()));
        if ($check_ip_minute_result > intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_TIMES'))) {
            $loginResult['errorCode'] = -6;
            $loginResult['errorMsg'] = '您提交注册的频率太快，请休息'.intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_TIMES')).'分钟后再试。';
            header('Content-Type: application/json; charset=utf-8');			     echo json_encode($loginResult);
            Monitor::add('REGISTER_FAIL');
            exit;
        }

        //查询分站信息
        $siteId = 1; //默认是主站进行注册
        $appInfo = $this->loadOpenData();
        if(!empty($appInfo)){
            $siteId = $appInfo['siteId'];
        }
        //注册随机密码
        $password  = substr(md5($mobile . mt_rand(1000000, 9999999)), 0, 10);
        $userInfo  = array('mobile' => $data['mobile'], 'site_id' => $siteId, 'password' => $password, 'country_code' => $data['country_code'], 'mobile_code'=>$data['mobile_code'], 'email' => '', 'username'=>'');
        $setParams = (array) json_decode($appInfo['setParams'], true);
        if (!empty($setParams['GroupId'])) {
            $userInfo['group_id'] = intval($setParams['GroupId']);
        }
        if (!empty($setParams['CouponLevelId'])) {
            $userInfo['coupon_level_id'] = intval($setParams['CouponLevelId']);
        }
        //如果相应的分站用户注册时，在用户表里没有正确记录site_id，可以手动在请求连接中加上相对应的site_id，不是必传参数
        if ($_REQUEST['site_id'] && ($site_key=array_search($_REQUEST['site_id'], $GLOBALS['sys_config']['TEMPLATE_LIST']))) {
            $userInfo['group_id'] = $GLOBALS['sys_config']['SITE_USER_GROUP'][$site_key];
            $userInfo['site_id']=$_REQUEST['site_id'];
        }

       $userInfo['referer'] = DeviceEnum::DEVICE_WAP;
        $turn_on_invite = app_conf('TURN_ON_INVITE');
        //邀请码校验
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
        if (!empty($appInfo['inviteCode'])) {
            $userInfo['invite_code'] = $appInfo['inviteCode'];
        }

        //doRegister
        $bo = BOFactory::instance('web');
        //复用car_year,代表随机生成密码 9999
        $userInfo['car_year'] = 9999;
        $upResult = $bo->insertInfo($userInfo, true);
        if ($upResult['status'] < 0) {
            //错误处理
            $loginResult['errorCode'] = -7;
            $loginResult['errorMsg'] = current($upResult['data']);
        }else{
            $ip = get_client_ip();
            $check_ip_minute_result = intval(\SiteApp::init()->cache->get('cash_bonus_user_register_'.$ip)) + 1;
            \SiteApp::init()->cache->set('cash_bonus_user_register_'.$ip,$check_ip_minute_result,intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_MINITES'))*60);
            Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WAP', 'SM_LOGIN_REGISTER_SUCCESS',json_encode($this->form->data))));	
            //注册成功
            $logRegLoginService = new LogRegLoginService();
            //注册track_id
            $track_id = \es_session::get('track_id');
            if(empty($track_id)){
                $track_id = hexdec(\libs\utils\Logger::getLogId());
                \es_session::set('track_id', $track_id);
            }
            if(in_array(strtoupper($this->form->data['cn']), get_adunion_order_coupon())) {
                \es_session::set('track_on', 1);
                \es_session::set('ad_invite_code', $this->form->data['cn']);
            }
            //$this->rpc->local('AdunionDealService\triggerAdRecord', array($upResult['user_id'], 1)); //广告联盟

            $userInfo = array('user_name' => $userInfo['mobile'], 'password' => $password);
            $ret = $bo->doLogin($userInfo, '');
            if ($ret['code'] == 0) {
                //登录成功
                $event_data = array();
                if($this->form->data['event_id']){
                    $eventObj   = new Event();
                    $event_data = $eventObj->trigger($upResult['user_id'], $userInfo['user_name'], $this->form->data['event_id'], $_REQUEST['event_data'], $this->form->data['cn']);
                }
                //oauth接口调用
                $oauth_code = $this->getAuthorizeCode();
                $loginResult['errorCode'] = 0;
                $loginResult['errorMsg']  = '';
                $loginResult['data']      = array('oauth_code' => $oauth_code, 'event_data' =>$event_data);
                /*开放平台——聚财项目*/
                if($this->form->data['oapi_sign'] == 'moss' && !empty($this->form->data['oapi_uri'])){
                    $loginResult['data']['oapi_uri'] = $this->form->data['oapi_uri'];
                    $loginResult['data']['oapi_status'] = 1;
                    $loginResult['data']['oapi_sign'] = 1;
                }
                //$this->rpc->local('RegisterService\afterRegister', array());
                RiskServiceFactory::instance(Risk::BC_REGISTER)->notify(array('userId'=>$upResult['user_id']), $this->form->data);
            }else{
                $loginResult['errorCode'] = -8;
                $loginResult['errorMsg'] = '登录失败，请重新登录';
            }
        }
        setLog($loginResult);
        echo json_encode($loginResult);
        exit;
    }

    //wap登录
    private function getAuthorizeCode()
    {
        $oauth = new \PDOOAuth2();
        //$wapClientId = 'db6c30dddd42e4343c82713e'; //wap主站client_id
        $wapClientId = $_REQUEST['client_id']; 
        $params = array('client_id' => $wapClientId, 'response_type' => 'code');
        $res = $oauth->finishClientAuthorization(true, $params, false);
        if(empty($res)){
            return false;
        }
        return $res['query']['code'];
    }
    /**
     * oauth2 认证，返回code码
     */
    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    //登录流程，需要细化
    private function doH5Login($userInfo, $data){
        RiskServiceFactory::instance(Risk::BC_LOGIN,Risk::PF_OPEN_API,$this->device)->check($data,Risk::SYNC);
        //设置登陆信息
        $GLOBALS['user_info'] = $userInfo;
        \es_session::set('user_info', $userInfo);
        //记录短信验证登陆日志
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WAP','SM_LOGIN_LOGIN_SUCCESS',json_encode($this->form->data))));
        $logRegLoginService = new LogRegLoginService();
        $clientIds = self::$_clientID;
        $clientExist = array_search($data['client_id'], $clientIds);
        if ($_REQUEST['isTransferProtocol'] && $clientExist) {
            $isTransferProtocol = \es_session::get('DoLoginIsTransferProtocolToken');
            $user = \es_session::get('user_info');
            if ($isTransferProtocol == $_REQUEST['isTransferProtocol'] && array_search($data['account'],$user)) {
                $ret = $this->rpc->local('UserService\updateUserToJXSD', array($user['id']));
                if($ret) {
                    RiskServiceFactory::instance(Risk::BC_LOGIN,Risk::PF_OPEN_API)->notify(array('userId'=>$user['id']));
                    $logRegLoginService->insert($this->form->data['account'], $user['id'], 0, 1, 2);
                    \es_session::delete('DoLoginIsTransferProtocolToken');
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'uid:|'.$user['id'].'|协议迁移成功')));
                    //修改为ajax返回接口
                    //$this->authorize();
                    //return true;
                    $this->getCodeAndExit();

                }else{
                    $logRegLoginService->insert($this->form->data['account'], '',0, 2, 2);	
                    $this->_error = '确认迁移失败，请稍后重试';
                    setLog(
                            array(
                                'errmsg' => $this->_error,
                                'uid' => $user['id'],
                                )
                          );
                    //需要修改为ajax返回接口
                    $this->showLoginError();
                }
            }else{
                $logRegLoginService->insert($this->form->data['account'], '', 0, 2, 2);
                setLog(array('errmsg' => '表单重复提交','uid' => $user['id'],));
                $this->_error = '表单重复提交';
                //需要修改为ajax返回接口
                $this->showLoginError();
                return;
            }
        }
        //*****************************************************
        // 先检查是否需要校验验证码
        $check_account_name_result = Block::check('WEB_LOGIN_USERNAME', $this->form->data['account'], true);
        $ip = get_client_ip();
        $check_client_ip_result = Block::check('WEB_LOGIN_IP', $ip, true);

        $verify = \es_session::get('verify');
        if ($check_account_name_result === false || $check_client_ip_result === false || !empty($verify)) {
            \es_session::set('verify', 'xxx removeVerify xxx');
            // 校验验证码
            $captcha = $this->form->data['captcha'];
            //验证码
            if (md5($captcha) !== $verify) {
                $logRegLoginService->insert($this->form->data['account'], '', 0, 2, 1);
                $this->_error = '您输入的验证码错误';
                Monitor::add('LOGIN_FAIL');
                return $this->showLoginError();
            }
        }
        //分站弹窗协议处理
        if((int)app_conf('USER_JXSD_TRANSFER_SWITCH') !== 1) {
            $userInfo['is_dflh'] = 0;
        } else {
            $userInfo['is_dflh'] = intval($userInfo['is_dflh']);
        }
        if ($clientExist && $userInfo['is_dflh'] == 1) {
            $isTransferProtocol = md5('DoLoginIsTransferProtocolToken'.time());
            \es_session::set('DoLoginIsTransferProtocolToken', $isTransferProtocol);
            //待细化处理
        }		
        RiskServiceFactory::instance(Risk::BC_LOGIN,Risk::PF_OPEN_API)->notify(array('userId'=>$user['id']));
        $logRegLoginService->insert($this->form->data['account'], $userLoginResponse->userId, 0, 1, 2);
        /*
           $this->authorize();
           return true;
         */
        $this->getCodeAndExit();

    }

    private function  getCodeAndExit(){
        $oauth_code = $this->getAuthorizeCode();
        $loginResult['errorCode'] = 0;
        $loginResult['errorMsg']  = '';
        $loginResult['data']      = array('oauth_code' => $oauth_code, 'event_data' =>$event_data);
        echo json_encode($loginResult);
        exit;
    }

    //获取邀请码
    private function getCN()
    {
        if (!empty($this->form->data['cn']))
            return $this->form->data['cn'];
        return BonusService::getReferCN($this->form->data['mobile']);
    }
    //错误信息
    private function showLoginError() {
        $loginResult['errorCode'] = 1;
        $loginResult['errorMsg']  = $this->_error;
        $loginResult['data']      = '';
        echo json_encode($loginResult);
        exit;
    }


}
