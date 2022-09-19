<?php

namespace openapi\controllers\user;

use libs\web\Open;
use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Block;

class Login extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "client_id" => array("filter" => 'string'),
                "redirect_uri" => array("filter" => 'string'),
                "response_type" => array("filter" => 'string'),
                "scope" => array("filter" => 'string'),
                "state" => array("filter" => 'string'),
                "from_register" => array("filter" => 'string'),
                'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字", 'option' => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/", 'optional' => true)),
                );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        $this->template = "openapi/views/user/loginError.html";
        $this->form->validate();
    }

    public function invoke() {
        //路径跟踪id
        if(!empty($_REQUEST['track_id'])){
            \es_session::set('track_id', $_REQUEST['track_id']);
        }
        $this->tpl->assign("page_title", '登录');
        $this->tpl->assign("website", app_conf('SHOP_TITLE'));
        $this->tpl->assign('querystring', (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
        $emptyUser = array('account' => '', 'password' => '');
        $this->tpl->assign("data", $emptyUser);
        $this->tpl->assign("from_register", !empty($_REQUEST['from_register']) ? $_REQUEST['from_register'] : '');
        $this->tpl->assign("mobile", $this->form->data['mobile']);
        $this->tpl->assign("from_site", !empty($_REQUEST['from_site']) ? $_REQUEST['from_site'] : '');
        $this->tpl->assign('regTempl', $this->rpc->local('RegisterTempleteService\getTemplete', array(trim($_REQUEST['from_platform']))));
        $ip = get_client_ip();
        $check_client_ip = Block::check('WEB_LOGIN_IP', $ip, true);
        $verify = \es_session::get('verify');
        if (!empty($verify) || $check_client_ip === false) { //验证码
            $this->tpl->assign("show_vcode", '1');
        }
        $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? true : false);
        $this->template = $this->getCustomTpl("openapi/views/user/login.html", 'login');
        if(isset($this->clientConf['js']['login'])){
            $fzjs = $this->clientConf['js']['login'];
            $this->tpl->assign('fzjs', $fzjs);
        }

        $this->tpl->assign("smLogin",  0);
        //for test
        //$this->tpl->assign("smLogin", 1);

        $redirectUri = urldecode(trim($_REQUEST['redirect_uri']));
        $urlInfo = parse_url($redirectUri);
        if(empty($urlInfo['query'])) return true;
        parse_str($urlInfo['query'], $param);

        $redirectUri = urldecode(trim($param['redirect_uri']));
        $urlInfo = parse_url($redirectUri);

        $appInfo = array();
        if (!empty($urlInfo['host']) && strtolower($urlInfo['host']) != 'm.wangxinlicai.com') {
            $siteId = Open::getSiteIdByDomain($urlInfo['host']);
            if ($siteId) {
                $appInfo = Open::getAppBySiteId($siteId);
                $this->tpl->assign("appInfo", $appInfo);
            }
            $this->tpl->assign("is_fenzhan", true);
            //分站短信设置
            if (1 == intval($GLOBALS['sys_config']['SM_LOGIN_SWITCH']) && !empty($appInfo)){
                //根据分站开关，是否开启短信验证码登录和注册
                $setParams = (array) json_decode($appInfo['setParams'], true);
                if (!empty($setParams['smLogin'])) {
                    $this->tpl->assign("smLogin",  intval($setParams['smLogin']));
                    $this->setSmLoginToken();
                    $this->setRegAgreement();
                }
                if(!empty($_GET['smLogin'])){
                    $this->tpl->assign("smLogin",  intval($_GET['smLogin']));
                    $this->setSmLoginToken();
                    $this->setRegAgreement();
                }
            }//分站短信设置
        }

    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->assign("errorCode", $this->errorCode);
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

    public function authCheck() {
        return true;
    }
    private function setSmLoginToken(){
        $smLoginToken = \es_session::get('smLoginToken');
        if(empty($smLoginToken)){
            \es_session::set('smLoginToken', md5(session_id().mt_rand(10000, 1000000)));
        }
        $this->tpl->assign('smLoginToken', \es_session::get('smLoginToken'));
    }
    private function setRegAgreement(){
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
