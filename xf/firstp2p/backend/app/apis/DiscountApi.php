<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use core\service\oto\O2ODiscountService;
use core\event\O2OExchangeDiscountEvent;
use core\service\DiscountService;
use libs\utils\PaymentApi;

/**
 * 优惠券信息接口
 */
class DiscountApi extends ApiBackend {
    /**
     * 投资券是否可用
     * @return array
     */
    public function canUseDiscount() {
        $userId = $this->getParam('userId');
        $discountId = $this->getParam('discountId');
        $discountGroupId = $this->getParam('discountGroupId');
        $consumeType = $this->getParam('consumeType');
        $extraParam = $this->getParam('extraParam');
        $errorInfo = array();
        $o2oDiscountService = new O2ODiscountService();
        $res = $o2oDiscountService->canUseDiscount($userId, $discountId, $discountGroupId, $errorInfo, $consumeType, $extraParam);
        if ($errorInfo) {
            $code = $errorInfo['errorCode'];
            $msg = isset($errorInfo['discountDayLimit']) ? $errorInfo['discountDayLimit'] : $errorInfo['discountGoodsPrice'];
            if ($msg) {
                PaymentApi::log('canUseDiscount err:code|'.$code.' msg|'.$msg);
            }
        }
        return $this->formatResult($res);
    }

    /**
     * freezeDiscount 冻结投资券
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-08
     * @access public
     * @return void
     */
    public function freezeDiscount() {
        $userId = $this->getParam('userId');
        $discountId = $this->getParam('discountId');
        $triggerTime = $this->getParam('triggerTime');
        $consumeId = $this->getParam('consumeId');
        $consumeType = $this->getParam('consumeType');
        $discountType = $this->getParam('discountType');
        $o2oDiscountService = new O2ODiscountService();
        $res = $o2oDiscountService->freezeDiscount($userId, $discountId, $triggerTime, $consumeId, $consumeType, $discountType);
        return $this->formatResult($res);
    }

    /**
     * unfreezeDiscount 解冻投资券
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-08
     * @access public
     * @return void
     */
    public function unfreezeDiscount() {
        $userId = $this->getParam('userId');
        $discountId = $this->getParam('discountId');
        $triggerTime = $this->getParam('triggerTime');
        $consumeId = $this->getParam('consumeId');
        $consumeType = $this->getParam('consumeType');
        $discountType = $this->getParam('discountType');
        $o2oDiscountService = new O2ODiscountService();
        $res = $o2oDiscountService->unfreezeDiscount($userId, $discountId, $triggerTime, $consumeId, $consumeType, $discountType);
        return $this->formatResult($res);
    }

    /**
     * consumeDiscount GTM使用投资券
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-16
     * @access public
     * @return void
     */
    public function consumeDiscount() {
        $userId = $this->getParam('userId');
        $discountId = $this->getParam('discountId');
        $dealLoadId = $this->getParam('dealLoadId');
        $discountType = $this->getParam('discountType');
        $triggerTime = $this->getParam('triggerTime');
        $consumeType = $this->getParam('consumeType');
        $extraInfo = $this->getParam('extraInfo');
        $o2oDiscountService = new O2ODiscountService();
        $res = $o2oDiscountService->consumeDiscount($userId, $discountId, $dealLoadId, $discountType, $triggerTime, $consumeType, $extraInfo);
        return $this->formatResult($res);
    }

    /**
     * cancelConsumeDiscount GTM回滚投资券
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-16
     * @access public
     * @return void
     */
    public function cancelConsumeDiscount() {
        $userId = $this->getParam('userId');
        $discountId = $this->getParam('discountId');
        $o2oDiscountService = new O2ODiscountService();
        $res = $o2oDiscountService->cancelConsumeDiscount($userId, $discountId);
        return $this->formatResult($res);
    }

    public function getDiscountRecord() {
        $discountId = $this->getParam('discountId');
        $o2oDiscountService = new O2ODiscountService();
        $discountRecord = $o2oDiscountService->getDiscountRecord($discountId);
        return $this->formatResult($discountRecord);
    }

    /**
     * o2oExchangeDiscount 投资完成后使用投资券
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-16
     * @access public
     * @return void
     */
    public function o2oExchangeDiscount() {
        $userId = $this->getParam('userId');
        $discountId = $this->getParam('discountId');
        $dealLoadId = $this->getParam('dealLoadId');
        $dealName = $this->getParam('dealName');
        $couponCode = $this->getParam('couponCode');
        $buyPrice = $this->getParam('buyPrice');
        $discountGoldCurrentOrderId = $this->getParam('discountGoldCurrentOrderId');
        $consumeType = $this->getParam('consumeType');
        $annualizedAmount = $this->getParam('annualizedAmount');
        $o2oExchangeDiscountEvent = new O2OExchangeDiscountEvent($userId, $discountId, $dealLoadId, $dealName, $couponCode, $buyPrice, $discountGoldCurrentOrderId, $consumeType, $annualizedAmount);
        $res = $o2oExchangeDiscountEvent->execute();
        return $this->formatResult($res);
    }

    public function validateDiscountAndDealinfo() {
        $userId = $this->getParam('userId');
        $discountId = $this->getParam('discountId');
        $discountGroupId = $this->getParam('discountGroupId');
        $discountSign = $this->getParam('discountSign');
        $dealInfo = $this->getParam('dealInfo');
        $money = $this->getParam('money');
        $siteId = $this->getParam('siteId');
        $discountService = new DiscountService();
        $res = $discountService->validateDiscountAndDealinfo($userId, $discountId, $discountGroupId, $discountSign, $dealInfo, $money, $siteId);
        return $this->formatResult($res);
    }

    /**
     * 获取领券中心的url
     */
    public function getDiscountCenterUrl() {
        return $this->formatResult((new \core\service\ApiConfService())->getDiscountCenterUrl(1));
    }
}
