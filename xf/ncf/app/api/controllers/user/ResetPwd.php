<?php

namespace api\controllers\user;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

/**
 * 重置初始密码
 */
class ResetPwd extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            "password" => array(
                "filter" => "reg",
                "message" => 'ERR_SIGNUP_PARAM_PASSWORD',
                "option" => array("regexp" => "/^.{6,20}$/")
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        $result = $this->rpc->local('UserService\forceResetInitPwd', array(
            $loginUser['id'],
            $data['password']),
            'user'
        );

        if ($result['status'] != 0) {
            $this->setErr('ERR_MANUAL_REASON', $result['msg']);
        }

        $this->json_data = array('status' => 0);
    }
}
