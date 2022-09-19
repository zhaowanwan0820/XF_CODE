<?php

/**
 * 用户密保问题——设置密保问题html页成功页
 * @author 刘振鹏<liuzhenpeng@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Block;
use core\service\user\BOFactory;
use libs\utils\Logger;

class ProtectPwdSuccess extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $this->template = "web/views/v2/account/setpwdprotect_3.html";
    }
}













?>
