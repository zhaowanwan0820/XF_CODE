<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use libs\utils\Logger;

// 投资券赠送
class AjaxGive extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'to_user_id' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
            'discount_id' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
            'discount_sign' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL')
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

        $res = array();
        $rpcParams = array($loginUser['id'], $data['to_user_id'], $data['discount_id']);
        $params = array('from_user_id'=> $loginUser['id'], 'to_user_id'=> $data['to_user_id'], 'discount_id' => $data['discount_id']);
        $signStr = $this->rpc->local('DiscountService\getSignature', array($params));
        // 参数验证
        if ($data['discount_sign'] != $signStr) {
            logger::wLog("API_DISCOUNT:". http_build_query($params)."&sign={$data['discount_sign']}&local={$signStr}");
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '参数错误');
            return false;
        }

        // 赠送
        $res = $this->rpc->local('O2OService\giveDiscount', $rpcParams);
        if ($res === false) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }

        $this->json_data = $res;
    }
}
