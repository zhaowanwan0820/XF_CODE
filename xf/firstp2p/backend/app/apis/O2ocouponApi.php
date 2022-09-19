<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use core\service\OtoTriggerRuleService;
use core\service\O2OService;
use core\service\oto\O2OCouponGroupService;
use core\service\UserCouponLevelService;

/**
 * O2O礼券触发相关接口
 */
class O2ocouponApi extends ApiBackend {

    public function getCouponGroupList() {
        $userId = $this->getParam('userId');
        $action = $this->getParam('action');
        $dealLoadId = $this->getParam('dealLoadId');
        $consumeType = $this->getParam('consumeType');
        $o2oService = new O2OService();
        $res = $o2oService->getCouponGroupList($userId, $action, $dealLoadId, $consumeType);
        return $this->formatResult($res);
    }

    public function getCouponTriggerList() {
        $userId = $this->getParam('userId');
        $action = $this->getParam('action');
        $dealLoadId = $this->getParam('dealLoadId');
        $consumeType = $this->getParam('consumeType');

        $groupService = new O2OCouponGroupService();
        $res = $groupService->getCouponTriggerList($userId, $action, $dealLoadId, $consumeType);
        return $this->formatResult($res);
    }

    public function chargeTriggerO2O() {
        $userId = $this->getParam('userId');
        $action = $this->getParam('action');
        $orderId = $this->getParam('orderId');
        $money = $this->getParam('money');
        $siteId = $this->getParam('siteId');
        $withdrawTime = $this->getParam('withdrawTime');
        $o2oService = new O2OService();
        $res = $o2oService->chargeTriggerO2O($userId, $action, $orderId, $money, $siteId, $withdrawTime);
        return $this->formatResult($res);
    }

    public function triggerO2OOrder() {
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
        $res = O2OService::triggerO2OOrder($userId, $action, $dealLoadId, $siteId, $money, $annualizedAmount, $consumeType, $triggerType, $extra);
        // 抽奖码触发上移到入口处，不放triggerO2OOrder内
        O2OService::triggerUniqueCode($userId, $consumeType, $dealLoadId, $money, $action);
        return $this->formatResult($res);
    }

    public function acquireCoupons() {
        $userId = $this->getParam('userId');
        $couponGroupIds = $this->getParam('couponGroupIds');
        $token = $this->getParam('token');
        $mobile = $this->getParam('mobile');
        $dealLoadId = $this->getParam('dealLoadId');
        $isSyncResult = $this->getParam('isSyncResult');
        $rebateAmount = $this->getParam('rebateAmount');
        $rebateLimit = $this->getParam('rebateLimit');
        $o2oService = new O2OService();
        $res = $o2oService->acquireCoupons($userId, $couponGroupIds, $token, $mobile, $dealLoadId, $isSyncResult, $rebateAmount, $rebateLimit);
        return $this->formatResult($res);
    }

    public function acquireDiscounts() {
        $userId = $this->getParam('userId');
        $discountGroupIds = $this->getParam('discountGroupIds');
        $token = $this->getParam('token');
        $dealLoadId = $this->getParam('dealLoadId');
        $remark = $this->getParam('remark');
        $isSyncResult = $this->getParam('isSyncResult');
        $rebateAmount = $this->getParam('rebateAmount');
        $rebateLimit = $this->getParam('rebateLimit');
        $o2oService = new O2OService();
        $res = $o2oService->acquireDiscounts($userId, $discountGroupIds, $token, $dealLoadId, $remark, $isSyncResult, $rebateAmount, $rebateLimit);
        return $this->formatResult($res);
    }

    /**
     * checkGroupService o2o后台查询会员组有效性状态
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2019-02-25
     * @access public
     * @return void
     */
    public function checkGroupService() {
        $result = array();
        $groupIdstr = $this->getParam('groupIds');
        $groupIds = explode(',', $groupIdstr);
        if (empty($groupIds)) {
            return $this->formatResult($result);
        }

        $userCouponLevelService = new UserCouponLevelService();
        foreach($groupIds as $id) {
            $group = $userCouponLevelService->getGroupById($id);
            if (isset($group) && (1 == $group['service_status'])) {
                $result[$id] = true;
            } else {
                $result[$id] = false;
            }
        }
        return $this->formatResult($result);
    }
}
