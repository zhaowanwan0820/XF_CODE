<?php

namespace task\controllers\dealrepay;

use core\dao\deal\DealModel;
use core\service\coupon\CouponService;
use libs\utils\Logger;
use task\controllers\BaseAction;

class ResetCoupon extends BaseAction
{
    public function invoke()
    {
        $params = json_decode($this->getParams(), true);
        try {
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $deal = DealModel::instance()->getDealInfo($dealId, true);

            $dealCoupon = CouponService::getCouponDealByDealId($dealId);
            if (!$dealCoupon) {
                throw new \Exception("优惠码设置信息获取失败deal_id:{$dealId}");
            }

            $coupon_res = CouponService::saveCouponDeal($dealId, $dealCoupon['rebate_days'], $dealCoupon['pay_type'], $dealCoupon['payAuto'], $deal['repay_start_time'], $deal->deal_status);

            if (!$coupon_res) {
                throw new \Exception('更新标优惠码返利天数失败');
            }
        } catch (\Exception $ex) {
            Logger::error(implode(',', array(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
