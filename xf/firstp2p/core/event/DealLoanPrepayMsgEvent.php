<?php
/**
 * 用于给出借人发送提前还款消息
 */
namespace core\event;

use core\event\BaseEvent;

use core\dao\DealPrepayModel;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\DealLoadModel;

use core\service\DealPrepayService;

class DealLoanPrepayMsgEvent extends BaseEvent
{
    private $_deal_prepay_id;

    public function __construct($deal_prepay_id) {
        $this->_deal_prepay_id = $deal_prepay_id;
    }

    public function execute()
    {
        $prepay = DealPrepayModel::instance()->find($this->_deal_prepay_id);
        $deal = DealModel::instance()->find($prepay->deal_id);
        // 还款期数统计
        $deal['repay_periods_sum'] = DealRepayModel::instance()->getDealRepayPeriodsSumByUserId($deal->id, $deal->user_id);
        $deal['repay_periods_order'] = DealRepayModel::instance()->getDealRepayPeriodsOrderByUserId($deal->id, $deal->user_id);

        $loan_user_id_collection = DealLoadModel::instance()->getDealLoanUserIdsExReservation($prepay->deal_id);
        $deal_prepay_service = new DealPrepayService();

        foreach ($loan_user_id_collection as $loan_user_id) {
            $deal_prepay_service->sendDealPrepayMessage($prepay, $deal, $loan_user_id);
        }

        return true;
    }

    public function alertMails() {
        return array('fanjingwen@ucfgroup.com');
    }
}
