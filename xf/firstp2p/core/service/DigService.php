<?php
/**
 * DigService.php
 *
 * @date 2015-04-03
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\DealModel;
use core\dao\BankModel;
use core\dao\UserBankcardModel;
use core\dao\DeliveryRegionModel;
use core\dao\UserBankcardAuditModel;
use core\dao\UserModel;
use libs\utils\PaymentApi;
use core\service\O2OService;
use core\service\UserTagService;
use core\service\UserService;
use libs\utils\Logger;
use core\dao\DealLoadModel;
//use core\service\CashpresentService;
//gearman
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\TestExampleEvent;
use libs\utils\Site;
use core\service\BonusService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\vip\VipService;
use core\service\CouponService;
use core\service\oto\O2OUtils;

class DigService extends BaseService {

    protected $eventName;

    protected $toGroupId = null;
    protected $toCouponLevelId = null;

    protected $result = null;

    protected $siteId = 1;

    public function __construct($hole){
        try {

            if(is_callable(array($this, $hole.'Callback'))) {
                $args = func_get_args();
                array_shift($args);
                $this->run($hole, $args);
            }
        }
        catch(\Exception $e) {
            PaymentApi::log($hole.'钩子执行失败:'.$e->getMessage());
        }
    }

    public function getEventName() {
        if (!empty($this->eventName)) {
            return $this->eventName;
        }
        return null;
    }

    public function getEventTags($eventName = null, $data = array()){
        if (empty($eventName) || empty($data)) {
            return array();
        }

        PaymentApi::log('用户注册填写的某个组里的邀请码'.var_export($data, true));
        // 读取 用户邀请码对应的groupId
        $couponService = new \core\service\CouponService();
        $userService = new \core\service\UserService();
        $couponInfo = $couponService->queryCoupon($data['cn']);
        PaymentApi::log('用户注册填写的某个组里的邀请码信息'.var_export($couponInfo, true));
        $inviter = $userService->getUserViaSlave($couponInfo['refer_user_id']);
        $inviterGroupId = empty($inviter) ? '' : $inviter->group_id;
        PaymentApi::log('用户注册填写的某个组里的邀请码对应的邀请人组ID'.var_export($inviterGroupId, true));
        // 检索事件和用户组 couponCode
        // groupId > tags > couponCode
        $eventCouponTags = \core\dao\OtoConfigModel::instance();
        $tags = $eventCouponTags->findAvailable($eventName, $data['cn'], $inviterGroupId);
        // 检索事件和group_id
        //$tags = $tags.','.$eventCouponTags->findAvailableByGroupId($eventName, $groupId);
        $tagsGroupId = isset($tags['toGroupId']) ? intval($tags['toGroupId']) : 0;
        if (!empty($tagsGroupId)) {
            $this->toGroupId = $tagsGroupId;
            $this->toCouponLevelId = $tags['toCouponLevelId'];
        }
        $tagsList = $tags['tags'];
        if (!empty($tagsList)) {
            $tagsList = explode(',', trim($tagsList, ','));
            PaymentApi::log('分解之后的tag数组'.var_export($tagsList, true));
            return $tagsList;
        }
        return array();
    }

    public function needTransferGroup() {
        $res = $this->toGroupId !== null;
        return $res;
    }

    public function getTransferGroupID() {
        return $this->toGroupId;
    }

    public function getTransferCouponLevelId() {
        return $this->toCouponLevelId;
    }

    public function run($hole, $args) {

        $actionName = $hole.'Callback';
        $args = $args[0];
        if (!empty($args[1])) {
            throw new \Exception('请求的参数个数太多');
        }
        $this->eventName = $hole;
        try {
            $eventTags = $this->getEventTags($this->getEventName(), $args);
            if (!empty($eventTags)) {
                $tagService = new UserTagService();
                $tagService->addUserTagsByConstName($args['id'], $eventTags);
                PaymentApi::log('打用户标签['.$hole.']'.var_export($eventTags, true));
            }
            $needTransferGroup = $this->needTransferGroup();
            if ($needTransferGroup) {
                $userService = new UserService();
                // TODO 是否遗漏其他的逻辑
                $res = $userService->moveUserToNewGroup($args['id'], $this->getTransferGroupID(), $this->getTransferCouponLevelId());
                PaymentApi::log('转移用户结果'.var_export($res, true));
            }
            $this->{$actionName}($args);
        }
        catch (\Exception $e) {
            PaymentApi::log('tags钩子执行失败:'.$e->getMessage());
        }
    }

    public function getResult() {
        return $this->result;
    }

    /**
     * 绑定银行卡之后回调
     * wangqunqiang
     */
    public function bindBankCardCallback($data) {
        PaymentApi::log('绑卡回调'.var_export($data, true));
        $rpcParams = array($data['id'], CouponGroupEnum::TRIGGER_FIRST_BINDCARD);
        $rpc = new \libs\rpc\Rpc();
        PaymentApi::log('绑卡回调 请求参数'.var_export($rpcParams, true));
        $list = \SiteApp::init()->dataCache->call($rpc, 'local', array('O2OService\getCouponGroupList', $rpcParams), 60);
        PaymentApi::log('绑卡回调 请求结果'.var_export($list, true));
        $this->result = $list;
    }

    /**
     * 注册完成回调
     */
    public function registerCallback($data) {
        PaymentApi::log('注册回调'.var_export($data, true));
        $rpcParams = array($data['id'], CouponGroupEnum::TRIGGER_REGISTER);
        PaymentApi::log('注册回调 请求参数'.var_export($rpcParams, true));
        $rpc = new \libs\rpc\Rpc();
        $list = \SiteApp::init()->dataCache->call($rpc, 'local', array('O2OService\getCouponGroupList', $rpcParams), 60);
        PaymentApi::log('注册回调 请求结果'.var_export($list, true));
        $this->result = $list;
    }

    /**
     * 复投回调
     * 通知贷 投资完成不进行礼券展示
     */
    public function makeLoanCallback($data) {
        if (!empty($data['isRedeem'])) {
            return;
        }

        $couponGroupService = new \core\service\oto\O2OCouponGroupService();
        $this->result = $couponGroupService->getCouponTriggerList(
            $data['id'],
            CouponGroupEnum::TRIGGER_REPEAT_DOBID,
            $data['loadid'],
            CouponGroupEnum::CONSUME_TYPE_P2P
        );
    }

    /**
     * 首次投资回调
     * 通知贷 投资完成不进行礼券展示
     */
    public function firstLoanCallback($data) {
        $userId = $data['id'];
        $list = array();
        $bonusService = new BonusService();
        // 首投红包返利
        if (empty($list) && !$bonusService->isBlackSite($data['siteId'])) {
            $res = $bonusService->firstDealRebate($userId, $data['cn'], $data['loadId'], $data['money'], $data['isRedeem']);
            if (!$res) {
                PaymentApi::log("FirstDealBonusRebateError." . $userId);
            }
        }
        if ($data['cn']) {
            // 首投给邀请人加vip经验
            $vipService = new VipService();
            $referUserId = $vipService->getReferUserId($userId);
            $sourceAmount = 1;
            $sourceType = VipEnum::VIP_SOURCE_INVITE;
            if ($referUserId) {
                $token = $sourceType.'_'.$userId;//一个用户最多只有一次被邀请触发vip经验的机会
                $info = '邀请'.$userId.'首投奖励';
                PaymentApi::log("FirstDeal add vip point|userId|" . $userId."|referUserId|".$referUserId."|token|".$token."|code|".$data['cn']);
                // 首次投资给信力需要根据年化额计算
                $annualizedAmount = O2OUtils::getAnnualizedAmountByDealLoadId($data['loadid']);
                $vipService->updateVipPoint($referUserId, $sourceAmount, $sourceType, $token, $info, $data['loadid'], 0, $annualizedAmount, $data['money']);
            }
        }
    }

    /**
     * 通知贷赎回回调
     */
    public function redeemCallback($data) {
        $list = array();
        if (app_conf('O2O_WITH_REDEEM')) {
            PaymentApi::log('通知贷赎回回调'.var_export($data, true));
            $rpcParams = array(
                $data['id'],
                CouponGroupEnum::TRIGGER_REPEAT_DOBID,
                $data['loadId'],
                CouponGroupEnum::CONSUME_TYPE_P2P
            );
            PaymentApi::log('通知贷赎回回调 请求参数'.var_export($rpcParams, true));
            $rpc = new \libs\rpc\Rpc();
            $list = \SiteApp::init()->dataCache->call($rpc, 'local', array('O2OService\getCouponGroupList', $rpcParams), 60);
        }

        PaymentApi::log('通知贷赎回回调 请求结果'.var_export($list, true));
        $this->result = $list;
    }
}
