<?php

/**
 * @abstract openapi  忘记密码页面1
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-28
 */

namespace openapi\controllers\user;

use openapi\controllers\BaseAction;
use libs\web\Form;
class ForgetPwd extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'redirect_uri' => array("filter" => "string","option" => array("optional" => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        \es_session::set('openapi_forgetpwd_step',array('step' => 1));
        \es_session::set('openapi_forgetpwd_query',array('redirect_uri' => $data['redirect_uri']));
        setLog(array('openapi_forgetpwd_step'=>\es_session::get('openapi_forgetpwd_step')));
        setLog(array('openapi_forgetpwd_query'=>\es_session::get('openapi_forgetpwd_query')));
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->template = 'openapi/views/user/forgetpwd.html';
    }
    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }
}
