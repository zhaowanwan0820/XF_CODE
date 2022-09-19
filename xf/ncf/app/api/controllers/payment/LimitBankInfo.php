<?php
/**
 *  获取快捷银行卡信息
 */
namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\payment\PaymentUserAccountService;

class LimitBankInfo extends AppBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>"required")
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $loginUser = $this->user;

        $obj = new PaymentUserAccountService();
        $result = $obj->getChargeLimitH5($loginUser['id']);
        if (!isset($result['respCode']) || $result['respCode'] != '00') {
            $this->setErr($result['respCode'], $result['respMsg']);
        }
        $this->json_data = $result['data'];
    }
}