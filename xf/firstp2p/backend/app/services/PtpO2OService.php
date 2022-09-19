<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RequestAcquireCouponList;
use NCFGroup\Protos\Ptp\RequestAcquireDiscountList;
use NCFGroup\Protos\Ptp\RequestAcquireRuleDiscount;
use core\service\O2OService;
use NCFGroup\Protos\O2O\RequestBatchGiveDiscounts;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RequestTriggerOtoOrder;
use libs\utils\Logger;

class PtpO2OService extends ServiceBase {
    /**
     * 领取多个礼券，支持同步返回结果和异步执行
     *
     * @param $userId int 用户id
     * @param $couponGroupId string 礼券券组id，多个用逗号分隔
     * @param $token string token码
     * @param string $mobile 返利手机号
     * @param int $dealLoadId 交易id
     * @param $isSyncResult bool 是否返回同步结果,true为返回，false不返回（会异步执行）
     * @param $rebateAmount float 返利金额，覆盖o2o的券组返利金额
     * @param $rebateLimit int 返利期限，覆盖o2o的券组的返利期限
     * @access public
     * @return array | bool
     */
    public function acquireCoupons(RequestAcquireCouponList $request) {
        $userId = $request->getUserId();
        $couponGroupIds = $request->getCouponGroupIds();
        $token = $request->getToken();
        $mobile = $request->getMobile();
        $dealLoadId = $request->getDealLoadId();
        $isSyncResult = $request->getIsSync();
        $rebateAmount = $request->getRebateAmount();
        $rebateLimit = $request->getRebateLimit();

        $o2oService = new O2OService();
        $res = $o2oService->acquireCoupons($userId, $couponGroupIds, $token, $mobile, $dealLoadId, $isSyncResult,
            $rebateAmount, $rebateLimit);

        // 异常情况，抛异常
        if ($res === false) {
            throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
        }

        return $res;
    }

    /**
     * 领取指定投资券规则的投资券
     * @param $userId int 用户id
     * @param $discountRuleId int 投资规则id
     * @param $token string 唯一token
     * @param $bidAmount float 起投金额
     * @param $bidDayLimit int 起投期限
     * @param $dealLoadId int 交易id
     * @param $remark string 备注
     * @param $rebateAmount float 返利金额
     * @param $rebateLimit int 返利期限
     * @return array | false
     */
    public function acquireDiscountRule(RequestAcquireRuleDiscount $request) {
        $userId = $request->getUserId();
        $discountRuleId = $request->getDiscountRuleId();
        $token = $request->getToken();
        $bidAmount = $request->getBidAmount();
        $bidDayLimit = $request->getBidDayLimit();
        $dealLoadId = $request->getDealLoadId();
        $remark = $request->getRemark();
        $rebateAmount = $request->getRebateAmount();
        $rebateLimit = $request->getRebateLimit();

        $o2oService = new O2OService();
        $res = $o2oService->acquireRuleDiscount($userId, $discountRuleId, $token, $bidAmount, $bidDayLimit,
            $dealLoadId, $remark, $rebateAmount, $rebateLimit);

        // 异常情况，抛异常
        if ($res === false) {
            throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
        }

        $response = new ResponseBase();
        $response->data = $res;
        return $response;
    }

    /**
     * 同时获取多张投资券，有异步重试机制
     * 如果同步调用成功，会立刻返回结果
     *
     * @param $userId int 用户id
     * @param $discountGroupId string 投资券组id，多个用逗号分隔
     * @param $token string 唯一token
     * @param $dealLoadId int 交易id
     * @param $remark string 备注
     * @param $isSyncResult bool 是否返回同步结果,true为返回，false不返回（会异步执行）
     * @param $rebateAmount float 返利金额，覆盖o2o的券组返利金额
     * @param $rebateLimit int 返利期限，覆盖o2o的券组的返利期限
     * @return array | false
     */
    public function acquireDiscounts(RequestAcquireDiscountList $request) {
        $userId = $request->getUserId();
        $discountGroupIds = $request->getDiscountGroupIds();
        $token = $request->getToken();
        $dealLoadId = $request->getDealLoadId();
        $remark = $request->getRemark();
        $isSyncResult = $request->getIsSync();
        $rebateAmount = $request->getRebateAmount();
        $rebateLimit = $request->getRebateLimit();

        $o2oService = new O2OService();
        $res = $o2oService->acquireDiscounts($userId, $discountGroupIds, $token, $dealLoadId, $remark,
            $isSyncResult, $rebateAmount, $rebateLimit);

        // 异常情况，抛异常
        if ($res === false) {
            throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
        }

        return $res;
    }

    /**
     * giveDiscounts 理财师app批量转赠投资券接口
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-05-23
     * @param RequestBatchGiveDiscounts $request
     * @param giveList 格式array(array('toUserId'=>123, 'discountId'=>345),array('toUserId'=>234, 'discountId'=>456))
     * @access public
     * @return void
     */
    public function giveDiscounts(RequestBatchGiveDiscounts $request) {
        $fromUserId = $request->getFromUserId();
        $giveList = $request->getGiveList();
        $o2oService = new O2OService();
        $res = $o2oService->batchGiveDiscount($fromUserId, $giveList);
        if ($res === false) {
            throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
        }
        $response = new ResponseBase();
        $response->data = $res;
        return $response;
    }

    public function triggerO2OOrder(RequestTriggerOtoOrder $request) {
        $userId = $request->getUserId();
        $action = $request->getAction();
        $dealLoadId = $request->getDealLoadId();
        $siteId = $request->getSiteId();
        $money = $request->getMoney();
        $annualizedAmount = $request->getAnnualizedAmount();
        $consumeType = $request->getConsumeType();
        $triggerType = $request->getTriggerType();
        $extra = $request->getExtra();
        $response = new ResponseBase();
        $isSync = true;
        try{
            O2OService::triggerO2OOrder($userId, $action, $dealLoadId, $siteId, $money, $annualizedAmount, $consumeType, $triggerType, $extra, $isSync);
        } catch (\Exception $e) {
            Logger::error('triggerO2OOrder error:'.$e->getMessage());
            $response->data = false;
            return $response;
        }
        $response->data = true;
        return $response;
    }
}
