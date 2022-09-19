<?php

/**
 * @abstract openapi  修改密码页面1
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-26
 */

namespace openapi\controllers\user;

use openapi\controllers\BaseAction;
use libs\web\Form;
class ModifyPwd extends BaseAction {

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
        $data = $this->form->data;
        \es_session::set('openapi_modifypwd_token', $data['oauth_token']);
        //将client_id和时间戳存起来，以便后续使用
        \es_session::set('openapi_modifypwd_query',array('redirect_uri' => $data['redirect_uri']));
        \es_session::set('openapi_modifypwd_step', 1);
        setLog(array('openapi_modifypwd_step'=>\es_session::get('openapi_modifypwd_step')));
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign('openId', $data['openId']);
        $this->template = 'openapi/views/user/modifypwd.html';
    }
    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

}
