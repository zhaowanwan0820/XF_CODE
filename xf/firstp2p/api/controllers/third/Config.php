<?php
/**
 * Config Info
 */
namespace api\controllers\third;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use libs\utils\Logger;

class Config extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        $rs = [];
        if (intval($user['idcardpassed']) == 1 && intval($user['id_type']) == 1) {
            $rs = $this->rpc->local('ThirdpartyPtpService\getPlatform', [$user['id']]);
        }
        $this->json_data = $rs;
    }
}
