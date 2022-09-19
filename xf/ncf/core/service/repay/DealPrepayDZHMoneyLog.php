<?php
namespace core\service\repay;

use core\dao\repay\PartialRepayModel;
use core\enum\AccountEnum;
use core\enum\DealRepayEnum;
use core\enum\PartialRepayEnum;
use core\enum\UserAccountEnum;
use core\service\account\AccountService;
use core\service\repay\RepayMoneyLog;
use core\service\user\UserService;
use NCFGroup\Common\Library\Idworker;

class DealPrepayDZHMoneyLog extends RepayMoneyLog
{

    private $partialRepayModel;

    private $borrowerUser;

    private $borrowerAccountId;

    private $rechargeUser;

    private $rechargeAccountId;

    public function __construct($deal, $repay, $repayAccountType)
    {
        parent::__construct($deal, $repay, $repayAccountType);
        $this->partialRepayModel = new PartialRepayModel();
        $this->borrowerUser      = UserService::getUserById($this->deal->user_id);
        $this->borrowerAccountId = AccountService::getUserAccountId($this->deal->user_id, UserAccountEnum::ACCOUNT_FINANCE);
        $rechargeUserId          = $this->dealService->getRepayUserAccount($deal['id'], DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI);
        $this->rechargeUser      = UserService::getUserById($rechargeUserId);
        $this->rechargeAccountId = AccountService::getUserAccountId($rechargeUserId, UserAccountEnum::ACCOUNT_RECHARGE);

        if(!$this->borrowerAccountId || !$this->rechargeAccountId) {
            throw new \Exception('未获取到部分还款账户ID');
        }
    }

    public function handleMoneyLog()
    {
        return $this->recharge()->canalFee()->payFee()->guaranteeFee()->consultFee()->loanFee()->principalInterest();
    }

    public function principalInterest()
    {
        $this->addRepayMoneyLog("偿还本息",array(PartialRepayEnum::FEE_TYPE_PRINCIPAL,PartialRepayEnum::FEE_TYPE_INTEREST,PartialRepayEnum::FEE_TYPE_COMPEN));
        return $this;
    }

    public function canalFee()
    {
        if($this->repay->canal_fee <=0){
            return $this;
        }
        $this->addRepayMoneyLog("渠道服务费",PartialRepayEnum::FEE_TYPE_QD);
        return $this;
    }

    public function payFee()
    {
        if($this->repay->pay_fee <=0)
        {
            return $this;
        }
        $this->addRepayMoneyLog("支付服务费",PartialRepayEnum::FEE_TYPE_FW);
        return $this;
    }

    public function guaranteeFee()
    {
        if($this->repay->guarantee_fee <=0) {
            return $this;
        }
        $this->addRepayMoneyLog("担保费",PartialRepayEnum::FEE_TYPE_DB);
        return $this;
    }

    public function consultFee()
    {
        if($this->repay->consult_fee <=0) {
            return $this;
        }
        $this->addRepayMoneyLog("咨询费",PartialRepayEnum::FEE_TYPE_ZX);
        return $this;
    }

    public function loanFee()
    {
        if($this->repay->loan_fee <=0) {
            return $this;
        }
        $this->addRepayMoneyLog("平台手续费", PartialRepayEnum::FEE_TYPE_SX);
        return $this;
    }

    public function recharge()
    {
        $feeTypes = [
            PartialRepayEnum::FEE_TYPE_PRINCIPAL,
            PartialRepayEnum::FEE_TYPE_INTEREST,
            PartialRepayEnum::FEE_TYPE_COMPEN,
            PartialRepayEnum::FEE_TYPE_QD,
            PartialRepayEnum::FEE_TYPE_FW,
            PartialRepayEnum::FEE_TYPE_DB,
            PartialRepayEnum::FEE_TYPE_ZX,
            PartialRepayEnum::FEE_TYPE_SX
        ];
        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);
        $rechargeRepayMoney = $this->partialRepayModel->getPrepayMoney($this->repay->id, PartialRepayEnum::REPAY_TYPE_COMPENSATORY, $feeTypes);
        if(bccomp($rechargeRepayMoney,'0.00',2) == 1) {
            //扣减代偿机构的钱
            if (!AccountService::changeMoney($this->rechargeAccountId,$rechargeRepayMoney, "代充值扣款","编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, false, true, 0, $bizToken)) {
                throw new \Exception("还款扣除{$logType}失败");
            }
            //借款人充值
            if (!AccountService::changeMoney($this->borrowerAccountId, $rechargeRepayMoney, "代充值","编号".$this->deal['id'].' '.$this->deal['name'], AccountEnum::MONEY_TYPE_INCR, false, true, 0, $bizToken)) {
                throw new \Exception("还款扣除{$logType}失败");
            }
        }
        return $this;
    }

