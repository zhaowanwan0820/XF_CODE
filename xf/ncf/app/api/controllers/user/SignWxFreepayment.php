<?php
/**
 * 用户签署网信超级账户免密协议
 * @author longbo 
 */
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\UserService;

class SignWxFreepayment extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        $ret = UserService::signWxFreepayment($loginUser['id']);
        if (!$ret) {
            $this->setErr(1, '签署失败');
        }

        $this->json_data = array();
    }
}