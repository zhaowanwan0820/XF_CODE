<?php

/**
 * 新版用户登录
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\utils\Block;

class Login extends BaseAction {

    public function init() {
        if(!empty($_REQUEST['source']) && ('anbind' == $_REQUEST['source'])){
            \es_session::delete('user_info');
            unset($GLOBALS['user_info']);
        }
        // 已经登陆不需要再次登陆
        if (!empty($GLOBALS ['user_info'])) {
            if (empty($_GET['client_id'])) {
                if (!empty($_GET['backurl'])) {
                    $ret = parse_url($_GET['backurl']);
                    $host = get_host();
                    if ($host == $ret['host']) {
                        return app_redirect($_GET['backurl']);
                    }
                }
                return app_redirect(url("/"));
            }
        }
    }

    public function invoke() {
        $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
        $this->tpl->assign("page_title", '登录');
        $this->tpl->assign("website",  app_conf('SHOP_TITLE'));

        $this->tpl->assign("smLogin",  0);

        //for test
        //$this->setSmLoginToken();

        if (1 == intval($GLOBALS['sys_config']['SM_LOGIN_SWITCH'])){
            if(!empty($this->appInfo)){
            //根据分站开关，是否开启短信验证码登录和注册
                $setParams = (array) json_decode($this->appInfo['setParams'], true);
                if (!empty($setParams['smLogin'])) {
                    $this->tpl->assign("smLogin",  intval($setParams['smLogin']));
                    //随进token,需要前端传输到webDoLogin，进行验证
                    $this->setSmLoginToken();
                }
                if(!empty($_GET['smLogin'])){
                    $this->tpl->assign("smLogin",  intval($_GET['smLogin']));
                    $this->setSmLoginToken();
                }
            }
        }

        $querystring = empty($_SERVER['QUERY_STRING']) ? '' : '?'.htmlspecialchars($_SERVER['QUERY_STRING']);

        $this->tpl->assign('querystring', $querystring);
        $verify = \es_session::get('verify');
        // 验证码
        if (!empty($verify)) {
            $this->tpl->assign("show_vcode",'1');
        }

        if (app_conf('TPL_LOGIN')) {
            $this->template = app_conf('TPL_LOGIN');
        } elseif(!empty($_GET['tpl'])) {
            // pc多模板登录
            $this->template = 'web/views/user/login_'.trim($_GET['tpl']).'.html';
        } else {
            $this->template = $this->getShowTemplate();
        }
    }

    public function getShowTemplate() {
        return $this->isModal() ? 'web/views/user/modal_login.html' : 'web/views/user/login.html';
    }

    private function setSmLoginToken(){
        $smLoginToken = \es_session::get('smLoginToken');
        if(empty($smLoginToken)){
            \es_session::set('smLoginToken', md5(session_id().mt_rand(10000, 1000000)));
        }
        $this->tpl->assign('smLoginToken', \es_session::get('smLoginToken'));
    }

}
