<?php

namespace openapi\controllers\common;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;

class Adv extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int"),
            "key"=> array("filter"=>"string"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $site_id = $data['site_id'] ? $data['site_id'] : 1;
        $advName = $data['key'] ? $data['key'] : 'WAP_TOP_SLIDES';
        $response = $this->rpc->local('AdvService\getAdv', array($advName));
        if(empty($response)){
            $response = null;
        }
        $this->json_data = $response;
    }
}
