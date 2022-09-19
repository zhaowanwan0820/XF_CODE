<?php
namespace core\service\intelligent;

use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\IntelligentInvestmentModel;
use core\service\ReservationDealService;
use libs\utils\Logger;

class IntelligentInvestment
{
    const MAX_INVESTOR = 200;

    //已投资用户数
    public $investedUserTotal = 0;
    //标的剩余金额
    public $dealRemainMoney = 0;
    //已投资用户ID
    public $investedUser = [];
    //标的金额
    public $dealMoney = 0;
    //标的信息
    public $deal;
    //标的Id
    public $dealId;

    private $_locker = 'intelligent_invest_lock_';

    public function __construct($deal)
    {
        if (empty($deal['id'])) {
            throw new \Exception('Deal is null');
        }

        $this->_locker = $this->_locker . $deal['id'];

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $lockRes = $redis->setNX($this->_locker, $deal['id']);
        if (!$lockRes) {
            Logger::error("Deal_is_Locked_".$deal['id']);
            throw new \Exception('Deal is Locked');
        } else {
            $redis->expire($this->_locker, 600);
        }

        $this->deal = [
                'id' => $deal['id'],
                'repay_time' => $deal['repay_time'],
                'loantype' => $deal['loantype'],
                'deal_status' => $deal['deal_status'],
                'borrow_amount' => $deal['borrow_amount'],
                'deal_type' => $deal['deal_type'],
                'is_float_min_loan' => $deal['is_float_min_loan'],
            ];

        $this->_setInvestData();
        Logger::info("intelligent_invest_start_".$deal['id']);
    }

    public function invest()
    {
        if(!$this->isDealValid($this->deal)) {
            return $this;
        }

        $retailInvest = new RetailInvestor($this);
        $retailInvest->invest();

        $richmanInvest = new RichmanInvestor($this);
        $richmanInvest->invest();

        return $this;
    }

    public function publish()
    {
        //发布到线上
        $reservationDealService = new ReservationDealService();
        $reservationDealService->updateReserveDealWaiting($this->dealId);
        return $this;
    }

    /**
     * 检查标的是否有效
     * @param $deal
     */
    public function isDealValid($deal)
    {
        $loadCount = DealLoadModel::instance()->getLoadCount($this->dealId);

        /**
         * 不可智投标的情况
         * 1、已经有投资
         * 2、状态不在预约中
         * 3、不是专享或者交易所的标的
         * 4、未关闭浮动起投
         **/
        if (($loadCount['buy_count'] > 0) ||
            ($deal['deal_status'] != DealModel::$DEAL_STATUS['reserving']) ||
            (!in_array($deal['deal_type'], [DealModel::DEAL_TYPE_EXCLUSIVE, DealModel::DEAL_TYPE_EXCHANGE])) ||
            ($deal['is_float_min_loan'] == DealModel::DEAL_FLOAT_MIN_LOAN_MONEY_YES) ) {
            Logger::error("Deal_is_not_valid:".var_export($deal, true));
            return false;
        }
        return true;
    }

    public function __destruct()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redis->del($this->_locker);
    }

    private function _setInvestData()
    {
        $this->investedUserTotal = 0;
        $this->dealRemainMoney = $this->deal['borrow_amount'];
        $this->dealMoney = $this->deal['borrow_amount'];
        $this->dealId = $this->deal['id'];
    }

}
