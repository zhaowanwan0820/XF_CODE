<?php

/**
 * @abstract openapi  邮箱设置首页
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-08-26
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\web\Open;

class SetEmail extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array("filter" => "required", "message" => "oauth_token不能为空"),
            'redirect_uri' => array("filter" => "string","option" => array("optional" => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userMail = $userInfo->getEmail();

        if ($this->form->data['redirect_uri']) {
            if (!$this->redirect_host_invoke()) {
                $this->setErr('ERR_PARAMS_VERIFY_FAIL','非法的redirect_uri');
                return false;
            }
           \es_session::set('openapi_modifymail_redirect_uri', $this->form->data['redirect_uri']);
        }
        \es_session::set('openapi_modifymail_token', $this->form->data['oauth_token']);
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        if ($userMail) {
            $this->tpl->assign("user_mail", $userMail);
            $this->template = "openapi/views/user/modify_mail.html";//已填写邮箱的用户
        } else {
            $this->template = "openapi/views/user/set_mail.html";//没有填写邮箱的用户
        }
    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }
    private function redirect_host_invoke() {
        $oauthServerConf = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'];
        $confHost = isset($oauthServerConf[$_REQUEST['client_id']]['redirect_uri']) ? parse_url($oauthServerConf[$_REQUEST['client_id']]['redirect_uri'],PHP_URL_HOST) : '';
        $redirectHost = parse_url($this->form->data['redirect_uri'],PHP_URL_HOST);
        if ($confHost == $redirectHost) {
            return true;
        }
        //open域host
        $siteId = Open::getSiteIdByDomain($redirectHost);
        if ($siteId && Open::getAppBySiteId($siteId)) {
            return true;
        }
        return false;
    }

}