    /**
     * 记录资金记录
     */
    public function addRepayMoneyLog($logType,$feeTypes)
    {
        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);

        //扣减借款人的钱
        $rechargeRepayMoney = $this->partialRepayModel->getPrepayMoney($this->repay->id, PartialRepayEnum::REPAY_TYPE_COMPENSATORY, $feeTypes);
        $borrowerRepayMoney = $this->partialRepayModel->getPrepayMoney($this->repay->id, PartialRepayEnum::REPAY_TYPE_BORROWER, $feeTypes);
        if(bccomp($rechargeRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            if (!AccountService::changeMoney($this->borrowerAccountId, $rechargeRepayMoney, $logType,"编号".$this->deal['id'].' '.$this->deal['name'], AccountEnum::MONEY_TYPE_REDUCE, false, true, 0, $bizToken)) {
                throw new \Exception("还款扣除{$logType}失败");
            }
        }

        if(bccomp($borrowerRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            if (!AccountService::changeMoney($this->borrowerAccountId, $borrowerRepayMoney, $logType,"编号".$this->deal['id'].' '.$this->deal['name'], AccountEnum::MONEY_TYPE_REDUCE, false, true, 0, $bizToken)) {
                throw new \Exception("还款扣除{$logType}失败");
            }
        }

        if(is_array($feeTypes)) {
            return true;
        }
        //收费机构收钱
        $feeUser = null;
        switch ($feeTypes) {
            case PartialRepayEnum::FEE_TYPE_SX:
                $fee_user_id = \core\dao\deal\DealAgencyModel::instance()->getLoanAgencyUserId($this->deal['id']);
                $accountId = AccountService::getUserAccountId($fee_user_id,$this->getUserAccountType('loan_fee'));
                break;
            case PartialRepayEnum::FEE_TYPE_ZX:
                $advisory_info = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['advisory_id']); // 咨询机构
                $fee_user_id = $advisory_info['user_id']; // 咨询机构账户
                $accountId = AccountService::getUserAccountId($fee_user_id,$this->getUserAccountType('consult_fee'));
                break;
            case PartialRepayEnum::FEE_TYPE_DB:
                $agency_info = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['agency_id']); // 咨询机构
                $fee_user_id = $agency_info['user_id']; // 担保机构账户
                $accountId = AccountService::getUserAccountId($fee_user_id,$this->getUserAccountType('guarantee_fee'));
                break;
            case PartialRepayEnum::FEE_TYPE_FW:
                $pay_user_info = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['pay_agency_id']); // 支付机构
                $fee_user_id = $pay_user_info['user_id']; // 支付机构账户
                $accountId = AccountService::getUserAccountId($fee_user_id,$this->getUserAccountType('pay_fee'));
                break;
            case PartialRepayEnum::FEE_TYPE_QD:
                $canal_user_info = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['canal_agency_id']); // 渠道机构
                $fee_user_id = $canal_user_info['user_id']; // 渠道机构账户
                $accountId = AccountService::getUserAccountId($fee_user_id,$this->getUserAccountType('canal_fee'));
                break;
        }

        if(!$accountId){
            throw new \Exception("未获取到账户ID userId:{$fee_user_id}");
        }

        if(bccomp($rechargeRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            $borrowerNote = "编号{$this->deal['id']} {$this->deal['name']} 借款人ID{$this->borrowerUser['id']} 借款人姓名{$this->borrowerUser['real_name']}";
            if (!AccountService::changeMoney($accountId, $rechargeRepayMoney, $logType,$borrowerNote,AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken)) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }

        if(bccomp($borrowerRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            $borrowerNote = "编号{$this->deal['id']} {$this->deal['name']} 借款人ID{$this->borrowerUser['id']} 借款人姓名{$this->borrowerUser['real_name']}";
            if (!AccountService::changeMoney($accountId, $borrowerRepayMoney, $logType,$borrowerNote,AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken)) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }
        return true;
    }
}
