<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestCustomers;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * Customers
 * 获取客户
 *
 * @uses BaseAction
 * @package default
 */
class Customers extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            'type' => array("filter" => "int", 'option' => array('optional' => true)),
            'pageNo' => array("filter" => "int", 'option' => array('optional' => true)),
            'pageSize' => array("filter" => "int", 'option' => array('optional' => true)),
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

        $pageNo = isset($data['pageNo']) ? $data['pageNo'] : 1;
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : 10;
        $type = isset($data['type']) ? intval($data['type']) : 0; // 默认在投客户

        $request = new RequestCustomers();
        $request->setPageable(new Pageable($pageNo, $pageSize));
        $request->setCfpId($userInfo->userId);
        $request->setType($type);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'getCustomers',
            'args' => $request
        ));

        $dataPage = $response->getDataPage();
        $customerProtos = $dataPage->getContent();
        $customers = array();
        foreach ($customerProtos as $proto) {
            $tmp = array();
            $tmp['userId'] = $proto->getUserId();
            $tmp['userName'] = $proto->getUserName();
            $tmp['realName'] = $proto->getRealName();
            $tmp['mobile'] = $proto->getMobile();
            $tmp['profitTotal'] = number_format($proto->getProfitTotal(), 2);
            $tmp['investingTotal'] = number_format($proto->getInvestingTotal() / 10000, 2);
            $tmp['latestDay'] = $proto->getLatestDay();
            $tmp['profitRatioAvg'] = $proto->getProfitRatioAvg();
            $tmp['periodAvg'] = $proto->getPeriodAvg();
            $tmp['investNum'] = $proto->getInvestNum();
            $tmp['pastDay'] = $proto->getPastDay();
            $tmp['neverInvest'] = $proto->getNeverInvest();
            $customers[] = $tmp;
        }

        $data = array();
        $data['pageNo'] = $dataPage->getPageNo();
        $data['pageSize'] = $dataPage->getPageSize();
        $data['totalPage'] = $dataPage->getTotalPage();
        $data['totalSize'] = $dataPage->getTotalSize();
        $data['customers'] = $customers;

        $this->json_data = $data;
        return true;
    }

}

