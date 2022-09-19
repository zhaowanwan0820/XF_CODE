<?php
namespace core\service\intelligent;

use core\dao\IntelligentInvestmentModel;
use core\service\intelligent\IntelligentInvestment;
use core\service\intelligent\InvestorBase;
use core\service\UserReservationService;
use core\dao\UserReservationModel;
use core\dao\DealModel;
use libs\utils\Logger;

class RichmanInvestor extends InvestorBase
{
    public function __construct(IntelligentInvestment $ii)
    {
        parent::__construct($ii);
        $this->maxInvestorCount = IntelligentInvestmentModel::MAX_USER_NUM - $this->ii->investedUserTotal;
        $this->moneyLimit = bcdiv($this->ii->dealRemainMoney, $this->maxInvestorCount, 2);
        Logger::info('RichmanInvest_'.$this->ii->dealId.':'.join(',', [$this->maxInvestorCount, $this->moneyLimit]));
    }

    public function invest()
    {
        $accFund = 0;//公积金余额
        $floor = $this->moneyLimit;//初始起投

        while ($this->maxInvestorCount > 0) {
            $getRetail = ($floor <= DealModel::DEAL_MIN_LOAN_UNIT) ? true : false;
            if (!$bidMoney = $this->getRichmanMoney($getRetail)) {
                Logger::info('RichmanInvest Break');
                break;
            }

            //最后一口标
            if (bccomp($bidMoney, $this->ii->dealRemainMoney, 2) !== -1) {
                Logger::info('RichmanInvestLastOneInvest:'.join(',',[$this->ii->dealRemainMoney, $floor, $bidMoney]));
                $bidMoney = $this->ii->dealRemainMoney;
            } elseif (bccomp($this->ii->dealRemainMoney, bcadd($bidMoney, DealModel::DEAL_MIN_LOAN_UNIT, 2), 2) == -1) {
                Logger::info('RichmanInvestLastInvestMissMatch:'.join(',',[$this->ii->dealRemainMoney, $floor, $bidMoney]));
                continue;
            }

            Logger::info('RichmanInvest_start_bid_'.$this->ii->dealId.':'.join(',', [$this->maxInvestorCount, $bidMoney, $floor]));
            if (bccomp($bidMoney, $floor, 2) !== -1) {
                if ($this->bid($bidMoney)) {
                    $this->maxInvestorCount--;
                    $regulation = bcsub($bidMoney, $this->moneyLimit, 2);
                    $accFund = bcadd($accFund, $regulation, 2);

                    $floor = bcsub($this->moneyLimit, $accFund, 2);
                    $floor = (bccomp($floor, DealModel::DEAL_MIN_LOAN_UNIT, 2) == -1) ? DealModel::DEAL_MIN_LOAN_UNIT : $floor;
                    Logger::info('accFund:'.join(',', [$accFund, $regulation, $floor]));
                }
            } else {
                Logger::info('InvestFailed.');
            }

            if ($this->ii->dealRemainMoney <= 0) {
                Logger::info('InvestMoneyIsFull.DealRemain:'.$this->ii->dealRemainMoney);
                break;
            }

        }
        return true;
    }

    private function getRichmanMoney($getRetail = false)
    {
        $res = false;
        if ($getRetail) {
            $res = $this->getNextReserveMoney('retail');
        }
        if (!$res) {
            $res = $this->getNextReserveMoney('richman');
        }
        return $res;
    }

}
