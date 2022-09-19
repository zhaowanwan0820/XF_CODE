<?php
namespace core\service\repay;

use core\dao\deal\DealLoadModel;
use core\dao\deal\DealModel;
use core\dao\repay\DealLoanRepayModel;
use core\dao\repay\DealPrepayModel;
use core\dao\repay\DealRepayModel;
use core\enum\DealLoanTypeEnum;
use core\enum\UserEnum;
use core\service\deal\DealService;
use core\service\user\UserService;
use libs\sms\SmsServer;
use core\service\BaseService;

class DealPrepayMsgService extends BaseService {

    public static function sendMsgBox($dealId,$repayId){
        $prepay = DealPrepayModel::instance()->find($repayId);
        $deal = DealModel::instance()->getDealInfo($dealId);
        // 还款期数统计
        $deal['repay_periods_sum']   = DealRepayModel::instance()->getDealRepayPeriodsSumByUserId($deal->id, $deal->user_id);
        $deal['repay_periods_order'] = DealRepayModel::instance()->getDealRepayPeriodsOrderByUserId($deal->id, $deal->user_id);

        $loan_user_id_collection = DealLoadModel::instance()->getDealLoanUserIdsExReservation($prepay->deal_id);
        $deal_prepay_service = new DealPrepayService();

        foreach ($loan_user_id_collection as $loan_user_id) {
            $deal_prepay_service->sendDealPrepayMessage($prepay, $deal, $loan_user_id);
        }
        return true;
    }
}