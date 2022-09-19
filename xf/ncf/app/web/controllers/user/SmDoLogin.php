<?php

/**
 * 短信验证码验证登陆
 *
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use core\Enum\DeviceEnum;
use core\service\user\BOFactory;
use core\service\LogRegLoginService;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Monitor;
use libs\lock\LockFactory;
use libs\utils\Logger;
use libs\utils\Block;

require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";

class SmDoLogin extends BaseAction
{
    private $_error = null;
    private $_flag = false;
    private $_isH5 = false;

    //兼容注册
    private $_isHaveCountyCode = false;
    private $_auth_clients = array(
        '6d03d1ab2ac33258fb1b5fcf',
        'd3e9e24156be0f5b8e1100ac',
        'bb469276d5eb331f2cb7c451',
        '4f853a4df204ffcd00924517',
        '8365f78859915a7db00e37c6',
        'e01e4e865e87f57999f14fce',
        '3b5883c1f384f73007a0cb0c',
    );

    public function init()
    {
        $this->form = new Form("post");
        $this->form->rules = array(
                'code'=> array('filter'=>'string'),
                //'mobile'=>array('filter'=>'string'),
                'country_code' => array('filter' => 'string'),
                'agreement' => array('filter' => 'string'),
                //兼容登录
                'captcha' => array('filter' => 'string'),
                'csessionid' => array('filter' => 'string'),
                'sig' => array('filter' => 'string'),
                'risk_token' => array('filter' => 'string'),

                //兼容注册
                'email' => array('filter' => 'email', 'option' => array("optional" => true)),
                'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字","option" => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}$/")),
                'invite' => array('filter' => 'string'),
                'from_platform' => array('filter' => 'string'),
                'type' => array('filter' => 'string'),
                'src' => array('filter' => 'string'),
                'isAjax' => array('filter' => 'int'),
                'site_id' => array("filter" => 'string', "option" => array("optional"=>true)),
                'event_cn_hidden' => array('filter' => 'int'),
                'smLoginToken' => array('filter' => 'string'),
                );
        if (!empty($_REQUEST['country_code']) && isset($GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]) && $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['is_show']){
            $this->form->rules['mobile'] =  array('filter' => 'reg', "message" => "手机格式错误",
                    "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['regex']}/"));
            $this->_isHaveCountyCode = true;
        }
        if (! $this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user_info['mobile']=$data['mobile'];

        //为防止跳过login，直接登录，设置短信码校验
        $tpl_query = array();
        !empty($_GET['tpl']) ? $tpl_query['tpl'] = trim($_GET['tpl']) : null;
        if(!empty($_GET['backurl'])){
            if(isMainDomain($_GET['backurl'])){
                $tpl_query['backurl'] = urldecode(trim($_GET['backurl']));
            }else{
                //$_GET['backurl'] = null;
            }
        }
        $tpl_str = empty($tpl_query) ? '' : '?'.http_build_query($tpl_query);
        //检测来源是否调用WebDoLogin进行校验后，调用此接口
        $smLoginToken = \es_session::get('smLoginToken');
        if( empty($smLoginToken) || $data['smLoginToken'] != $smLoginToken ){
            Monitor::add('LOGIN_FAIL');
            app_redirect(url('user/login' . $tpl_str));
            exit;
        }
        //检测用户输入验证码错误信息
        $ip = get_client_ip();
        $check_ip_minute_result = Block::check('SM_LOGIN_CODE_VERIFY_RV_CN_IP_LAST', $ip,false);
        $check_phone_minute_result = Block::check('SM_LOGIN_CODE_VERIFY_RV_CN_PHONE_LAST', $data['mobile'],false);
        if($check_ip_minute_result === false || $check_phone_minute_result ==false) {
            Monitor::add('LOGIN_FAIL');
            app_redirect(url('user/login' . $tpl_str));
            exit;
        }

        //短信验证码校验
        if ($data['code']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($user_info['mobile']));
            if (empty($vcode) || $vcode != $data['code']) {
                //校验失败
                Monitor::add('LOGIN_FAIL');
                app_redirect(url('user/login' . $tpl_str));
                exit;
            } else {
                $this->rpc->local('MobileCodeService\delMobileCode', array($data['mobile']));//删除短信验证码
                // 验证表单令牌
                if (!check_token()) {
                    Monitor::add('LOGIN_FAIL');
                    app_redirect(url('user/login'.$tpl_str));
                    exit;
                }

                // 先检查是否需要校验验证码
                $verify = \es_session::get('verify');
                $needVerify = true;
                // 先检查是否需要校验验证码
                $loginVerifyWhiteList = \dict::get("LOGIN_VERIFY_WHITELIST");
                if(!app_conf('VERIFY_SWITCH')||(!empty($loginVerifyWhiteList)&&in_array(get_real_ip(), $loginVerifyWhiteList))){
                    $needVerify = false;
                }

                if ($needVerify && !empty($verify)) { //图形验证码存在则验证图形验证码
                    // 验证码校验失败，立刻将session中verify设置成非MD5值
                    \es_session::set('verify','xxx removeVerify xxx');
                    $captcha = $this->form->data['captcha'];
                    if (md5($captcha) !== $verify) {
                        if ($_GET['client_id'] || $_GET['backurl']) {
                            $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
                        }
                        $this->_error = '您输入的验证码错误 ';
                        $this->tpl->assign("show_vcode", '1');
                        Monitor::add('LOGIN_FAIL');
                        return $this->showLoginError();
                    }
                }
                //判断用户是否注册过，是：登陆逻辑，否：注册逻辑
                $country_code = $data['country_code'];
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$data['country_code']]['code'];
                $data['mobile_code'] = $mobile_code;
                $userInfo = $this->getUserInfo($data);
                //setLog(array('uid'=>$userinfo['id']));
                if (empty($userInfo)) {
                    //未注册逻辑
                    $this->doRegister($data);
                    return;
                }
                //登陆逻辑
                $this->doLogin($userInfo, $data);
                return;
            }
        }else{
            //无手机验证码
            Monitor::add('LOGIN_FAIL');
            return app_redirect(url('user/login' . $tpl_str));
        }//end code

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


    //登录流程，需要细化，模仿user/doLogin
    private function doLogin($userInfo, $data){
        $this->form->data['username'] = $this->form->data['mobile'];
        RiskServiceFactory::instance(Risk::BC_LOGIN)->check($this->form->data,Risk::SYNC);
        //设置登陆信息
        $GLOBALS['user_info'] = $userInfo;
        \es_session::set("user_info", $userInfo);
        //记录短信验证登陆日志
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'WEB','SM_LOGIN_LOGIN_SUCCESS',json_encode($this->form->data))));
        //logging
        $logRegLoginService = new LogRegLoginService();
        $logRegLoginService->insert($userInfo['user_name'], $userInfo['user_id'], 0, 1, 1);
        RiskServiceFactory::instance(Risk::BC_LOGIN)->notify(array('userId'=>$userInfo['user_id']));
        // 回跳url
        $jumpUrl = get_login_gopreview();
        //设置回调的url
        if ($_GET['backurl']) {
            if (substr(urldecode($_GET['backurl']), 0, 7) == 'http://' || substr(urldecode($_GET['backurl']), 0, 8) == 'https://') {
                header("Location: ". urldecode($_GET['backurl']));
                exit;
            }
            app_redirect(url(trim($_GET['backurl'], '/')));
            exit;
        }
        if ($GLOBALS ['user_info'] ['force_new_passwd']) {
            app_redirect(url('user/editpwd'));
            exit;
        }
        if (empty($jumpUrl)) {
            app_redirect($jumpUrl);
            exit;
        } else {
            header("location:/");
            exit;
        }
        return;
    }


    //注册流程
    private function doRegister($data){
        $this->form->data['username'] = $this->form->data['mobile'];
        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER,Risk::PF_WEB,$this->_isH5?DeviceEnum::DEVICE_WAP:DeviceEnum::DEVICE_WEB)->check($this->form->data,Risk::SYNC);
        if ($ret === false) {
            $this->_error = array('mobile'=>'注册异常');
            return $this->showRegisterError();
        }
        //查询分站信息
        $siteId = 1; //默认是主站进行注册
        if(!empty($this->appInfo)){
            $siteId = $this->appInfo['id'];
        }
        //注册随机密码
        $password  = substr(md5($mobile . mt_rand(1000000, 9999999)), 0, 10);
        $userInfo  = array('mobile' => $data['mobile'], 'site_id' => $siteId, 'password' => $password, 'country_code' => $data['country_code'], 'mobile_code'=>$data['mobile_code'], 'email' => '', 'username'=>'');
        $setParams = (array) json_decode($this->appInfo['setParams'], true);
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
        // 邀请码未填情况从红包中获取
        if (empty($this->form->data['invite'])){
            $this->form->data['invite'] = $this->rpc->local('BonusService\getReferCN', array(trim($this->form->data['mobile'])));
        }
        // add by wangfei5@ ,邀请码识别+转换
        $aliasInfo = $this->rpc->local('CouponService\getShortAliasFormMobilOrAlias', array(trim($this->form->data['invite'])));
        $this->form->data['invite'] = $aliasInfo['alias'];
        $appInfo =  $this->appInfo;
        if (!empty($appInfo['inviteCode'])) {
            $userInfo['invite_code'] = $appInfo['inviteCode'];
        }
        //default 1
        if (!$this->_isH5) {
            if ($this->form->data['agreement'] != '1') {
                //$this->_error = array('agreement' => '不同意注册协议无法完成注册');
                //Monitor::add('REGISTER_FAIL');
                //return $this->showRegisterError();
            }
        }
        $userInfo['referer'] = DeviceEnum::DEVICE_WEB; //记录来源PC端
        //doRegister
        $bo = BOFactory::instance('web');
        // 加锁，防用户注册重复提交
        $lockKey = "register-user-".$userInfo['mobile'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey)) {
            $this->_error = array('mobile'=>'该手机号已经注册，如有疑问请联系客服');
            return $this->showRegisterError();
        }
        //复用car_year,代表随机生成密码 9999
        $userInfo['car_year'] = 9999;
        $upResult = $bo->insertInfo($userInfo, $this->_isH5);
        $lock->releaseLock($lockKey);
        if ($upResult['status'] < 0) {
            //错误处理
            $this->_error = $upResult['data'];
            return $this->showRegisterError();
        }else{
            RiskServiceFactory::instance(Risk::BC_REGISTER)->notify(array('userId'=>$upResult['user_id']), $this->form->data);
            //记录短信验证注册日志
            Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, 'WEB', 'SM_LOGIN_REGISTER_SUCCESS',json_encode($this->form->data))));

            //register success
            // 增加日志注册成功时候uid
            $this->setLog("uid",$upResult['user_id']);
            $logRegLoginService = new LogRegLoginService();
            //$this->rpc->local('AdunionDealService\triggerAdRecord', array($upResult['user_id'],1)); //广告联盟
            if (!empty($_REQUEST['client_id'])) {
                $GLOBALS['user_info'] = array('id' => $upResult['user_id']);
                //h5 web站处理 直接回调到redirect_uri
                if ($_REQUEST['client_id'] == '7b9bd46617b3f47950687351' || $_REQUEST['client_id'] == 'db6c30dddd42e4343c82713e') {
                    $this->log();
                    header("Location: " . urldecode($_REQUEST['redirect_uri']));
                    return;
                } elseif (in_array($_REQUEST['client_id'], $this->_auth_clients)) { //对于注册后自动登录的case
                    $this->log();
                    $this->authorize();
                    return;
                }else{
                    if ($this->form->data['isAjax'] == 1) {
                        $redirect = PRE_HTTP . APP_HOST . '/user/login?' . $_SERVER['QUERY_STRING'];
                        echo json_encode(array(
                                    'errorCode' => 0,
                                    'errorMsg' => '',
                                    'redirect' => $redirect,
                                    'data' => ['downloadURL' => empty($downloadURL) ? $redirect : $downloadURL],));
                    }else{
                        $this->tpl->assign('jump_url', PRE_HTTP . APP_HOST . '/user/login?' .$_SERVER['QUERY_STRING']);
                        $this->tpl->display('web/views/user/success.html');
                    }
                }
                $logRegLoginService->insert($this->form->data['mobile'], $upResult['user_id'],1, 0, 1, $this->form->data['invite']);
                return;
            }

            $userInfo = array('user_name' => $userInfo['mobile'], 'password' => $password);
            $ret = $bo->doLogin($userInfo, '');
            if ($ret['code'] == 0) {
                //登录成功
                $logRegLoginService->insert($this->form->data['mobile'], $upResult['user_id'],1, $ret['code'] == 0 ? 1 : 0, 1, $this->form->data['invite']);
                $targetUrl = "/";
                /*
                if (!empty($this->form->data['type']) && $this->form->data['type'] == 'h5') {
                    $targetUrl = 'account/addbank';
                }elseif (!empty($this->form->data['type']) && $this->form->data['type'] == 'bdh5') {
                    if (isset($GLOBALS['h5Union'][$this->form->data['src']])) {
                        $h5UnionService = H5UnionService::getInstance($GLOBALS['h5Union'][$this->form->data['src']]);
                        $h5UnionService->addRedisCount();
                    }
                    $targetUrl = 'account';
                }else{
                    $targetUrl = 'account/addbank';
                }*/
                if (isset($_GET['from'])){
                    $targetUrl .='?from=reg';
                }
                // add by wangfei5@
                $this->rpc->local('RegisterService\afterRegister', array());
                if ($this->isModal()) {
                    setcookie('modal_login_succ', 1, 0, '/', get_root_domain());
                    $this->template = null;
                    return true;
                }

                if ($this->form->data['isAjax'] == 1) {
                    echo json_encode(array(
                                'errorCode' => 0,
                                'errorMsg' => '',
                                'redirect' => $targetUrl,
                                'data' => ['downloadURL' => empty($downloadURL) ? url($targetUrl) : $downloadURL],));
                }else {
                    app_redirect(url($targetUrl));
                    exit;
                }

            }else{
                //登录失败
                if ($this->form->data['isAjax'] == 1) {
                    echo json_encode(array(
                                'errorCode' => -1,
                                'errorMsg' => $ret['msg'],));
                }else{
                    return $this->show_error($ret['msg']);
                }
            }
        }
    }

    /**
     *
     * 显示错误提示页面
     * 根据$this->_error显示对应错误
     *
     * */
    private function showLoginError() {
        $this->tpl->assign("page_title", '登录');
        $this->tpl->assign("website", app_conf('APP_NAME'));
        $this->tpl->assign("error", $this->_error);
        $this->tpl->assign("data", $this->form->data);
        if (app_conf('TPL_LOGIN')) {
            $this->template = app_conf('TPL_LOGIN');
        } elseif (!empty($_GET['tpl'])) {
            $this->template = 'web/views/user/login_'.trim($_GET['tpl']).'.html';
        } else {
            $this->template = $this->getShowTemplate();
        }
        if(!empty($_REQUEST['modal'])){
            $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
        }
        $logRegLoginService = new LogRegLoginService();
        $logRegLoginService->insert($this->form->data['username'], '', 0, 2, 1);
    }

    /**
     * 显示注册页面错误提示信息
     * 根据$this->_error 显示错误信息到对应的位置
     * */
    private function showRegisterError() {
        $data = $this->form->data;
        $register = new \web\controllers\user\Register();
        $agreement = $register->getAgreementAddress(app_conf('APP_SITE'));

        $this->tpl->assign('invite_money', $register->getInviteMoney());
        $this->tpl->assign("page_title", '注册');
        $this->tpl->assign("website", app_conf('APP_NAME'));
        $this->tpl->assign("error", $this->_error);
        $this->tpl->assign("errorMsg", current($this->_error));
        $this->tpl->assign("data", $data);
        $this->tpl->assign("cn", $data['invite']);
        $this->tpl->assign("from_platform", $data['from_platform']);
        $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? true : false);
        $this->tpl->assign("event_cn_hidden", $this->form->data['event_cn_hidden']);
        if ($data['isAjax'] == 1) {
            $errorMsg = $this->_error;
            if (is_array($errorMsg)) {
                $errorMsg = array_pop($errorMsg);
            }
            echo json_encode(array(
                        'errorCode' => -1,
                        'errorMsg' => $errorMsg,
                        ));
        } else {
            $this->tpl->assign("agreement", $agreement);
            if (app_conf('TPL_REGISTER')) {
                $this->template = app_conf('TPL_REGISTER');
            } else {
                // var_dump($_POST);
                $this->template = $this->getShowTemplate();
            }
        }
        $logRegLoginService = new LogRegLoginService();
        $logRegLoginService->insert(trim($this->form->data['username']), '', 2, 0, 1, $this->form->data['invite']);
        return false;
    }

    public function getShowTemplate() {
        return $this->isModal() ? 'web/views/user/modal_register.html' : 'web/views/user/register.html';
    }

    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }



}
