<?php
namespace core\service\repay;

use core\enum\DealRepayEnum;
use core\service\deal\DealService;
use core\service\repay\DealRepayMoneyLog;
use core\service\repay\DealPrepayMoneyLog;
use libs\utils\Logger;

class RepayMoneyLogRoute {

    private static $container;

    /**
     * 还款类型 正常、提前、部分
     * @param $repayType
     * @param $repayAccountType
     */
    public static function handleMoneyLog($deal,$repay,$repayType,$repayAccountType,$partRepayInfo = []){
        self::$container = self::getContainer($deal,$repay,$repayType,$repayAccountType);
        self::initContainer($deal,$repay,$repayType,$repayAccountType,$partRepayInfo);
        return self::$container->handleMoneyLog();
    }

    public static function getContainer($deal,$repay,$repayType,$repayAccountType){
        $container = array(
            DealRepayEnum::DEAL_REPAY_NORMAL => '\core\service\repay\DealRepayMoneyLog',
            DealRepayEnum::DEAL_REPAY_PREPAY => '\core\service\repay\DealPrepayMoneyLog',
            DealRepayEnum::DEAL_REPAY_PART   => '\core\service\repay\DealPartMoneyLog',
            DealRepayEnum::DEAL_REPAY_PREYAY_DZH => '\core\service\repay\DealPrepayDZHMoneyLog',
            DealRepayEnum::DEAL_REPAY_NORMAL_PART => '\core\service\repay\DealPartRepayMoneyLog',
        );
        return new $container[$repayType]($deal,$repay,$repayAccountType);
    }

    public static function initContainer($deal,$repay,$repayType,$repayAccountType,$partRepayInfo){
        if($repayType == DealRepayEnum::DEAL_REPAY_NORMAL){
            //$totalRepayMoney = $repay->principal + $repay->interest + $repay->loan_fee + $repay->consult_fee + $repay->guarantee_fee + $repay->pay_fee + $repay->management_fee + $repay->canal_fee;
            $totalRepayMoney = $repay->repay_money;
            self::$container->setTotalRepayMoney($totalRepayMoney);
        }elseif($repayType == DealRepayEnum::DEAL_REPAY_PREPAY){
            $totalRepayMoney = $repay->prepay_money;
            self::$container->setTotalRepayMoney($totalRepayMoney);
        }elseif($repayType == DealRepayEnum::DEAL_REPAY_NORMAL_PART) {
            self::$container->partRepayInfo = $partRepayInfo;
            self::$container->setTotalRepayMoney($partRepayInfo['totalRepayMoney']);
        }
    }

}
