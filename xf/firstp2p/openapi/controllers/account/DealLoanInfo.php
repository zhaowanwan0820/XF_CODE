<?php

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\conf\Error;
use openapi\controllers\BaseAction;


/**
 * 投资详情
 */
class DealLoanInfo extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'open_id' => ['filter' => 'string', 'required' => true, "message" => "open_id is error"],
            'load_id' => ['filter' => 'int', 'required' => true, "message" => "load_id is error"],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $userObj = $this->getUserByAccessToken();

        $data = $this->form->data;
        $loadId = intval($data['load_id']);
        $userId = $userObj->userId;

        try{
            $XHService = new \core\service\XHService();
            $res = $XHService->bidInfoSearch($loadId,$userId);
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
            return false;
        }

        $this->json_data = $res;
        return true;
    }
}