<?php

/**
 * openapi
 * 充值检查 (Ajax接口)
 */

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\BaseAction;

class PayCheckAsyn extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'orderSn' => array('filter' => 'int'),
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        $userId = $userInfo->userId;
        $orderSn = $this->form->data['orderSn'];
        //状态查询
        $status = $this->rpc->local('PaymentService\getChargeStatusCache', array($userId, $orderSn));

        $result = array('status' => 1);
        if (!$status) {
            $result['status'] = 0;
        }

        $this->json_data = $result;
        return true;
    }

}
