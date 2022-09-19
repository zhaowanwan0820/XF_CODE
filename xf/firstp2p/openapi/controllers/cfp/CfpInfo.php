<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestUser;

class CfpInfo extends BaseAction {

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
        $request->setCfpId($userInfo->userId);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'getCfpInfo',
            'args' => $request
        ));

        $data = array(
            'userId' => $response->getUserId(),
            'userName' => $response->getUserName(),
            'email' => $response->getEmail(),
            'realName' => $response->getRealName(),
            'mobileShow' => $response->getMobileShow(),
            'mobile' => $response->getMobile(),
            'profitTotal' => number_format($response->getProfitTotal(), 2),
            'beenSettled' => number_format($response->getBeenSettled(), 2),
            'tobeSettled' => number_format($response->getTobeSettled(), 2),
            'customerNum' => $response->getCustomerNum(),
            'investingNum' => $response->getInvestingNum(),
            'couponStr' => $response->getCouponStr(),
            'couponInofStr' => $response->getCouponInfoStr(),
        );

        $this->json_data = $data;
        return true;
    }

}
