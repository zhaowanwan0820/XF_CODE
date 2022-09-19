<?php
/**
 * DtCouponService.php
 * 多投优惠码服务
 *
 * @date 2016-02-17
 *
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */

namespace core\service\duotou;

use libs\utils\Logger;
use core\enum\duotou\CommonEnum;
use core\enum\CouponEnum;
use core\service\coupon\CouponService;
use core\service\duotou\DuotouService;

class DtCouponService extends DuotouService
{
    const COUPON_TYPE = 'duotou';

    /**
     * 使用优惠码投资.
     *
     * @param unknown $dealLoadId         投资记录Id
     * @param unknown $dealId             多投标Id
     * @param unknown $userId             用户Id
     * @param unknown $money              投资金额
     * @param unknown $couponId           优惠码
     * @param unknown $accrueInterestTime 计息日
     */
    public function bid($dealLoadId, $dealId, $userId, $money, $couponId, $accrueInterestTime)
    {
        $response = CouponService::consume(CouponEnum::TYPE_DUOTOU, $couponId, $money, $userId, $dealId, $dealLoadId, $accrueInterestTime, 1);

        if (!$response) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, $dealId, $dealLoadId, $userId, '多投使用优惠码出借失败', $response['resMsg'])));
            $content = '用户ID:'.$userId.",标的ID:{$dealId},出借记录ID:{$dealLoadId},金额:{$money},多投使用优惠码出借失败,原因:".$response['resMsg'];
            \libs\utils\Alarm::push(CommonEnum::DT_DEAL, '多投使用优惠码出借失败', $content);
            return false;
        }
        return true;
    }

    /**
     * 赎回调用优惠码，.
     *
     * @param int $deal_id       流标的标的ID
     * @param int $dealRepayTime 赎回时间
     *
     * @return bool
     */
    public function redeem($dealLoadId, $dealRepayTime)
    {
        $response = CouponService::redeem(CouponEnum::TYPE_DUOTOU, $dealLoadId, $dealRepayTime);
        if (!$response) {//失败
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, $dealLoadId, $dealRepayTime, '转让/退出调用优惠码失败', $response['resMsg'])));
            $content = "出借记录ID:{$dealLoadId},转让/退出时间:{$dealRepayTime},转让/退出调用优惠码失败,原因:".$response['resMsg'];
            \libs\utils\Alarm::push(CommonEnum::DT_DEAL, '转让/退出调用优惠码失败', $content);
            return false;
        }
        return true;
    }
}
