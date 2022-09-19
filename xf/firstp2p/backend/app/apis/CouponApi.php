<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use core\service\CouponDealService;
use core\service\CouponService;
use core\service\O2OService;
use core\service\CouponBindService;
use core\service\CouponLogService;

/**
 * 保存标优惠码接口.
 */
class CouponApi extends ApiBackend
{
    /**
     * 通过用户真实姓名获取用户id.
     *
     * @param $name string 真实姓名
     *
     * @return array
     */
    public function saveCouponDeal()
    {
        $dealId = $this->getParam('dealId');
        $payType = $this->getParam('payType');
        $payAuto = $this->getParam('payAuto');
        $rebateDays = $this->getParam('rebateDays');
        $isRebate = $this->getParam('isRebate');
        $type = $this->getParam('type');
        $couponDealService = new CouponDealService($type);
        $result = $couponDealService->handleCoupon($dealId,$payType,$payAuto,$rebateDays,$isRebate);

        return $this->formatResult($result);
    }

    public function getCouponDealByDealId()
    {
        $type = $this->getParam('type');
        $dealId = $this->getParam('dealId');
        $couponDealService = new CouponDealService($type);
        $result = $couponDealService->getCouponDealByDealId($dealId);
        return $this->formatResult($result);
    }

    public function queryCoupon()
    {
        $shortAlias = $this->getParam('shortAlias');
        $type = $this->getParam('type');
        $dealId = empty($dealId) ? 0 : $dealId;
        $isFinancePassedNeeded = $this->getParam('isFinancePassedNeeded');
        $isFinancePassedNeeded = empty($isFinancePassedNeeded) ? false : $isFinancePassedNeeded;
        $couponService = new CouponService($type);
        $result = $couponService->queryCoupon($shortAlias, $isFinancePassedNeeded);

        return $this->formatResult($result);
    }

    public function getCouponLatest()
    {
        $consumeUserId = $this->getParam('consumeUserId');
        $type = $this->getParam('type');
        $couponService = new CouponService($type);
        $result = $couponService->getCouponLatest($consumeUserId);

        return $this->formatResult($result);
    }

    public function consume()
    {
        $coupon = $this->getParam('coupon');

        $type = $this->getParam('type');
        if (!in_array($type, array(CouponLogService::MODULE_TYPE_DUOTOU, CouponLogService::MODULE_TYPE_P2P, CouponLogService::MODULE_TYPE_JIJIN, CouponLogService::MODULE_TYPE_GOLD, CouponLogService::MODULE_TYPE_GOLDC, CouponLogService::MODULE_TYPE_NCFPH))) {
            return $this->formatResult(false, 1, '项目名称不正确');
        }

        $money = $this->getParam('money');
        if ($money <= 0.0) {
            return $this->formatResult(false, 1, '投资金额不能小于等于0');
        }

        $userId = $this->getParam('userId');
        if ($userId <= 0) {
            return $this->formatResult(false, 1, '投资人id错误');
        }

        $dealId = $this->getParam('dealId');
        if ($dealId <= 0) {
            return $this->formatResult(false, 1, '标ID不能为空');
        }

        $dealLoadId = $this->getParam('dealLoadId');
        if ($dealLoadId <= 0) {
            return $this->formatResult(false, 1, '投资ID不能为空');
        }

        $repayStartTime = $this->getParam('repayStartTime');
        $siteId = $this->getParam('siteId');
        if ($siteId <= 0) {
            return $this->formatResult(false, 1, 'siteId不能为空');
        }
        $coupon_fields = array();
        $coupon_fields['deal_id'] = $dealId;
        $coupon_fields['repay_start_time'] = $repayStartTime;
        $coupon_fields['money'] = $money;
        $coupon_fields['site_id'] = $siteId;

        //活期黄金专用
        $amount = $this->getParam('amount');
        if (!empty($amount)) {
            $coupon_fields['amount'] = $amount;
        }
        $price = $this->getParam('price');
        if (!empty($price)) {
            $coupon_fields['price'] = $price;
        }

        $couponService = new CouponService($type);
        $ret = $couponService->consume($dealLoadId, $coupon, $userId, $coupon_fields, CouponService::COUPON_SYNCHRONOUS);
        if (empty($ret)) {
            return $this->formatResult(false, 1, '操作失败');
        } else {
            $coupon_log = $ret->_row;
        }

        return $this->formatResult($coupon_log, 0, '操作成功');
    }

    public function redeem()
    {
        $type = $this->getParam('type');
        if (!in_array($type, array(CouponLogService::MODULE_TYPE_DUOTOU, CouponLogService::MODULE_TYPE_P2P, CouponLogService::MODULE_TYPE_JIJIN, CouponLogService::MODULE_TYPE_GOLD, CouponLogService::MODULE_TYPE_GOLDC, CouponLogService::MODULE_TYPE_NCFPH))) {
            return $this->formatResult(false, 1, '项目名称不正确');
        }

        $dealLoadId = $this->getParam('dealLoadId');
        if (empty($dealLoadId)) {
            return $this->formatResult(false, 1, '投资id错误');
        }

        $dealRepayTime = $this->getParam('dealRepayTime');
        if (empty($dealRepayTime)) {
            return $this->formatResult(false, 1, '还款时间不能为空');
        }

        $couponLogService = new CouponLogService($type);
        $result = $couponLogService->redeem($dealLoadId, $dealRepayTime);
        if (!$result) {
            return $this->formatResult(false, 1, '操作失败');
        }

        return $this->formatResult($result, 0, '操作成功');
    }

    /**
     * 获取用户好友个数
     * @param $userId int 用户id
     * @return int
     */
    public function getUserFriendCount() {
        $userId = $this->getParam('userId');
        $couponBindService = new CouponBindService();
        $count = $couponBindService->getUserFriendCount($userId);
        return $this->formatResult($count);
    }

    public function getOneUserCoupon(){
        $userId = $this->getParam('userId');
        $couponService = new CouponService();
        $res = $couponService->getOneUserCoupon($userId);
        return $this->formatResult($res);

    }

    public function checkcoupon(){
        $shortAlias = $this->getParam('shortAlias');
        $type = $this->getParam('type');
        $couponService = new CouponService($type);
        $res = $couponService->checkCoupon($shortAlias);
        return $this->formatResult($res);
    }

    public function isShowCoupon(){
        $dealId = $this->getParam('dealId');
        $dealId = empty($dealId) ? false : $dealId;
        $type = $this->getParam('type');
        $couponService = new CouponService($type);
        $res = $couponService->isShowCoupon($dealId);
        return $this->formatResult($res);
    }

    public function getByUserId(){
        $userId = $this->getParam('userId');
        $shortAlias = $this->getParam('shortAlias');
        $shortAlias = empty($shortAlias) ? false : $shortAlias;
        $couponBindService = new CouponBindService();
        $res = $couponBindService->getByUserId($userId, $shortAlias);
        return $this->formatResult($res);
    }
}
