<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyShopService;

class ProductDetail extends AppBaseAction {
    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
            'productId' => array('filter' => 'required', 'message'=> ''),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        $productId = $data['productId'];
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }


        $shopService = new CandyShopService();
        $product = $shopService->getProduct($productId);

        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('product', $product);
    }
}
