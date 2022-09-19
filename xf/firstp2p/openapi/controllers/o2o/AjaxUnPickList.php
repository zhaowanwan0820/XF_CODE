<?php
namespace openapi\controllers\o2o;

use libs\web\Form;
use openapi\controllers\BaseAction;

class AjaxUnPickList extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'page'=>array('filter' => 'int','option' => array('optional' => true)),
            'oauth_token' => array("filter" => "required", "message" => "token is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        $userId = $userInfo->userId;
        $page   = empty($data['page']) ? 1 : intval($data['page']);
        $rpcParams = array($userId,  $page);
        $response = $this->rpc->local('O2OService\getUnpickList', $rpcParams);
        $this->json_data = $response;
        return true;
    }
}
