<?php

/**
 * @abstract openapi  忘记密码修改密码页面
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-28
 */

namespace openapi\controllers\user;

use openapi\controllers\BaseAction;

class ResetPwd extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
    }
    public function invoke() {
        $step = \es_session::get('openapi_forgetpwd_step');
        if ($step['step'] >= 3) {
            $step = \es_session::set('openapi_forgetpwd_step',array('step' => 4));
        } /* elseif ($step['idno'] == false && $step['step'] >= 3) {
            $step = \es_session::set('openapi_forgetpwd_step',array('step' => 4,'idno' => $step['idno']));
        }  */else {
            $this->setErr('ERR_SYSTEM_ACTION_PERMISSION');
            return false;
        }
        setLog(array('openapi_forgetpwd_step'=>\es_session::get('openapi_forgetpwd_step')));
        $req = \es_session::get('openapi_forgetpwd_query');
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $redirect_uri = $req['redirect_uri'];
        $this->tpl->assign("redirect_uri", $redirect_uri);
        $this->template = 'openapi/views/user/resetpwd.html';
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
