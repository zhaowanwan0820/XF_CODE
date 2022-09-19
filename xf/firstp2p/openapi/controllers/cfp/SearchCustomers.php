<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestSearchCustomers;
use NCFGroup\Common\Extensions\Base\Pageable;
class SearchCustomers extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            'skey' => array("filter" => "string", 'option' => array('optional' => true)),
            'type' => array("filter" => "int", 'option' => array('optional' => true)),

            'bidRepayDayMin' => array("filter" => "int", 'option' => array('optional' => true)),
            'bidRepayDayMax' => array("filter" => "int", 'option' => array('optional' => true)),
            'bidYearrate' => array("filter" => "int", 'option' => array('optional' => true)),
            'bidRepayLimitTime' => array("filter" => "int", 'option' => array('optional' => true)),
            'benefitMoneyMin' => array("filter" => "int", 'option' => array('optional' => true)),
            'benefitMoneyMax' => array("filter" => "int", 'option' => array('optional' => true)),
            'pageSize' => array("filter" => "int", 'option' => array('optional' => true)),
            'page' => array("filter" => "int", 'option' => array('optional' => true)),
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
        $skey = isset($data['skey']) ? $data['skey'] : '';
        $type = isset($data['type']) ? $data['type'] : 0;
        $bidRepayDayMin = isset($data['bidRepayDayMin']) ? $data['bidRepayDayMin'] : 0;
        $bidRepayDayMax = isset($data['bidRepayDayMax']) ? $data['bidRepayDayMax'] : 0;
        $bidYearrate = isset($data['bidYearrate']) ? $data['bidYearrate'] : 0;
        $bidRepayLimitTime = isset($data['bidRepayLimitTime']) ? $data['bidRepayLimitTime'] : 0;
        $benefitMoneyMin = isset($data['benefitMoneyMin']) ? $data['benefitMoneyMin'] : 0;
        $benefitMoneyMax = isset($data['benefitMoneyMax']) ? $data['benefitMoneyMax'] : 0;
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : 10;
        $pageNo = isset($data['page']) ? $data['page'] : 1;

        $request = new RequestSearchCustomers();
        $request->setCfpId($userInfo->userId);
        $request->setType(intval($type));
        $request->setSkey(intval($skey));
        $request->setBidRepayDayMin(intval($bidRepayDayMin));
        $request->setBidRepayDayMax(intval($bidRepayDayMax));
        $request->setBidYearrate(intval($bidYearrate));
        $request->setBidRepayLimitTime(intval($bidRepayLimitTime));
        $request->setBenefitMoneyMin(intval($benefitMoneyMin));
        $request->setBenefitMoneyMax(intval($benefitMoneyMax));
        $request->setPageable(new Pageable($pageNo, $pageSize));

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'searchCfpCustomers',
            'args' => $request
        ));

        $dataPage = $response->getDataPage();
        $customersSrc = $dataPage->getContent();
        $customers = array();
        foreach ($customersSrc as $proto) {
            $tmp = array();
            $tmp['userId'] = $proto->getUserId();
            $tmp['userName'] = $proto->getUserName();
            $tmp['realName'] = $proto->getRealName();
            $tmp['mobile'] = $proto->getMobile();
            $customers[] = $tmp;
        }

        $this->json_data = $customers;
        return true;
    }

}

