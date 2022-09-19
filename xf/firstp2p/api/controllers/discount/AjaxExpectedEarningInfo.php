<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class AjaxExpectedEarningInfo extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'money' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL'),
            'discount_id' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL'),
            'appversion' => array('filter' => 'int', 'option' => array('optional' => true)),
            'consume_type' => array('filter' => 'int', 'option' => array('optional' => true)),
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

        if (!empty($data['money']) && !preg_match('/^\d+(\.\d{1,2})?$/', $data['money'])) {
            $data['money'] = 0;
        }

        $appversion = isset($data['appversion']) ? $data['appversion'] : '';
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $rpcParams = array($loginUser['id'], $data['id'], $data['money'], $data['discount_id'], $appversion, $consumeType);
        $res = $this->rpc->local('O2OService\getExpectedEarningInfo', $rpcParams);
        if ($res === false) {
            $res = array('discountDetail'=>'', 'discountGoodPrice'=>'', 'discountAmount'=>0);
        } else {
            $res['discountGoodPrice'] = rtrim(strtr(base64_encode($res['discountGoodPrice']), '+/', '-_'), '=');
        }

        $this->json_data = $res;
    }
}
