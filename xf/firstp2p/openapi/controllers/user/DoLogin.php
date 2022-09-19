<?php

/**
 * @author yutao <yutao@ucfgroup.com>
 * @abstract openapi 登录接口
 * @date 2014-11-27
 */

namespace openapi\controllers\user;

use libs\utils\Monitor;
use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Block;
use core\service\LogRegLoginService;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\RequestUserLogin;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\web\Open;

require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";

class DoLogin extends BaseAction {

    //协议弹窗分站clientId
    private static $_clientID = array(
            '房贷' => 'bb469276d5eb331f2cb7c451',
            '典当' => '24cf469b079e3b94ed5a71b9',
            '金融1号' => '4f853a4df204ffcd00924517',
            '艺金融' => '5610e2cd133cd29ecf8e32ee',
            '哈哈' => '6d03d1ab2ac33258fb1b5fcf',
            '荣信汇' => '8365f78859915a7db00e37c6',
            );

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "mobile" => array("filter" => "string"),
                "account" => array("filter" => [$this, "getAccount"], "message" => '用户名不能为空'),
                "password" => array("filter" => "reg", "message" => '用户名密码不匹配', "option" => array("regexp" => "/^.{5,25}$/")),
                "verify" => array("filter" => "reg", "message" => '验证码错误', "option" => array("regexp" => "/^[0-9a-zA-Z]{4,10}$/", 'optional' => true)),
                );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->_before_invoke();
            $this->_error = $this->form->getErrorMsg();
            $this->showLoginError();
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        RiskServiceFactory::instance(Risk::BC_LOGIN,Risk::PF_OPEN_API,$this->device)->check($data,Risk::SYNC);
        $logRegLoginService = new LogRegLoginService();

        //分站弹窗协议处理
        //*****************************************************
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
                    $this->authorize();
                    return true;
                } else {
                    $logRegLoginService->insert($this->form->data['account'], '', 0, 2, 2);
                    $this->_error = '确认迁移失败，请稍后重试';
                    setLog(
                            array(
                                'errmsg' => $this->_error,
                                'uid' => $user['id'],
                                )
                          );
                    $this->showLoginError();
                    return false;
                }
            } else {
                $logRegLoginService->insert($this->form->data['account'], '', 0, 2, 2);
                setLog(array('errmsg' => '表单重复提交','uid' => $user['id'],));
                $this->showLoginError();
                return false;
            }
        }
        //*****************************************************
        // 先检查是否需要校验验证码
        $check_account_name_result = Block::check('WEB_LOGIN_USERNAME', $this->form->data['account'], true);
        $ip = get_client_ip();
        $check_client_ip_result = Block::check('WEB_LOGIN_IP', $ip, true);

        $verify = \es_session::get('verify');
        \es_session::set('verify', 'xxx removeVerify xxx');
        if ($check_account_name_result === false || $check_client_ip_result === false || !empty($verify)) {
            // 校验验证码
            $captcha = $this->form->data['verify'];
            if (md5($captcha) !== $verify) {
                $logRegLoginService->insert($this->form->data['account'], '', 0, 2, 1);
                $this->_error = '您输入的验证码错误';
                Monitor::add('LOGIN_FAIL');
                return $this->showLoginError(1);
            }
        }

        $request = new RequestUserLogin();
        try {
            $request->setAccount($data['account']);
            $request->setPassword($data['password']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            Monitor::add('LOGIN_FAIL');
            return false;
        }
        $userLoginResponse = $GLOBALS['rpc']->callByObject(array(
                    'service' => 'NCFGroup\Ptp\services\PtpUser',
                    'method' => 'login',
                    'args' => $request
                    ));
        if ($userLoginResponse->resCode) {
            $this->_error = $userLoginResponse->errorMsg;
            $logRegLoginService->insert($this->form->data['account'], '', 0, 2, 2);
            // 登录失败则向频次险种中插入记录
            if (Block::check('WEB_LOGIN_USERNAME', $data['account']) === false || Block::check('WEB_LOGIN_IP', $ip) === false) {
                // 如果超过限制，则提示需要填写验证码
                return $this->showLoginError(1);
            } else {
                // 未超过限制泽提示登录失败
                return $this->showLoginError();
            }
            return false;
        }
        //分站弹窗协议处理
        //*****************************************************
        $userInfo = \es_session::get('user_info');
        if((int)app_conf('USER_JXSD_TRANSFER_SWITCH') !== 1) {
            $userInfo['is_dflh'] = 0;
        } else {
            $userInfo['is_dflh'] = intval($userInfo['is_dflh']);
        }
        if ($clientExist && $userInfo['is_dflh'] == 1) {
            $isTransferProtocol = md5('DoLoginIsTransferProtocolToken'.time());
            \es_session::set('DoLoginIsTransferProtocolToken', $isTransferProtocol);
            $this->tpl->assign('isTransferProtocol',$isTransferProtocol);
            $this->tpl->assign('account',$data['account']);
            $this->tpl->assign('password','hsadfgs');//不验证用户密码，到时验证用户名和session标识
            $this->tpl->assign('querystring', (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
            $this->template = $this->getCustomTpl("openapi/views/user/fz_transfer_protocol.html", 'fz_transfer_protocol');
            return;
        }
        //*****************************************************
        RiskServiceFactory::instance(Risk::BC_LOGIN,Risk::PF_OPEN_API)->notify(array('userId'=>$userLoginResponse->userId));
        $logRegLoginService->insert($this->form->data['account'], $userLoginResponse->userId, 0, 1, 2);
        //用户行为追踪，根据配置邀请码，用户是否需要种 trackId
        $couponLatest = $this->rpc->local('CouponService\getCouponLatest', array($userLoginResponse->userId));
        $invite_code = $couponLatest['short_alias'];
        $track_id = \es_session::get('track_id');
        if (!$track_id && in_array(strtoupper($invite_code), get_adunion_order_coupon())) {
            $track_id = hexdec(\libs\utils\Logger::getLogId());
            \es_session::set('track_id', $track_id);
            \es_session::set('track_on', 1);
        }
        $this->authorize();
        return true;
    }

    public function getAccount($account) {
        if (empty($account)) {
            if (empty($this->form->data['mobile']))
                return false;
            else
                $_REQUEST['account'] = $this->form->data['mobile']; // 哈哈农庄会传mobile参数，这里做兼容
        }
        //xss 过滤
        $_REQUEST['account'] = filter_var($_REQUEST['account'], FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/^[0-9a-zA-Z_-]*$/")));
        return true;
    }

    /**
     * oauth2 认证，返回code码
     */
    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    public function authCheck() {
        return true;
    }

    /**
     * 显示注册页面错误提示信息
     * 根据$this->_error 显示错误信息到对应的位置
     * */
    private function showLoginError($show_vcode = 0) {
        $data = $this->form->data;
        /**
         * 防止验证码换名刷新bug
         */
        $verify = \es_session::get('verify');
        if (!empty($verify)) {
            $show_vcode = 1;
        }
        $this->tpl->assign("show_vcode", $show_vcode);
        $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
        $this->tpl->assign("page_title", 'login');
        $this->tpl->assign("website", app_conf('APP_NAME'));
        $this->tpl->assign("error", $this->_error);
        $this->tpl->assign("data", $data);
        $this->tpl->assign("account", $this->form->data["account"]);
        $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'], "MicroMessenger") ? true : false);
        if (@$_REQUEST['from_site'] == 'csh') {
            $this->tpl->assign("mobile", $_REQUEST['account']);
            $this->tpl->assign("from_site", $_REQUEST['from_site']);
        }
        $this->template = $this->getCustomTpl("openapi/views/user/login.html", 'login');
        if (isset($this->clientConf['js']['login'])) {
            $fzjs = $this->clientConf['js']['login'];
            $this->tpl->assign('fzjs', $fzjs);
        }

        $appInfo = $this->getAppInfo();
        //短信登录处理
        if (1 == intval($GLOBALS['sys_config']['SM_LOGIN_SWITCH'])){
            //根据分站开关，是否开启短信验证码登录和注册
            if(!empty($appInfo)){
                $setParams = (array) json_decode($appInfo['setParams'], true);
                if (!empty($setParams['smLogin']) || !empty($_GET['smLogin'])) {
                    $this->tpl->assign("smLogin",  1);
                    $this->setSmLoginToken();
                    // 用户注册协议中的域名 START
                    if (isset($GLOBALS['sys_config']['SITE_DOMAIN'][APP_SITE])) {
                        $rootDomain = $GLOBALS['sys_config']['SITE_DOMAIN'][APP_SITE];
                    }else{
                        $rootDomain = 'www.firstp2p.com';
                    }
                    $this->tpl->assign('rootDomain', $rootDomain);
                    $this->tpl->assign('isMaster', APP_SITE == 'firstp2p' ? 1 : 0);
                }
            }
        }
        return false;
    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

    private function getAppInfo(){
        $redirectUri = urldecode(trim($_REQUEST['redirect_uri']));
        $urlInfo = parse_url($redirectUri);
        if(!empty($urlInfo['query'])){
            parse_str($urlInfo['query'], $param);
            $redirectUri = urldecode(trim($param['redirect_uri']));
            $urlInfo = parse_url($redirectUri);
            if (!empty($urlInfo['host']) && strtolower($urlInfo['host']) != 'm.wangxinlicai.com') {
                $siteId = Open::getSiteIdByDomain($urlInfo['host']);
                if ($siteId) {
                    $appInfo = Open::getAppBySiteId($siteId);
                    return $appInfo;
                }
            }
        }
        return array();
    }
    private function setSmLoginToken(){
        $smLoginToken = \es_session::get('smLoginToken');
        if(empty($smLoginToken)){
            \es_session::set('smLoginToken', md5(session_id().mt_rand(10000, 1000000)));
        }
        $this->tpl->assign('smLoginToken', \es_session::get('smLoginToken'));
    }

}
