<?php
namespace task\controllers\dealprepay;


use core\dao\deal\DealExtModel;
use core\dao\deal\DealModel;
use core\enum\DealExtEnum;
use core\service\coupon\CouponService;
use libs\utils\Logger;
use task\controllers\BaseAction;

class ResetCoupon extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info("Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $repayId = $params['repayId'];
            $deal = DealModel::instance()->getDealInfo($dealId,true);

            $dealCoupon = CouponService::getCouponDealByDealId($dealId);
            if(!$dealCoupon) {
                throw new \Exception("优惠码设置信息获取失败deal_id:{$dealId}");
            }

            // 优惠码结算时间为放款时结算：直接保存计算后得出的各项数据
            // 优惠码结算时间为还清时结算： 保存结算后的各项数据 并修改优惠码返利天数
            if($dealCoupon['pay_type'] == 1) {
                $rebate_days = floor((get_gmtime() - $deal['repay_start_time'])/86400); // 优惠码返利天数=操作日期-放款日期
                if($rebate_days <= 0) {
                    throw new \Exception("优惠码返利天数不能为负值:rebate_days:".$rebate_days);
                }

                $coupon_res = CouponService::saveCouponDeal($dealId, $rebate_days, $dealCoupon['pay_type'], $dealCoupon['pay_Auto'], $deal['repay_start_time'], $deal->deal_status);

                if(!$coupon_res){
                    throw new \Exception("更新标优惠码返利天数失败");
                }
            }

        }catch (\Exception $ex){
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}