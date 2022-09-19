<?php

/**
 * @abstract openapi  忘记密码身份号验证页面
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016-04-28
 */

namespace openapi\controllers\user;

use openapi\controllers\BaseAction;
use libs\web\Form;
class ForgetPwdIdno extends BaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
    }
    public function invoke() {
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->template = 'openapi/views/user/forgetpwdidno.html';
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
