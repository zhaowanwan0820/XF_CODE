<?php
/**
 * 用户密保问题——设置密保问题html页成功页
 * @author 刘振鹏<liuzhenpeng@ucfgroup.com>
 */
namespace web\controllers\account;

use web\controllers\BaseAction;

class ProtectPwdSuccess extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $this->template = "web/views/account/setpwdprotect_3.html";
    }
}