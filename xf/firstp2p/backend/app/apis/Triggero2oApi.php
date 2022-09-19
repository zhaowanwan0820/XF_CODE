<?php
namespace NCFGroup\Ptp\Apis;

use core\service\O2OService;
use core\event\O2OExchangeDiscountEvent;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class Triggero2oApi {
    private $result = array('code' => 0, 'msg' => 'success', 'data' =>'');
    protected $params = [];

    public function __construct() {
        $this->params = json_decode(file_get_contents('php://input'), true);
        if (isset($this->params['Message']) && $this->params['Message']) {
            $this->params = json_decode($this->params['Message'], true);
        }
    }

    /**
     * 获取参数
     * @param $key string 键值
     * @param $default string 默认值
     * @return mixed
     */
    public function getParam($key = '', $default = '') {
        if (empty($key)) {
            return $this->params;
        }

        return isset($this->params[$key]) ? $this->params[$key] : $default;
    }

    /**
     * 记录广告联盟相关信息
     */
    public function triggerAdRecord() {
        return $this->result;
        /*
        $uid = $this->getParam('user_id');
        $type = 4;
        $deal_id = $this->getParam('deal_id', 0);
        $load_id = $this->getParam('load_id', 0);
        $money = $this->getParam('money', 0.00);
        $order_channel = $this->getParam('order_channel', 0);
        $coupon = $this->getParam('coupon_id', '');
        $ceuid = $this->getParam('euid', '');
        $ctrack_id = $this->getParam('track_id', 0);

        $adunionDealService = new \core\service\AdunionDealService();
        $res = $adunionDealService->triggerAdRecord(
            $uid,
            $type,
            $deal_id,
            $load_id,
            $money,
            $order_channel,
            $coupon,
            $ceuid,
            $ctrack_id
        );

        return $this->result;
        */
    }

    /**
     * 兑换优惠券
     */
    public function exchangeDiscount() {
        $discountId = $this->getParam('discount_id');
        if (empty($discountId)) {
            return $this->result;
        }

        $consumeType = $this->getParam('consumeType');
        $userId = $this->getParam('user_id');
        $dealLoadId = $this->getParam('load_id');
        $dealName = $this->getParam('deal_name');
        $couponCode = $this->getParam('coupon_id');
        $buyPrice = $this->getParam('buyPrice', 0);
        $discountGoldCurrentOrderId = $this->getParam('discountGoldCurrentOrderId', 0);
        $annualizedAmount = $this->getParam('annualizedAmount');

        try {
            $o2oExchangeDiscountEvent = new O2OExchangeDiscountEvent(
                $userId,
                $discountId,
                $dealLoadId,
                $dealName,
                $couponCode,
                $buyPrice,
                $discountGoldCurrentOrderId,
                $consumeType,
                $annualizedAmount
            );
            // 兑换优惠券
            $o2oExchangeDiscountEvent->execute();
        } catch (\Exception $e) {
            $this->result['code'] = -1;
            $this->result['msg'] = $e->getMessage();
        }

        return $this->result;
    }

    /**
     * o2o触发落单
     */
    public function notify () {
        $userId = $this->getParam('userId');
        $action = $this->getParam('action');
        $dealLoadId = $this->getParam('dealLoadId');
        $siteId = $this->getParam('siteId');
        $money = $this->getParam('money');
        $annualizedAmount = $this->getParam('annualizedAmount');
        $consumeType = $this->getParam('consumeType');
        $triggerType = $this->getParam('triggerType');
        $extra = $this->getParam('extra');
        $bidSource = $this->getParam('bidSource');
        if ($bidSource) {
            $extra['bid_source'] = $bidSource;
        }

        try {
            $res = O2OService::triggerO2OOrder(
                $userId,
                $action,
                $dealLoadId,
                $siteId,
                $money,
                $annualizedAmount,
                $consumeType,
                $triggerType,
                $extra
            );
        } catch (\Exception $e) {
            $this->result['code'] = -1;
            $this->result['msg'] = $e->getMessage();
        }

        return $this->result;
    }
}

