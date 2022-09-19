<?php

/**
 * @abstract openapi  修改密码页面2
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-28
 */

namespace openapi\controllers\user;
use libs\web\Form;
use openapi\controllers\BaseAction;

class RenewPwd extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array('openId' => array("filter" => "string"));
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $step = \es_session::get('openapi_modifypwd_step');
        if ($step < 2) {
            $this->setErr("ERR_SYSTEM_ACTION_PERMISSION");
            return false;
        }
        \es_session::set('openapi_modifypwd_step', 4);
        $req = \es_session::get('openapi_modifypwd_query');
        setLog(array('openapi_modifypwd_step'=>\es_session::get('openapi_modifypwd_step')));
        $redirect_uri = $req['redirect_uri'];
        $openId = $this->form->data['openId'];
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign("redirect_uri", $redirect_uri);
        $this->tpl->assign("openId", $openId);
        $this->template = 'openapi/views/user/renewpwd.html';
    }
    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

    public function authCheck() {
        return true;
    }

}
