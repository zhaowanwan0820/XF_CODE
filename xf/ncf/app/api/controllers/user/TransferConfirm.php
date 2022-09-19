<?php
/**
 * 用户迁移到经讯时代确认
 * @author 王传路
 */
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\UserService;

class TransferConfirm extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->user;

        $ret = UserService::updateUserToJXSD($loginUser['id']);
        if($ret) {
            $this->json_data = array();
            return true;
        } else {
            $this->setErr(1, '确认迁移失败，请稍后重试');
        }
    }
}
