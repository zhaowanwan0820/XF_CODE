<?php
/**
 * VipIntro.php
 *
 * @date 2018年03月22日
 */

namespace web\controllers\account;

use web\controllers\BaseAction;


class VipIntro extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $this->template = "web/views/account/vipintro.html";
    }
}
