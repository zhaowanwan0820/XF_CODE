<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestInvestAnalyse;

class InvestAnalyse extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "type" => array("filter" => "int", 'option' => array('optional' => true)),
            "userId" => array("filter" => "int", 'option' => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $type = isset($data['type']) ? intval($data['type']) : 0;

        $request = new RequestInvestAnalyse();
        $request->setCfpId($userInfo->userId);
        $request->setType($type);
        if (isset($data['userId']) && $data['userId'] > 0) {
            $request->setUserId(intval($data['userId']));
        } else {
            $request->setUserId(0);
        }
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'getInvestAnalyse',
            'args' => $request
        ));

        $this->json_data = $response->toArray();
        return true;
    }

}
