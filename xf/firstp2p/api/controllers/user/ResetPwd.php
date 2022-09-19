<?php

namespace api\controllers\user;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class ResetPwd extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            "password" => array("filter" => "reg", "message" => 'ERR_SIGNUP_PARAM_PASSWORD', "option" => array("regexp" => "/^.{6,20}$/")),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $newPwd = $data['password'];
        $result = $this->rpc->local('UserService\forceResetInitPwd', array($loginUser['id'], $newPwd));
        if ($result['status'] == 0) {
            $this->json_data = array('status' => 0);
        } else {
            $this->setErr('ERR_MANUAL_REASON', $result['msg']);
        }
    }
}
