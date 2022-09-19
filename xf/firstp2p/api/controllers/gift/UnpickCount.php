<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Common\Library\ApiService;

class UnpickCount extends AppBaseAction {
    const GIFT_TYPE_UNPICK = 1;//个人中心礼券指向未领取
    const GIFT_TYPE_MINE = 2;//个人中心礼券指向已领取
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $count = 0;
        $rpcParams = array($loginUser['id']);
        $count = $this->rpc->local('O2OService\getUnpickCount', $rpcParams);
        $hasUnpick = false;
        $result['count'] = $count;
        if(isset($count) && ($count > 0)){
            $result['desc'] = $count.'张未领取';
            $result['giftType'] = self::GIFT_TYPE_UNPICK;
            $hasUnpick = true;
        }
        //没有未领取，检查是否有新到的券
        if (!$hasUnpick) {
            #$newCount = ApiService::rpc("o2o", "coupon/getUserNewCouponCount", ['userId' => $loginUser['id']]);
            $newCount = 0;
            if ($newCount) {
                $result['desc'] = $newCount.'张新到';
                $result['giftType'] = self::GIFT_TYPE_MINE;
            }
        }

        $this->json_data = $result;
    }
}
