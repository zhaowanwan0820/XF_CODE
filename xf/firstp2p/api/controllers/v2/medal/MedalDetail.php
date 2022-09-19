<?php

namespace api\controllers\medal;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\MedalService;

class MedalDetail extends AppBaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'medalId' => array('filter' => 'int'),
        );
       
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $medalId = $data['medalId'];
        $user = $this->getUserByToken();
        if(empty($user)) {
            $this->json_data = $this->rpc->local('MedalService\getMedal', array($medalId, true));
            return true;
        }
        $userId = $user['id'];
        $request = $this->rpc->local('MedalService\createUserMedalRequestParameter', array($userId, $medalId));
        $result = $this->rpc->local('MedalService\getOneMedalDetail', array($request));
        $this->json_data = $result;    
    }
}
