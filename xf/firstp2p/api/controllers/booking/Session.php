<?php

namespace api\controllers\booking;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\booking\BookService;

/**
 * 预约首页
 */
class Session extends AppBaseAction {
    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        $booking = BookService::myBooking($loginUser['id']);
        $list = BookService::getBookingList();

        $this->tpl->assign('citys', BookService::$CITYS);
        $this->tpl->assign('screen', $booking ? $booking : '');
        $this->tpl->assign('sessions', $list);
        $this->tpl->assign('token', $data['token']);
    }
}
