<?php

namespace api\controllers\agreement;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\AgreementService;

class Check extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL'),
                'type' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL'),
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
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $result = AgreementService::check($user['id'], $data['type']);
        $this->json_data = array("pass" => $result);
    }

}
