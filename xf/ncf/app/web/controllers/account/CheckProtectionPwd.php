<?php
/**
 * 用户密保问题——回答密保问题html页
 * @刘振鹏
 * @author <liuzhenpeng@ucfgroup.com>
 */
namespace web\controllers\account;

use web\controllers\BaseAction;

class CheckProtectionPwd extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $this->template = "web/views/v2/account/chkpwdprotect.html";
    }
}