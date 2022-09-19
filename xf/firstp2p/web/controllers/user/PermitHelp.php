<?php
namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\utils\Block;
use core\service\WeiXinService;


class PermitHelp extends BaseAction {
    public function init() {
    }
    public function invoke() {
        $this->template = "web/views/user/permit_help.html";
   }

}
