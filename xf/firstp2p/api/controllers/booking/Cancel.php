<?php

namespace api\controllers\booking;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\booking\BookService;

/**
 * 取消预约
 */
class Cancel extends AppBaseAction {
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'screen' => array('filter' => 'required', 'message'=> '取消场次不能为空'),
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
        BookService::cancel($loginUser['id'], $data['screen']);
        $this->json_data = array();
    }
}
