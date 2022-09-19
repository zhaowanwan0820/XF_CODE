<?php
namespace core\service\intelligent;

use core\dao\IntelligentInvestmentModel;
use core\service\intelligent\IntelligentInvestment;
use core\service\UserReservationService;
use core\dao\UserReservationModel;
use core\dao\UserModel;
use core\dao\DealModel;
use libs\utils\Logger;

class InvestorBase
{
    //投资人数限制
    protected $maxInvestorCount = 0;

    //单笔投资标准
    protected $moneyLimit = 0;

    //IntelligentInvestmet对象实例
    protected $ii = null;

    //预约服务实例
    protected $reserveService = null;

    //预约列表
    protected $reserveList = ['retail' => [], 'richman' => []];

    //预约数据是否已经取光
    protected $reserveIsNull = ['retail' => false, 'richman' => false];

    //预约id
    protected $beginId = ['retail' => 0, 'richman' => 0];

    //当前预约
    protected $reserve = [];

    public function __construct(IntelligentInvestment $ii)
    {
        $this->ii = $ii;
        $this->reserveService = new UserReservationService();
        Logger::info('IntelligentStart.'.json_encode($ii));
    }

    public function invest() {}

    protected function bid($money)
    {
        $bidRes = $this->reserveService->processOne($this->reserve['id'], $this->ii->dealId, $this->reserve['user_id'], $money);
        Logger::info('InvestRes:'.json_encode($bidRes));
        if ($bidRes['respCode'] == UserReservationService::CODE_RESERVE_BID_SUCCESS) {
            $this->ii->dealRemainMoney = bcsub($this->ii->dealRemainMoney, $money, 2);
            $this->ii->investedUser[$this->reserve['user_id']] = $this->reserve['user_id'];
            $this->ii->investedUserTotal++;
            Logger::info('InvestSuccess:'.json_encode($this->ii));
            return true;
        }
        return false;
    }

    protected function getNextReserveMoney($type)
    {
        Logger::info('getNextReserveMoney:'.$type);
        if (!in_array($type, ['retail', 'richman'])) {
            return false;
        }
        if (empty($this->reserveList[$type])) {
            if ($this->reserveIsNull[$type]) {
                return false;
            }

            $investDeadlineArray = $this->reserveService->getInvestDeadlineByDeal($this->ii->deal);
            $deadline = isset($investDeadlineArray['invest_deadline']) ? $investDeadlineArray['invest_deadline'] : 0;
            $deadlineUnit = isset($investDeadlineArray['invest_deadline_unit']) ? $investDeadlineArray['invest_deadline_unit'] : 0;

            $userReservationModel = new UserReservationModel();
            switch ($type) {
                case 'retail':
                    $this->reserveList[$type] = $userReservationModel->getUserReserveListByLimit(time(), IntelligentInvestmentModel::RESERVE_LIST_SELECT_LIMIT, $this->beginId[$type], $deadline, $deadlineUnit, $this->ii->deal['deal_type'], DealModel::DEAL_MIN_LOAN_UNIT, $this->moneyLimit);
                    break;
                case 'richman':
                    $this->reserveList[$type] = $userReservationModel->getUserReserveListByLimit(time(), IntelligentInvestmentModel::RESERVE_LIST_SELECT_LIMIT, $this->beginId[$type], $deadline,$deadlineUnit, $this->ii->deal['deal_type'], $this->moneyLimit, 0);
                    break;
            }

            if (count($this->reserveList[$type]) < IntelligentInvestmentModel::RESERVE_LIST_SELECT_LIMIT) {
                $this->reserveIsNull[$type] = true;
            }
        }

        if (!$this->reserve = array_shift($this->reserveList[$type])) {
            return false;
        }
        Logger::info('getReserveInfo:'.json_encode($this->reserve));
        if ($this->reserve['id'] > $this->beginId[$type]) {
            $this->beginId[$type] = $this->reserve['id'] ;
        }
        if (isset($this->ii->investedUser[$this->reserve['user_id']])) {
            return $this->getNextReserveMoney($type);
        }
        $user = UserModel::instance()->find($this->reserve['user_id']);
        $validMoney = $this->reserveService->getEffectReserveAmount($user, $this->reserve, true);
        Logger::info('getValidMoney:'.$validMoney);
        if (!$validMoney) {
            $this->getNextReserveMoney($type);
        }
        return $validMoney;
    }
}
