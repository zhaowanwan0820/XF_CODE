<?php

namespace task\controllers\index;

use libs\web\Form;
use task\controllers\BaseAction;

class Index extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        return true;
    }

    public function invoke() {
        $this->json_data = array('a'=>'b', 'c'=>'d');
        return true;
    }
}