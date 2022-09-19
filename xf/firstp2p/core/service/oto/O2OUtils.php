<?php

namespace core\service\oto;

use core\exception\O2OException;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\CompoundRedemptionApplyModel;
use core\service\O2OService;
use core\service\CouponService;
use core\service\CouponBindService;
use libs\utils\PaymentApi;
use libs\utils\Finance;
use libs\utils\Logger;
use core\dao\OtoAcquireLogModel;

class O2OUtils {
    // 还款方式
    const LOAN_TYPE_5 = 5;//按天一次性还款
    const LOAN_TYPE_BY_CROWDFUNDING = 7; // 公益标

    /**
     * 获取交易的年化额
     */
    public static function getAnnualizedAmountByDealLoadId($dealLoadId, $slave = true) {
        $dealoadColumns = 'deal_id, money';
        if ($slave) {
            $dealLoadInfo = DealLoadModel::instance()->findViaSlave($dealLoadId, $dealoadColumns);
        } else {
            $dealLoadInfo = DealLoadModel::instance()->find($dealLoadId, $dealoadColumns);
        }

        if (empty($dealLoadInfo)) {
            return false;
        }

        // 计算投资年化
        $dealId = intval($dealLoadInfo['deal_id']);
        $dealInfo = DealModel::instance()->find($dealId, 'rate, repay_time, loantype, deal_type, repay_start_time');
        if (empty($dealInfo)) {
            return false;
        }

        // 通知贷单独计算时间
        if ($dealInfo['deal_type'] == DealModel::DEAL_TYPE_COMPOUND) {
            if ($dealInfo['repay_start_time'] == 0) {
                return false;
            }

            $apply = CompoundRedemptionApplyModel::instance()->getApplyByDealLoanId($dealLoadId);
            if (!$apply) {
                return false;
            }
            $dealInfo['repay_time'] = ($apply['repay_time'] - $dealInfo['repay_start_time']) / 86400;
        }

        // 先用
        if ($dealInfo['loantype'] == self::LOAN_TYPE_5) {
            $divideRate = $dealInfo['repay_time'] / 360;//360天,金融领域年周期为360天
        } else {
            $divideRate = $dealInfo['repay_time'] / 12;//12月
        }

        $rebateRate = $dealInfo->getRebateRate($dealInfo->loantype);
        $annualizedAmount = round($dealLoadInfo['money'] * $divideRate * $rebateRate, 2); //不乘利率
        return $annualizedAmount;
    }

    /**
     * 获取投资年化额(除通知贷),投资券确认页面用
     */
    public static function getAnnualizedAmountByDealIdAndAmount($dealId, $amount) {
        $dealModel = DealModel::instance()->findViaSlave($dealId);
        if (empty($dealModel)) {
            PaymentApi::log("O2OUtils.getAnnualizedAmountByDealIdAndAmount 获取投资年化额找不到交易记录, dealId:{$dealId}, amount:{$amount}", Logger::ERR);
            return 0;
        }
        $finance = new Finance();
        // 计算年化额
        $moneyYear = $finance->getMoneyYearPeriod($amount, $dealModel->loantype, $dealModel->repay_time);
        $rebateRate = $dealModel->getRebateRate($dealModel->loantype);
        $annualizedAmount = round(bcmul($moneyYear , $rebateRate, 2), 2);
        return $annualizedAmount;
    }


    public static function getReferId($userId, $triggerMode, $dealLoadId = 0, $dealType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        PaymentApi::log("O2OUtils 获取用户绑定关系，user|$userId |triggerMode| $triggerMode |dealLoadId| $dealLoadId");
        if (empty($userId)) return 0;

        $referUserId = 0;
        if (in_array($triggerMode, CouponGroupEnum::$TRIGGER_DEAL_MODES) && $dealType == CouponGroupEnum::CONSUME_TYPE_P2P) {
            // 从交易里获取邀请人信息
            $condition =" id = $dealLoadId AND user_id = $userId" ;
            $dealLoadInfo = DealLoadModel::instance()->findBy($condition, 'short_alias');
            if ($dealLoadInfo['short_alias']) {
                $couponService = new CouponService();
                $referUserId = $couponService->getReferUserId($dealLoadInfo['short_alias']);
            }
        }
        if (empty($referUserId)) {
            // 勋章等其他类型的奖励，获取邀请人信息
            $coupon_bind_service = new CouponBindService();
            $coupon_bind = $coupon_bind_service->getByUserId($userId);
            PaymentApi::log("O2OUtils 获取用户绑定关系，user|$userId".json_encode($coupon_bind));
            if (!empty($coupon_bind)) {
                $short_alias = $coupon_bind['short_alias'];
                $couponService = new CouponService();
                $referUserId = $couponService->getReferUserId($short_alias);
            }
        }
        return $referUserId;
    }
}
