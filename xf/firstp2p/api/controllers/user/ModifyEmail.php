<?php
/**
 * 修改邮箱
 * @author longbo
 */
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\BOFactory;

class ModifyEmail extends AppBaseAction
{

    private $_error = null;

    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'new_email' => array('filter' => 'email', 'message' => 'ERR_SIGNUP_PARAM_EMAIL'),
            'password' => array(
                'filter' => 'length',
                'message' => 'ERR_PASS_RULE',
                "option" => array(
                    "min" => 5,
                    "max" => 25
                )
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke ()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        if (strtolower($loginUser['email']) == strtolower($data['new_email'])) {
            return $this->setErr('ERR_EMAIL_REPEAT');
        }

        $bo = BOFactory::instance('web');
        $pwd_compile = $bo->compilePassword($data['password']);
        if($pwd_compile !== $loginUser['user_pwd']){
            return $this->setErr('ERR_AUTH_FAIL');
        }

        $is_exist = $this->rpc->local('UserService\checkEmailExist', array($data['new_email']));
        if($is_exist){
            return $this->setErr('ERR_SIGNUP_EMAIL_UNIQUE');
        }

        $save_data = array('id' => $loginUser['id'], 'email' => $data['new_email']);
        $save = $this->rpc->local('UserService\updateInfo', array($save_data));
        if($save){
            $this->json_data = array();
            return true;
        }
        return $this->setErr(0, 'Email更新失败');
    }
}
