<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\o2o\CouponService;

class UnpickCount extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;
        $count = 0;
        $count = CouponService::getUnpickCount($loginUser['id']);
        if(isset($count) && ($count > 9)){
            $count = '9+';//超过9的时候，只显示9+
        }
        $this->json_data = array('count' => $count);
    }
}
