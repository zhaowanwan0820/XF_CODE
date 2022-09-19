<?php

namespace api\controllers\candyevent;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyWishService;

class WishLottery extends AppBaseAction
{
    const IS_H5 = true;
    // 双十一活动结束时间
    const DOUBLE11_ACTIVITY_END = '2018-11-17';

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
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        $userId = $loginUser['id'];
        $wishService = new CandyWishService();
        if ($wishService->hasWishLottery($userId)) {
            $url = "/candyevent/WishResult?token=" . $data['token'];
            app_redirect($url);
        }
        
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('isActivityEnd', time() > strtotime(self::DOUBLE11_ACTIVITY_END));

        $this->template = $this->getTemplate('');
    }

}