<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestInvestRecord;
use NCFGroup\Common\Extensions\Base\Pageable;

class InvestRecord extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            'userId' => array("filter" => "int", 'option' => array('optional' => true)),
            'calProfit' => array("filter" => "int", 'option' => array('optional' => true)),
            'pageNo' => array("filter" => "int", 'option' => array('optional' => true)),
            'pageSize' => array("filter" => "int", 'option' => array('optional' => true)),
            'skeyDt' => array("filter" => "string", 'option' => array('optional' => true)),
            'skeySt' => array("filter" => "string", 'option' => array('optional' => true)),
            'skeyUser' => array("filter" => "string", 'option' => array('optional' => true)),
            'skeyDealName' => array("filter" => "string", 'option' => array('optional' => true)),
            'investMin' => array("filter" => "string", 'option' => array('optional' => true)),
            'investMax' => array("filter" => "string", 'option' => array('optional' => true)),
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
        $userId = isset($data['userId']) ? $data['userId'] : 0;
        $calProfit = isset($data['calProfit']) ? $data['calProfit'] : 0;
        $skeyDt = isset($data['skeyDt']) ? $data['skeyDt'] : '';
        $skeySt = isset($data['skeySt']) ? $data['skeySt'] : -1;
        $skeyUser = isset($data['skeyUser']) ? $data['skeyUser'] : '';
        $skeyDealName = isset($data['skeyDealName']) ? $data['skeyDealName'] : '';
        $skeyInvestMin = isset($data['investMin'])?$data['investMin']:''; 
        $skeyInvestMax = isset($data['investMax'])?$data['investMax']:'';

        $request = new RequestInvestRecord();
        $request->setPageable(new Pageable($pageNo, $pageSize));
        $request->setCfpId($userInfo->userId);
        $request->setUserId($userId);
        $request->setSkeyDt($skeyDt);
        $request->setSkeySt(intval($skeySt));
        $request->setSkeyUser($skeyUser);
        $request->setSkeyDealName($skeyDealName);
        $request->setCalProfit($calProfit);

        $request->setInvestMin($skeyInvestMin);
        $request->setInvestMax($skeyInvestMax);

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'getInvestRecord',
            'args' => $request
        ));

        $dataPage = $response->getDataPage();
        $protos = $dataPage->getContent();
        $commissions = array();
        foreach ($protos as $proto) {
            $tmp = array();
            $tmp['dealId'] = $proto->getDealId();
            $tmp['userName'] = $proto->getUserName();
            $tmp['realName'] = $proto->getRealName();
            $tmp['dealName'] = $proto->getDealName();
            //$tmp['dueDay'] = $proto->getDueDay();
            $tmp['dealInvestTime'] = $proto->getDuration();
            $tmp['dueDay'] = '-';
            $tmp['total'] = number_format($proto->getTotal(), 2);
            $tmp['dealRate'] = $proto->getDealRate();
            $tmp['loanAmount'] = number_format($proto->getLoanAmount(), 2).'元';
            $tmp['createTime'] = $proto->getCreateTime();
            $tmp['profitRate'] = $proto->getProfitRate();
            $tmp['profitStatus'] = $proto->getProfitStatus();
            $tmp['commission'] = $proto->getCommission();
            $tmp['investAmount'] = $proto->getInvestAmount();
            $commissions[] = $tmp;
        }

        $data = array();
        $data['pageNo'] = $dataPage->getPageNo();
        $data['pageSize'] = $dataPage->getPageSize();
        $data['totalPage'] = $dataPage->getTotalPage();
        $data['totalSize'] = $dataPage->getTotalSize();
        $data['benefit'] = $response->getCommission();
        $data['allInvest'] = $response->getInvestAmount();
        $data['commissions'] = $commissions;

        $this->json_data = $data;
        return true;
    }

}

