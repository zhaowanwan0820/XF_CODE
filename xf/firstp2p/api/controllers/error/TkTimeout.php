<?php

namespace api\controllers\error;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;

class TkTimeout extends AppBaseAction {
    public function init() {
        parent::init();
    }

    public function invoke() {
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }
}
