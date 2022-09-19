<?php
/**
 * add email
 * @author longbo
 */
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\MsgConfigService;

class AddEmail extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'email' => array('filter' => 'email', 'message' => 'ERR_SIGNUP_PARAM_EMAIL'),
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
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        if (!empty($loginUser['email'])){
            return $this->setErr('ERR_EMAIL_HAS_SET');
        }
        $isExist = $this->rpc->local('UserService\checkEmailExist', array($data['email']));
        if($isExist){
            return $this->setErr('ERR_SIGNUP_EMAIL_UNIQUE');
        }
        $emailData = array('id' => $loginUser['id'], 'email' => $data['email']);
        if ($this->rpc->local('UserService\updateInfo', array($emailData))) {
            $this->json_data = array();
            return true;
        } else {
            return $this->setErr(0, '添加邮箱失败');
        }

    }

}
