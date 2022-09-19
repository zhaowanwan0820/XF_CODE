<?php
/**
 * 确认礼物兑换接口
 */

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class Confirm extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'couponId' => array('filter' => 'required'),
            // O2O Feature 礼物ID
            // O2O Feature
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

        $res = $this->rpc->local('O2OService\setCouponConfirm', array($data['couponId'], $loginUser['id']));
        $this->template = $this->getTemplate('confirm');
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }
}
