<?php
/*
 * @date 2019-05-15
 * @author weiwei12 <weiwei12@ucfgroup.com>
 */

namespace api\controllers\payment;


use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\PaymentService;

/**
 * 充值卡解绑
 *
 *
 * Class ChargeCardUnbind
 * @package api\controllers\payment
 */
class ChargeCardUnbind extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "bankCardId" => array("filter" => "required", "message" => "bankCardId is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['token'])) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        $paymentService = new PaymentService();
        $result = $paymentService->unbindCard($user['id'], $data['bankCardId'], true);
        if (!$result) {
            $this->setErr('ERR_MANUAL_REASON', '解绑充值卡失败');
            return false;
        }
        $this->json_data = ['ret' => $result];
    }
}
