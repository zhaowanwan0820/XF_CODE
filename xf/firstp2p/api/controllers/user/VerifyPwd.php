<?php
/**
 * 验证用户密码
 * @author longbo
 */
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\BOBase;

class VerifyPwd extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'password' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        $BoBase = new BoBase();
        if ($BoBase->compilePassword(trim($data['password'])) == $loginUser['user_pwd']) {
            $this->json_data = [];
        } else {
            $this->setErr('ERR_MANUAL_REASON', '用户密码不正确');
        }
    }
}


