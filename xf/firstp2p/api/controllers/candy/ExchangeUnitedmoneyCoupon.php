<?php
namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyShopService;

class ExchangeUnitedmoneyCoupon extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
            'productId' => array('filter' => 'required', 'message' => '商品ID不能为空'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $productId = $data['productId'];
        $loginUserInfo = $this->getUserByToken();
        $userId = $loginUserInfo['id'];
        if (empty($loginUserInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $shopService = new CandyShopService();
        $shopService->exchangeUnitedmoneyCoupon($userId, $productId);
    }
}