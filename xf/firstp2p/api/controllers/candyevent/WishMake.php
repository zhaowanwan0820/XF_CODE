<?php

namespace api\controllers\candyevent;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyWishService;

class WishMake extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
            'productId' => array('filter' => 'required', 'message'=> ''),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        if (time() > strtotime(Double11Status::LOTTERY_START_TIME)) {
            $this->setErr(-1, "您已超过许愿时间");
        }

        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        $userId = $loginUser['id'];
        $wishService = new CandyWishService();
        if ($wishService->hasMakeWish($userId)) {
            $this->setErr(-1, "您已许愿");
        }

        if (!$wishService->checkInvest($userId)) {
            $this->setErr(-1, "投资额不足");
        }

        $wishService->makeWish($userId, $data['productId']);
    }

}