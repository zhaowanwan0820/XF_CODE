<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use core\service\o2o\DiscountService;

class AjaxExpectedEarningInfo extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'money' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL'),
            'discount_id' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL'),
            'consume_type' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        if (!empty($data['money']) && !preg_match('/^\d+(\.\d{1,2})?$/', $data['money'])) {
            $data['money'] = 0;
        }

        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : 1;
        $res = DiscountService::getExpectedEarningInfo($loginUser['id'], $data['id'], $data['money'], $data['discount_id'], $consumeType);
        if ($res === false) {
            $res = array('discountDetail'=>'', 'discountGoodPrice'=>'', 'discountAmount'=>0);
        } else {
            $res['discountGoodPrice'] = rtrim(strtr(base64_encode($res['discountGoodPrice']), '+/', '-_'), '=');
        }

        $this->json_data = $res;
    }
}
