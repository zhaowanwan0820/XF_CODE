<?php

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;

class Level extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
           't' => array('filter' => 'int'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;

        $type = intval($data['t']);
        if ($type < 1 || $type > 2) { //目前只有一二阶段
            $type = 1;
        }

        $this->template = "web/views/v3/activity/level{$type}.html";
    }

}
