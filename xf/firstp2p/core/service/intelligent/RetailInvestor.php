<?php
namespace core\service\intelligent;

use core\dao\IntelligentInvestmentModel;
use core\dao\UserModel;
use core\service\intelligent\IntelligentInvestment;
use core\service\intelligent\InvestorBase;
use libs\utils\Logger;

class RetailInvestor extends InvestorBase
{
    public function __construct(IntelligentInvestment $ii)
    {
        parent::__construct($ii);
        $iInvestModel = new IntelligentInvestmentModel();
        $this->maxInvestorCount = $iInvestModel->getMaxCommonUserNum($this->ii->dealMoney);
        $this->moneyLimit = bcdiv($this->ii->dealMoney, IntelligentInvestmentModel::MAX_USER_NUM, 2);
        Logger::info('retailInvest_'.$this->ii->dealId.':'.join(',', [$this->maxInvestorCount, $this->moneyLimit]));
    }

    public function invest()
    {
        while ($this->maxInvestorCount > 0) {
            if (!$bidMoney = $this->getNextReserveMoney('retail')) {
                Logger::info('RetailInvest Break');
                break;
            }
            if (bccomp($bidMoney, $this->moneyLimit, 2) !== 1) {
                Logger::info('retail_Invest_bidMoney:'.$bidMoney);
                if ($this->bid($bidMoney)) {
                    $this->maxInvestorCount--;
                }
            }
        }
        return true;
    }
}
