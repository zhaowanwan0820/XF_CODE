<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Listbyfieldval extends AppBaseAction {
    
    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "val" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke (){
        $data = $this->form->data;
        $val = trim($data['val']);
        if(empty($val)){
            $this->json_data = array();
            return true;
        }

        //根据真实姓名或手机号查询用户
        if(is_numeric($val)){
            $this->json_data = $this->rpc->local("UserService\getUserByMobileORIdno", array($val));
        }else{
            $this->json_data = $this->rpc->local("UserService\getUserByRealName", array($val));
        }
        return true;
    }

}