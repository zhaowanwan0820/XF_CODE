<?php
/**
 * 补刀脚本，补普惠邀请码当前30分钟之前到60分钟之间的数据.
 */
require_once dirname(__FILE__).'/../../app/init.php';
use core\service\third\ThirdDealService;
use core\service\CouponDealService;
use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '1024M');

class coupon_deal_async
{
    public function run($time)
    {
        $thirdDealService = new ThirdDealService();
        $deals = $thirdDealService->getDealsByUpdateTime($time);
        if (!empty($deals)) {
            foreach ($deals as $value) {
                try {
                    $this->handleCoupon($value);
                } catch (\Exception $e) {
                    \libs\utils\Alarm::push('coupon_deal_async', $e->getMessage(), json_encode($value));
                    Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, $e->getMessage(), 'data:'.json_encode($value))));
                }
            }
        }
    }

    private function handleCoupon($dealInfo)
    {
        if($dealInfo['loantype'] == -1){
            throw new Exception('还款类型不正确');
        }

        $couponDealService = new CouponDealService('third');
        $couponDealInfo = $couponDealService->getCouponDealByDealId($dealInfo['id']);
        if (empty($couponDealInfo)) {
            $result = $couponDealService->waitingDeal($dealInfo);
            if (empty($result)) {
                throw new Exception('初始化邀请码标的失败');
            }
        }

        $couponDealInfo = $couponDealService->getCouponDealByDealId($dealInfo['id']);
        if ($couponDealInfo['deal_status'] != $dealInfo['deal_status']) {
            $result = $couponDealService->handleCoupon($dealInfo['id']);
            if (empty($result)) {
                throw new Exception('更新邀请码标的失败');
            }
        }
    }
}

//取一小时后数据
$starTime = isset($argv[1]) ? $argv[1] : time() - 3600;
$coupon_deal_async = new coupon_deal_async();
$coupon_deal_async->run($starTime);
exit('第三方标信息同步处理完毕');
