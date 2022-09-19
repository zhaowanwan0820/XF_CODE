<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyShopService;

class Exchange extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
            'productId' => array('filter' => 'required', 'message' => '商品ID不能为空'),
            'addressId' => array('filter' => 'int')
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $productId = intval($data['productId']);
        $addressId = intval($data['addressId']);
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $shopService = new CandyShopService();
        $shopService->exchangeCoupon($loginUser['id'], $productId, $addressId);
    }
}
