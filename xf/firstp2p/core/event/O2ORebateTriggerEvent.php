<?php

namespace core\event;

use libs\utils\Logger;
use core\event\BaseEvent;
use libs\utils\PaymentApi;
use core\dao\OtoAllowanceLogModel;
use core\dao\UserModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\MsgBoxService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use core\service\GoldBidRebateService;
use libs\sms\SmsServer;
use NCFGroup\Common\Library\Msgbus;

/**
 * o2o投资触发返利
 */
class O2ORebateTriggerEvent extends BaseEvent {
    private $_userId;
    private $_referUserId;
    private $_serviceUserId;
    private $_dealLoadId;
    private $_acqurieLogId;
    private $_trigger;
    private $_siteId;
    private $_rebateGoldOrderId;
    private $_triggerType;
    private $_consumeType;

    public function __construct($userId, $referUserId, $logId, $dealLoadId, $trigger, $siteId = 1, $orderId = 0, $serviceUserId = 0, $triggerType = 0, $consumeType = CouponGroupEnum::CONSUME_TYPE_ZHUANXIANG) {
        $this->_userId = $userId;
        $this->_referUserId = $referUserId;
        $this->_serviceUserId = $serviceUserId;
        $this->_dealLoadId = $dealLoadId;
        $this->_acqurieLogId = $logId;
        $this->_trigger = $trigger;
        $this->_siteId = $siteId;
        $this->_rebateGoldOrderId = $orderId;
        $this->_triggerType = $triggerType;
        $this->_consumeType = $consumeType;
    }

    public function execute() {
        $params = array(
            'userId'=>$this->_userId,
            'referUserId'=>$this->_referUserId,
            'logId'=>$this->_acqurieLogId,
            'trigger'=>$this->_trigger,
            'dealLoadId'=>$this->_dealLoadId,
            'siteId'=>$this->_siteId,
            'serviceUserId'=>$this->_serviceUserId,
            'triggerType' => $this->_triggerType,
            'consumeType' => $this->_consumeType
        );

        PaymentApi::log("O2OService.O2ORebateTriggerEvent投资触发返利, params: "
            .json_encode($params, JSON_UNESCAPED_UNICODE), Logger::INFO);

        // 没有相关的返利
        if (empty($this->_trigger)) {
            return true;
        }

        // 处理触发返利
        $this->allowance($this->_trigger);

        // 处理推送消息
        $this->sendTriggerMessage($this->_trigger);
        return true;
    }

