<?php

namespace api\controllers\candyevent;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyWishService;

class WishResult extends AppBaseAction
{
    const IS_H5 = true;
    const WISH_NO_MAKE = 1;
    const WISH_NO_WIN = 2;
    const WISH_HAS_KOI = 3;
    const WISH_HAS_WIN = 4;

    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        $userId = $loginUser['id'];
        $wishService = new CandyWishService();

        if ($wishService->checkPrize($userId)) {
            $this->tpl->assign('resId', self::WISH_HAS_WIN);
        } else {
            $this->tpl->assign('resId', self::WISH_NO_WIN);
        }

        if (!$wishService->hasMakeWish($userId)) {
            $this->tpl->assign('resId', self::WISH_NO_MAKE);
        }
        
        if ($wishService->checkKoi($userId)) {
            $this->tpl->assign('resId', self::WISH_HAS_KOI);
        }

        $wishInfo = $wishService->getUserWish($userId);

        $this->tpl->assign('wishInfo', $wishInfo);
        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('');
    }

}