<?php
/**
 * 生成前置合同链接
 * User: yangshuo
 */
namespace openapi\controllers\retail;

use core\enum\UserAccountEnum;
use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\user\BankService;
use core\service\account\AccountService;
use core\service\contract\ContractBeforeBorrowService;
use core\service\supervision\SupervisionAccountService;
use openapi\lib\Tools;

class CreateEarlierContractsUrl extends BaseAction {

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "approveNumber" => array("filter" => "required", "message" => "approveNumber is required"),
            "repayPeriod" => array("filter" => "int", "message" => "repayPeriod is required"),        //借款期限(天)
            "repayPeriodType" => array("filter" => "int", "message" => "repayPeriodType is required"),     //借款期限类型(1:天,2:月)
            "borrowAmount" => array("filter" => "required", "message" => "borrowAmount is required"),   //借款金额(元)
            "contractTplType" => array("filter" => "int", "message" => "contractTplType is required"),//合同类型
            "loanMoneyType" => array("filter" => "int", "message" => "loanMoneyType is required"),    //放款方式-1实际放款(默认)2非实际放款 3受托支付
            "callBackUrl" => array("filter" => "required", "message" => "callBackUrl is required"),
            // 第三方异步通知url地址
            "orderNotifyUrl" => array("filter" => "string"),
            "wx_open_id" => array("filter" => "required", "message" => "wx_open_id is required"),          //用户id
            "type_id" => array("filter" => "int", "message" => "type_id is required"),                //产品类型
            "entrustName" => array("filter" => "string"),     //受托方户名
            "loanBankCard" => array("filter" => "string"),    //受托方账号
            "bankZone" => array("filter" => "string"),      //受托方开户行（联行号）
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

    }

    public function invoke()
    {
        $params = $this->form->data;

        //若放款方式为受托支付，则受托方用户名、受托方账号、受托方开户行为必填项。
        if ($params['loanMoneyType'] == 3) {
            if (empty($params['entrustName'])) {
                $this->setErr("ERR_PARAMS_ERROR", 'entrustName 不能为空');
                return false;
            }
            if (empty($params['loanBankCard'])) {
                $this->setErr("ERR_PARAMS_ERROR", 'loanBankCard 不能为空');
                return false;
            }
            if (empty($params['bankZone'])) {
                $this->setErr("ERR_PARAMS_ERROR", 'bankZone 不能为空');
                return false;
            }
        }
        //受托方开户行是否存在
        if (!empty($params['bankZone'])) {
            $bankList = BankService::getBranchInfoByBranchNo($params['bankZone']);
            if (empty($bankList)) {
                $this->setErr("ERR_BANK_NOT_FOUND");
                return false;
            }
        }
        //借款期限类型校验
        if (!in_array($params['repayPeriodType'], [1,2])) {
            $this->setErr("ERR_REPAY_PERIOD_TYPE_ILLEGAL");
            return false;
        }

        //是否开户
        $userId = Tools::getUserIdByOpenID($params['wx_open_id']);
        //wx_open_id校验
        if ($userId == false) {
            $this->setErr("ERR_USER_NOT_FOUND");
            return false;
        }
        $accountService = new AccountService();
        $accountId = $accountService::getUserAccountId($userId,UserAccountEnum::ACCOUNT_FINANCE);
        if (!$accountId) {
            $this->setErr("ERR_USER_NOT_FOUND");
            return false;
        }
        $supervisionAccountService = new SupervisionAccountService();
        $isSupervision = $supervisionAccountService->isSupervisionUser($userId);
        if (!$isSupervision) {
            $this->setErr("ERR_SUPERVISION_NOACCOUNT");
            return false;
        }
        //校验放款审批单在临时表中是否存在
        $contractResponse = ContractBeforeBorrowService::getContractByApproveNumber($params['approveNumber']);
        if(empty($contractResponse)) {
            $contractParams = array(
                "loanMoneyType" => $params['loanMoneyType'],
                "repayPeriod" => $params['repayPeriod'],
                "repayPeriodType" => $params['repayPeriodType'],
                "borrowAmount" => $params['borrowAmount'],
                "type_id" => $params['type_id'],
                "entrustName" => $params['entrustName'],
                "loanBankCard" => $params['loanBankCard'],
                "bankZone" => $params['bankZone'],
                "orderNotifyUrl" => empty($params['orderNotifyUrl']) ? '' : $params['orderNotifyUrl'],

            );
            $insertResponse = ContractBeforeBorrowService::insertBeforeBorrowContract($params['approveNumber'],intval($userId),intval($params['contractTplType']),json_encode($contractParams));
        } elseif ($contractResponse['borrowerSignTime'] > 0) {//根据放款审批单号在合同临时表中校验是否存在。
            $this->setErr("ERR_CONTRACT_SIGNED",'该标的合同已签署');
            return false;
        }elseif ($contractResponse['borrowerSignTime'] <= 0) {
            $req = ['contractId' => $contractResponse['id'], 'callBackUrl' => $params['callBackUrl']];
            $result['url'] = $this->getHost()."/contract/GetTmpContracts?".$this->getOpenapiUrl($req);
            $this->json_data_err = $result;
            $this->setErr("ERR_CONTRACT_NOT_SIGNED");
            return true;
        }

        $req = ['contractId' => $insertResponse, 'callBackUrl' => $params['callBackUrl']];
        $result['url'] = $this->getHost()."/contract/GetTmpContracts?".$this->getOpenapiUrl($req);
        $this->json_data = $result;
    }

}