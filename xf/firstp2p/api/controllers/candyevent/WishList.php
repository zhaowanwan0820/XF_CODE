<?php

namespace api\controllers\candyevent;

use api\controllers\AppBaseAction;
use core\service\candy\CandyWishService;
use libs\web\Form;

class WishList extends AppBaseAction
{
    const IS_H5 = true;
    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if (time() > strtotime(Double11Status::LOTTERY_START_TIME)) {
            $url = "WishLottery?token=" . $data['token'];
            app_redirect($url);
            return true;
        }

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 检查用户是否许愿
        $userId = $loginUser['id'];
        $wishSercvice = new CandyWishService();
        if ($wishSercvice->hasMakeWish($userId)) {
            $url = "/candyevent/WishDetail?token=" . $data['token'];
            app_redirect($url);
            return true;
        }

        // 展示商品
        $productList = $wishSercvice->getProducts();

        // 商品许愿比例
        foreach ($productList as $key => $val) {
            $productList[$key]['productWishRate'] = $wishSercvice->getWishRate($key);
        }

        $this->tpl->assign('productList', $productList);
        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('');
    }

}