<?php

namespace api\controllers\candyevent;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyWishService;
use api\controllers\candyevent\WishResult;

class WishDoLottery extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
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
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        $userId = $loginUser['id'];
        $wishService = new CandyWishService();

        $wishInfo = $wishService->getUserWish($userId);
        if (!$wishService->hasMakeWish($userId)) {
            $this->json_data = [
                'res' => WishResult::WISH_NO_MAKE,
            ];
            return true;
        }

        if ($wishService->hasWishLottery($userId)) {
            $this->setErr('ERR_DARKMOON_UDPATE_DEAL_LOAD_FAIL');
        }

        try {
            $wishResult = $wishService->lottery($userId);
        } catch (\Exception $e) {
            $this->setErr('ERR_DARKMOON_UDPATE_DEAL_LOAD_FAIL');
        }

        if (!$wishResult) {
            $this->json_data = [
                'res' => WishResult::WISH_NO_WIN,
                'image' => $wishInfo['image']
            ];
            return true;
        }

        if ($wishService->checkKoi($userId)) {
            $this->json_data = [
                'res' => WishResult::WISH_HAS_KOI,
                'image' => $wishInfo['image']
            ];
            return true;
        }

        $this->json_data = [
            'res' => WishResult::WISH_HAS_WIN,
            'image' => $wishInfo['image']
        ];
    }

}