<?php

namespace api\controllers\booking;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\booking\BookService;

/**
 * 预约
 */
class Book extends AppBaseAction {
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'screen' => array('filter' => 'required', 'message'=> '预约场次不能为空'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        $data = $this->form->data;
        $bookingId = false;

        try {
            $bookingId = BookService::book($loginUser['id'], $data['screen']);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $message = $message ? $message : '系统繁忙, 请重试';
            $this->setErr('ERR_MANUAL_REASON', $message);
        }

        $this->json_data = array('booking'=>$bookingId);
    }
}
