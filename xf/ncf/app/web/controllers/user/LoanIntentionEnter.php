<?php

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\LoanIntentionService;


class LoanIntentionEnter extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
    }

    public function invoke() {
        $this->template = "web/views/v2/account/frame.html";
        $this->tpl->assign("inc_file","web/views/v2/account/loanIntentionEnter.html");
    }
}
