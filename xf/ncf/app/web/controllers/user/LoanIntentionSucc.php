<?php

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\LoanIntentionService;


class LoanIntentionSucc extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
    }

    public function invoke() {
        $type = \es_session::get('loanIntention');
        $this->tpl->assign("type", $type);
        $this->template = "web/views/v2/account/frame.html";
        $this->tpl->assign("inc_file","web/views/v2/account/borrowsucc.html");
    }
}
