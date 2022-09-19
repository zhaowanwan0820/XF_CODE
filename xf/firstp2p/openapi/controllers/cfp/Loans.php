<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestLoans;
use NCFGroup\Common\Extensions\Base\Pageable;

class Loans extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            'userId' => array("filter" => "int", 'message' => "指定客户"),
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
        $userId = $data['userId'];

        $request = new RequestLoans();
        $request->setPageable(new Pageable($pageNo, $pageSize));
        $request->setCfpId($userInfo->userId);
        $request->setUserId(intval($userId));
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'getLoans',
            'args' => $request
        ));

        $dataPage = $response->getDataPage();
        $protos = $dataPage->getContent();
        $loans = array();
        foreach ($protos as $proto) {
            $tmp = array();
            $tmp['dealId'] = $proto->getDealId();
            $tmp['dealName'] = $proto->getDealName();
            $tmp['dueDay'] = $proto->getDueDay();
            $tmp['total'] = number_format($proto->getTotal() / 10000, 2).'万';
            $tmp['dealRate'] = $proto->getDealRate();
            $tmp['loanAmount'] = number_format($proto->getLoanAmount(), 2).'元';
            $tmp['repayment'] = $proto->getRepayment();
            $loans[] = $tmp;
        }

        $data = array();
        $data['pageNo'] = $dataPage->getPageNo();
        $data['pageSize'] = $dataPage->getPageSize();
        $data['totalPage'] = $dataPage->getTotalPage();
        $data['totalSize'] = $dataPage->getTotalSize();
        $data['loans'] = $loans;

        $this->json_data = $data;
        return true;
    }

}

