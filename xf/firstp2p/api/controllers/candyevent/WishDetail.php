<?php

namespace api\controllers\candyevent;

use api\controllers\AppBaseAction;
use core\service\candy\CandyWishService;
use libs\web\Form;

class WishDetail extends AppBaseAction
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
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = $loginUser['id'];
        $wishService = new CandyWishService();
        $userWishProduct = $wishService->getUserWish($userId);

        $this->tpl->assign('productDetail', $userWishProduct);
        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('');
    }
}