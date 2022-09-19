<?php
namespace core\service\repay;

use core\enum\AccountEnum;
use core\enum\DealRepayEnum;
use core\enum\UserAccountEnum;
use libs\utils\ABControl;
use NCFGroup\Common\Library\Idworker;
use core\service\repay\RepayMoneyLog;
use core\service\account\AccountService;

class DealRepayMoneyLog extends RepayMoneyLog {

    public function handleMoneyLog(){
        $this->daichongzhi();
        $this->indirect();
        $this->consultFee();
        $this->guaranteeFee();
        $this->payFee();
        $this->canalFee();
        $this->loanFee();
        $this->managementFee();
        $this->principalInterest();
    }

    public function principalInterest(){
        //$accountId = AccountService::getUserAccountId($this->repayUserId,$this->getUserAccountType('principal_interest'));
        if($this->repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG || $this->repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI){
            $accountId = AccountService::getUserAccountId($this->deal->user_id,UserAccountEnum::ACCOUNT_FINANCE);
        }else{
            $accountId = $this->getRepayAccountId();
        }

        if(!$accountId){
            throw new \Exception("未获取到账户ID userId:{$this->repayUserId}");
        }

        $repayMoney = $this->repay->principal + $this->repay->interest;
        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $accountId))){
            $isAsync = true;
        }

        if (!AccountService::changeMoney($accountId,$repayMoney, "偿还本息", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE,$isAsync, true, 0,$bizToken)) {
            throw new \Exception('还款失败-还款账户余额更新失败 userId:'.$this->repayUserId);
        }
        return $this;
    }
}
