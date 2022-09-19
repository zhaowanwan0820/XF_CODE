<?php

namespace web\controllers\bind;

use libs\web\Bind;
use libs\web\Form;
use web\controllers\BaseAction;

class Error extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'device'   => array('filter' => 'string'),
            'errno' => array('filter' => 'string'),
            'errmsg'   => array('filter' => 'string'),
        );

        $this->form->validate();
    }


    public function invoke() {
        $data = $this->form->data;

        $this->tpl->assign('errmsg', $data['errmsg']);
        $this->tpl->assign('errno', $data['errno']);

        if ($data['device'] == 'wap') {
            $this->template = 'web/views/v3/bind/error_wap.html';
        } else {
            $this->template = 'web/views/v3/bind/error.html';
        }
        return false;
 
    }
}

