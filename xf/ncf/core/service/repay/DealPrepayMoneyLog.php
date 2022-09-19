<?php
namespace core\service\repay;


use core\enum\AccountEnum;
use core\enum\DealRepayEnum;
use core\enum\UserAccountEnum;
use NCFGroup\Common\Library\Idworker;
use core\service\repay\RepayMoneyLog;
use core\service\account\AccountService;

class DealPrepayMoneyLog extends RepayMoneyLog{

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
        if($this->repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG || $this->repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI){
            $accountId = AccountService::getUserAccountId($this->deal->user_id,UserAccountEnum::ACCOUNT_FINANCE);
        }else{
            $accountId = $this->getRepayAccountId();
        }
        if(!$accountId){
            throw new \Exception("未获取到账户ID userId:{$this->repayUserId}");
        }
        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);
        $repayMoney = $this->repay->remain_principal + $this->repay->prepay_interest;
        if (!AccountService::changeMoney($accountId,$repayMoney, "提前还款", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, false, true, 0, $bizToken)) {
            throw new \Exception('提前还款-还款账户余额更新失败 userId:'.$this->repayUserId);
        }

        // 扣除提前还款补偿金
        $prepayCompensation = $this->repay->prepay_compensation;
        if($prepayCompensation > 0 ){
            if (!AccountService::changeMoney($accountId,$prepayCompensation, "提前还款补偿金", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, false, true, 0, $bizToken)) {
                throw new \Exception('提前还款-提前还款补偿金更新失败 userId:'.$this->repayUserId);
            }
        }
    }
}
