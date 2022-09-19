<?php
namespace task\controllers\dealcreate;

use core\dao\deal\DealExtModel;
use core\enum\DealEnum;
use core\enum\DealExtEnum;
use task\controllers\BaseAction;
use core\service\deal\DealService;
use libs\utils\Logger;
use core\service\coupon\CouponService;

/**
 * 上标完成之后的邀请码初始化服务
 * Class Create
 * @package task\controllers\dealcreate
 */
class InitCoupon extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $dealExtModel = DealExtModel::instance()->getDealExtByDealId($dealId);
            $loanFeeRateType = $dealExtModel->loan_fee_rate_type;

            // 0.放款时结算；1.还清时结算
            // 信贷固定比例前收、固定比例分期收，系统自动关联优惠码选择结算时间为放款时结算
            // 固定比例后收，系统自动关联优惠码选择结算时间为还清时结算
            if($loanFeeRateType == DealExtEnum::FEE_RATE_TYPE_BEHIND || $loanFeeRateType == DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND){
                $payType = 1;
            } else{
                $payType = 0;
            }
            $payAuto = 1;

            $repayReriod = $params['repay_period'];
            $rebateDays = ($params['repay_period_type'] == 1) ? $repayReriod : ($repayReriod * 30);

            $rpcRes = CouponService::saveCouponDeal($dealId,$rebateDays,$payType,$payAuto);
            if(!$rpcRes){
                throw new \Exception('优惠码信息保存失败');
            }
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}