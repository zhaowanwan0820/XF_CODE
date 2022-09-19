<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use core\service\o2o\DiscountService;

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
        $loginUser = $this->user;

        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : 1;
        $bidDayLimit = isset($data['bid_day_limit']) ? $data['bid_day_limit'] : 0;

        $res = DiscountService::getAvailableDiscountCount($loginUser['id'], $data['deal_id'], false, $type, $consumeType, $bidDayLimit);
        // 前端要求数据类型数据类型是int
        if ($res === false) {
            $count = 0;
        } else {
            $count = intval($res['data']);
        }

        $this->json_data = $count;
    }
}
