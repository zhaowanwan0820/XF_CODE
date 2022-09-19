<?php
/**
 * 发起兑换确认接口
 */

namespace api\controllers\seller;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class DoApply extends BaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponId' => array("filter" => "int", "message"=>"couponId is empty"),
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

        $couponInfo = $this->rpc->local('O2OService\setCouponConfirm', array($data['couponId'], $loginUser['id']));

        $this->tpl->assign('couponInfo', $couponInfo);
        $this->tpl->assign('token', $this->form->data['token']);
        if ($couponInfo) {
            $couponUserInfo = $this->rpc->local('UserService\getUser', array($couponInfo['ownerUserId']));
            $this->tpl->assign('couponUserInfo', $couponUserInfo);
            $this->template = $this->getTemplate('apply_suc');
        } else {
            $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
            $this->template = $this->getTemplate('apply_fail');
        }
    }
}
