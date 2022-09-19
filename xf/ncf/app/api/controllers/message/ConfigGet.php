<?php
/**
 * 用户推送配置读取
 */
namespace api\controllers\message;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class ConfigGet extends AppBaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $user = $this->user;

        $result = $this->rpc->local('MsgBoxService\userConfigGet', array($user['id']));
        return $this->json_data = $result;
    }
}
