<?php
/**
 *  * 发起兑换确认接口
 *   */

namespace web\controllers\seller;

use libs\web\Form;
use api\conf\Error;
use web\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class Doapply extends BaseAction {
    public function init() {
        parent::init();
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            // O2O Feature 礼物ID
            'couponId' => array("filter" => "int", "message"=>"couponId is empty"),
        );
        if (!$this->form->validate()) {
            $error = Error::get($this->form->getErrorMsg());
            $this->show_error($error['errmsg']);
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS ['user_info'];
        $couponInfo = $this->rpc->local('O2OService\setCouponConfirm', array($data['couponId'], $loginUser['id']));
        $this->tpl->assign('couponInfo', $couponInfo);
        if ($couponInfo) {
            $couponUserInfo = $this->rpc->local('UserService\getUser', array($couponInfo['ownerUserId']));
            $this->tpl->assign('couponUserInfo', $couponUserInfo);
            $this->template = 'web/views/v2/seller/apply_suc.html';
        } else {
            $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
            $this->template = 'web/views/v2/seller/apply_fail.html';
        }
    }
}
