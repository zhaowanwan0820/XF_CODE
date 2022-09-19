<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class AjaxAvaliableCount extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'deal_id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'consume_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'bid_day_limit' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
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

        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $bidDayLimit = isset($data['bid_day_limit']) ? $data['bid_day_limit'] : 0;

        $rpcParams = array($loginUser['id'], $data['deal_id'], false, $type, $consumeType, $bidDayLimit);
        $count = $this->rpc->local('O2OService\getAvailableDiscountCount', $rpcParams);
        if ($count === false) $count = 0;

        $this->json_data = $count;
    }
}
