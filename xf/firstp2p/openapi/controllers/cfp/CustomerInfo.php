<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestUser;

class CustomerInfo extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "userId" => array("filter" => "required", "message" => "请指定客户ID"),
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
        $request->setUserId(intval($data['userId']));
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'getCustomerInfo',
            'args' => $request
        ));

        $data = array(
            'userId' => $response->getUserId(),
            'userName' => $response->getUserName(),
            'realName' => $response->getRealName(),
            'mobile' => $response->getMobile(),
            'memo' => $response->getMemo(),
            'investingTotal' => number_format($response->getInvestingTotal() / 10000, 2),
            'latestDay' => $response->getLatestDay(),
            'profitRatioAvg' => $response->getProfitRatioAvg(),
            'periodAvg' => $response->getPeriodAvg(),
            'investNum' => $response->getInvestNum(),
            'pastDay' => $response->getPastDay(),
            'dealName' => $response->getDealName(),
            'loanAmount' => $response->getLoanAmount(),
            'dealRate' => $response->getDealRate(),
            'dealLoanType' => $response->getDealLoanType(),
            'benefitMoney'=> number_format($response->getProfitTotal(), 2),
        );

        $this->json_data = $data;
        return true;
    }

}
