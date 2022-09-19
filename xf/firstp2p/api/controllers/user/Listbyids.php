<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Listbyids extends AppBaseAction {
    
    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "uids" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke (){
        $data = $this->form->data;
        $uids = trim($data['uids']);
        $uidArray = explode(',', $uids);
        if(empty($uidArray)){
            $this->json_data = array();
            return true;
        }

        $this->json_data = $this->rpc->local("UserService\getUserInfoByIds", array($uidArray, 'id,real_name, mobile, idno, create_time'));
        return true;
    }

}