    /**
     * 返利处理
     */
    private function allowance(array $triggers) {
        $currentTime = time();
        $allowanceService = new \core\service\oto\O2OAllowanceService();
        foreach ($triggers as $trigger) {
            // 处理触发返利
            foreach ($trigger['reward'] as $reward) {
                // 返利类型不能为空
                if (empty($reward['type'])) {
                    continue;
                }

                // 查询返利凭证是否存在
                $token = 'trigger_'.$this->_acqurieLogId.'_'.$reward['type'].'_'.$trigger['id'];
                $condition = "token = '{$token}'";
                $logInfo = OtoAllowanceLogModel::instance()->findBy($condition, 'id');
                // 已经返利过了，保证操作的幂等
                if ($logInfo) {
                    PaymentApi::log('触发返利已完成', Logger::INFO);
                    continue;
                }

                $toUserId = 0;
                $allowanceType = 0;
                $roleType = 0;
                if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_USER_COUPON) {
                    $toUserId = $this->_userId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_COUPON;
                    $roleType = CouponGroupEnum::ROLE_TYPE_USER;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_USER_DISCOUNT) {
                    $toUserId = $this->_userId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT;
                    $roleType = CouponGroupEnum::ROLE_TYPE_USER;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_INVITER_COUPON) {
                    $toUserId = $this->_referUserId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_COUPON;
                    $roleType = CouponGroupEnum::ROLE_TYPE_INVITER;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_INVITER_DISCOUNT) {
                    $toUserId = $this->_referUserId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT;
                    $roleType = CouponGroupEnum::ROLE_TYPE_INVITER;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_INVITER_NOTE) {
                    $toUserId = $this->_referUserId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_NOTE;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_USER_NOTE) {
                    $toUserId = $this->_userId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_NOTE;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_USER_REBATE_COUPON) {
                    $toUserId = $this->_userId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_COUPON;
                    $roleType = CouponGroupEnum::ROLE_TYPE_USER;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_INVITER_REBATE_COUPON) {
                    $toUserId = $this->_referUserId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_COUPON;
                    $roleType = CouponGroupEnum::ROLE_TYPE_INVITER;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_USER_GOLD) {
                    $toUserId = $this->_userId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_GOLD;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_SERVICER_COUPON) {
                    $toUserId = $this->_serviceUserId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_COUPON;
                    $roleType = CouponGroupEnum::ROLE_TYPE_SERVICER;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_SERVICER_DISCOUNT) {
                    $toUserId = $this->_serviceUserId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT;
                    $roleType = CouponGroupEnum::ROLE_TYPE_SERVICER;
                } else if ($reward['type'] == CouponGroupEnum::TRIGGER_REWARD_SERVICER_REBATE_COUPON) {
                    $toUserId = $this->_serviceUserId;
                    $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_COUPON;
                    $roleType = CouponGroupEnum::ROLE_TYPE_SERVICER;
                }

                // 开始返利, 收入方不能为空
                if (empty($toUserId)) {
                    PaymentApi::log("O2OService.O2ORebateTriggerEvent: 收入方为空", Logger::ERR);
                    continue;
                }

                if ($allowanceType == CouponGroupEnum::ALLOWANCE_TYPE_GOLD) {
                    if (empty($reward['rebateAmount']) || empty($reward['wxUserId'])) {
                        PaymentApi::log("O2OService.O2ORebateTriggerEvent: 赠金克重，或出资方为空", Logger::ERR);
                        continue;
                    }

                    // 买金赠金
                    $buyPrice = (new \core\service\GoldService())->getGoldPrice(true);
                    $rebateConf = array(
                        'wxUserId' => $reward['wxUserId'],
                        'logId' => $this->_acqurieLogId,
                        'dealLoadId' => $this->_dealLoadId,
                        'token' => $token,
                        'siteId' => $this->_siteId,
                    );
                    $goldBidRebateService = new GoldBidRebateService(
                        $this->_userId,
                        $reward['rebateAmount'],
                        $buyPrice['data']['gold_price'],
                        '',         // 邀请码 不传
                        $this->_rebateGoldOrderId,
                        $rebateConf
                    );
                    $goldBidRebateService->doBid();
                } else {
                    // 从reward里面取rebateAmount和rebateLimit
                    $rebateAmount = empty($reward['money']) ? 0 : $reward['money'];
                    $rebateLimit = empty($reward['dayLimit']) ? 0 : $reward['dayLimit'] * 86400;

                    $allowanceId = '';
                    if ($allowanceType == CouponGroupEnum::ALLOWANCE_TYPE_COUPON) {
                        // 返礼券
                        $coupons = $allowanceService->rebateCoupons(
                            $toUserId,
                            $reward['value'],
                            $token,
                            $this->_dealLoadId,
                            $rebateAmount,
                            $rebateLimit,
                            true,
                            $this->_acqurieLogId
                        );

                        // 获取返的礼券id，多个用逗号进行分割
                        if ($coupons) {
                            $couponIds = array();
                            foreach ($coupons as $item) {
                                $couponIds[] = $item['coupon']['id'];
                            }
                            $allowanceId = implode(',', $couponIds);
                        }
                    } else if ($allowanceType == CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT) {
                        // 返投资券
                        $discounts = $allowanceService->rebateDiscounts(
                            $toUserId,
                            $reward['value'],
                            $token,
                            $this->_dealLoadId,
                            '触发返投资券',
                            $rebateAmount,
                            $rebateLimit
                        );

                        // 获取返的投资券id，多个用逗号进行分割
                        if ($discounts) {
                            $discountIds = array();
                            foreach ($discounts as $discount) {
                                $discountIds[] = $discount['id'];
                            }
                            $allowanceId = implode(',', $discountIds);
                        }
                    }

                    $this->addTriggerAllowanceLog(
                        $toUserId,
                        $allowanceType,
                        $reward['value'],
                        $allowanceId,
                        $token
                    );

                    // 邀请返利相关
                    if ($roleType) {
                        $this->addTriggerRebateLog($roleType, $toUserId, $trigger['id'], $token,$allowanceId, $allowanceType);
                    }
                }
            }
        }
    }

    /**
     * addTriggerRebateLog 邀请返利记录
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2019-04-11
     * @param mixed $roleType
     * @param mixed $toUserId
     * @param mixed $triggerRuleId
     * @param mixed $token
     * @param mixed $allowanceId
     * @param mixed $allowanceType
     * @access private
     * @return void
     */
    private function addTriggerRebateLog($roleType, $toUserId, $triggerRuleId, $token, $allowanceId, $allowanceType) {
        $rewardData = array(
            'userId' => $toUserId,
            'roleType' => $roleType,
            'relationUserId' => $this->_userId,
            'dealLoadId' => $this->_dealLoadId,
            'triggerRuleId' => $triggerRuleId,
            'token' => $token,
            'status' => OtoAllowanceLogModel::STATUS_DONE,
            'allowanceMoney' => 0,
            'allowanceId' => $allowanceId,
            'allowanceType' => $allowanceType,
            'triggerType' => $this->_triggerType,
            'dealType' => $this->_consumeType,
            'createTime' => time(),
            //TODO待补充
            'extra' => array('inviteUserId' => $this->_referUserId, 'serviceUserId'=> $this->_serviceUserId),
        );
        PaymentApi::log("O2OService.O2ORebateTriggerEvent触发返利参数, params: ".json_encode($rewardData));
        Msgbus::instance()->produce('trigger_o2o_rebate_coupon', $rewardData);
    }

    /**
     * 添加触发返利记录
     */
    private function addTriggerAllowanceLog($toUserId, $allowanceType, $allowanceCoupon, $allowanceId, $token) {
        $currentTime = time();

        // 添加触发返利记录
        $data = array();
        $data['from_user_id'] = $this->_userId; // 对于触发，表示投资人UserId
        $data['to_user_id'] = $toUserId;        // 返利人获得者UserId
        $data['acquire_log_id'] = $this->_acqurieLogId;
        $data['gift_id'] = 0;
        $data['gift_group_id'] = 0;
        $data['deal_load_id'] = $this->_dealLoadId;
        $data['action_type'] = OtoAllowanceLogModel::ACTION_TYPE_TRIGGER;
        $data['create_time'] = $currentTime;
        $data['update_time'] = $currentTime;
        $data['allowance_type'] = $allowanceType;
        $data['allowance_money'] = 0;
        $data['allowance_coupon'] = $allowanceCoupon;
        $data['allowance_id'] = $allowanceId;
        $data['token'] = $token;
        $data['status'] = OtoAllowanceLogModel::STATUS_DONE;
        $data['site_id'] = $this->_siteId;
        $allowanceLogId = OtoAllowanceLogModel::instance()->addRecord($data);
        if (!$allowanceLogId) {
            throw new \Exception('添加触发返利记录失败');
        }

        return $allowanceLogId;
    }

    /**
     * 是否发送消息
     */
    private function sendTriggerMessage($triggers) {
        $userId = $this->_userId;
        $referUserId = $this->_referUserId;
        $msgBoxService = new MsgBoxService();
        $users = array();
        foreach ($triggers as $trigger) {
            if (empty($trigger['push'])) {
                continue;
            }

            try {
                foreach ($trigger['push'] as $item) {
                    $pushUserId = 0;
                    if ($item['pushType'] == CouponGroupEnum::PUSH_TYPE_WX_OWNER) {
                        $pushUserId = $userId;
                    } else if($item['pushType'] == CouponGroupEnum::PUSH_TYPE_WX_INVITER) {
                        $pushUserId = $referUserId;
                    }

                    if (!empty($pushUserId)) {
                        if (!empty($item['msgBoxTitle']) && !empty($item['msgBoxBody'])) {
                            $msgBoxService->create($pushUserId, MsgBoxEnum::TYPE_O2O_COUPON, $item['msgBoxTitle'], $item['msgBoxBody']);
                        }
                        if (!empty($item['sms'])) {
                            // 发送短信
                            if (!isset($users[$pushUserId])) {
                                $users[$pushUserId] = UserModel::instance()->findViaSlave($pushUserId, 'mobile');
                            }

                            $userInfo = $users[$pushUserId];
                            SmsServer::instance()->send($userInfo['mobile'], 'TPL_SMS_O2O_COMMON', array($item['sms']), $pushUserId);
                        }
                    }
                }
            } catch (\Exception $e) {
                PaymentApi::log('send trigger message failed, userId: '.$userId.', referUserId: '.$referUserId
                    .', data:'.json_encode($trigger['push'], JSON_UNESCAPED_UNICODE).', msg: '.$e->getMessage(), Logger::ERR);
            }
        }
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'luzhengshuai@ucfgroup.com');
    }
}
