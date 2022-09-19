<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestUser;

class DaysInvestStat extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
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

        $request = new RequestUser();
        $request->setCfpId(intval($userInfo->userId));
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpPerformance',
            'method' => 'getDaysInvestStat',
            'args' => $request
        ));

        $data = array();
        $data['todayClientele'] = $response->getTodayInvest();
        $data['totalClientele'] = $response->getTotalCustomers();
        $data['clienteleDaysJSON'] = $response->getDayInvestNumDetail();
        $data['radarJSON'] = $response->getMoneyInvestDetail();

        $this->json_data = $data;
        return true;
    }

}

