<?php
/**
 * DtCouponService.php
 * 多投优惠码服务
 * @date 2016-02-17
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */
namespace core\service\duotou;

use libs\utils\Logger;
use NCFGroup\Protos\Ptp\RequestCoupon;
use NCFGroup\Protos\Duotou\Enum\CommonEnum;

class DtCouponService {

    const COUPON_TYPE = 'duotou';

    /**
     * 使用优惠码投资
     * @param unknown $dealLoadId 投资记录Id
     * @param unknown $dealId 多投标Id
     * @param unknown $userId 用户Id
     * @param unknown $money 投资金额
     * @param unknown $couponId 优惠码
     * @param unknown $accrueInterestTime 计息日
     */
    public function bid($dealLoadId,$dealId,$userId,$money,$couponId,$accrueInterestTime) {
        $dealId = "{$dealId}"; // 强制转换为字符串，因为PtpCoupon 进行了强制类型验证
        \libs\utils\PhalconRPCInject::init();
        $request = new RequestCoupon();
        $request->setDealLoadId($dealLoadId);
        $request->setDealid($dealId);
        $request->setUserId($userId);
        $request->setMoney($money);
        $request->setCoupon(strval($couponId));
        $request->setType(self::COUPON_TYPE);
        $request->setRepayStartTime($accrueInterestTime);

        $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpCoupon',
                'method' => 'consume',
                'args' => $request
        ));
        if($response['resCode']) {//失败
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $dealId,$dealLoadId,$userId, "多投使用优惠码投资失败", $response['resMsg'])));
            $content = "用户ID:" . $userId . ",标的ID:{$dealId},投资记录ID:{$dealLoadId},金额:{$money},多投使用优惠码投资失败,原因:" . $response['resMsg'];
            \libs\utils\Alarm::push(CommonEnum::DT_DEAL, '多投使用优惠码投资失败', $content);
            return false;
        }
        return true;
    }

    /**
     * 赎回调用优惠码，
     * @param int $deal_id 流标的标的ID
     * @param int $dealRepayTime 赎回时间
     * @return bool
     */
    public function redeem($dealLoadId,$dealRepayTime) {
        \libs\utils\PhalconRPCInject::init();
        $request = new RequestCoupon();
        $request->setDealLoadId($dealLoadId);
        $request->setType(self::COUPON_TYPE);
        $request->setDealRepayTime($dealRepayTime);

        $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpCoupon',
                'method' => 'redeem',
                'args' => $request
        ));
        if($response['resCode'] != 0 ) {//失败
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $dealLoadId,$dealRepayTime, "赎回调用优惠码失败", $response['resMsg'])));
            $content = "投资记录ID:{$dealLoadId},赎回时间:{$dealRepayTime},赎回调用优惠码失败,原因:" . $response['resMsg'];
            \libs\utils\Alarm::push(CommonEnum::DT_DEAL, '赎回调用优惠码失败', $content);
            return false;
        }
        return true;
    }
}
