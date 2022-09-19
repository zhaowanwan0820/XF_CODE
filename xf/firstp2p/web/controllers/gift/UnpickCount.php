<?php

namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Common\Library\ApiService;

class UnpickCount extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
    }

    public function invoke() {
        $loginUser = $GLOBALS['user_info'];

        $count = 0;
        $rpcParams = array($loginUser['id']);
        $count = $this->rpc->local('O2OService\getUnpickCount', $rpcParams);
        $hasUnpick = false;
        $result['count'] = $count;
        if(isset($count) && ($count > 0)){
            $result['desc'] = $count.'张未领取';
            $result['giftType'] = 1;
            $hasUnpick = true;
        }
        //没有未领取，检查是否有新到的券
        if (!$hasUnpick) {
            $newCount = ApiService::rpc("o2o", "coupon/getUserNewCouponCount", ['userId' => $loginUser['id']]);
            if ($newCount) {
                $result['desc'] = $newCount.'张新到';
                $result['giftType'] = 2;
            }
        }

        ajax_return($result);
    }
}
