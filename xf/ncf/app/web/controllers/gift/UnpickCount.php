<?php

namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\o2o\CouponService;

class UnpickCount extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
    }

    public function invoke() {
        $loginUser = $GLOBALS['user_info'];
        $count = 0;
        $rpcParams = array($loginUser['id']);
        $count = CouponService::getUnpickCount($loginUser['id']);
        if(isset($count) && ($count > 9)){
            $count = '9+';//超过9的时候，只显示9+
        }
        $this->json_data = array('count' => $count);
    }
}
