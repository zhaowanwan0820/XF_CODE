<?php

namespace core\event;

use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\event\BaseEvent;
use core\service\UserService;
use core\dao\OtoAcquireLogModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\oto\O2OUtils;
use core\dao\OtoAllowanceLogModel;

class O2ORebateExchangeEvent extends BaseEvent {
    private $_coupon;
    private $_storeId;
    private $_dealLoadId;
    private $_siteId;
    private $_logId;

    public function __construct($storeId, $coupon, $dealLoadId = 0, $siteId = 1) {
        $this->_storeId = $storeId;
        $this->_coupon = $coupon;
        $this->_dealLoadId = $dealLoadId;
        $this->_siteId = $siteId;
        // 这里的logId，是为了关联allowance_log之间的多条记录
        $this->_logId = isset($coupon['logId']) ? $coupon['logId'] : 0;
    }

    public function execute() {
        $couponGroup = $this->_coupon['couponGroup'];
        $coupon = $this->_coupon['coupon'];
        $product = $this->_coupon['product'];
        $storeId = $this->_storeId;
        $userId = $coupon['ownerUserId'];
        PaymentApi::log("O2OService.O2ORebateExchangeEvent兑券返利, couponId: ".$coupon['id'].', allowance: '
            .json_encode($coupon['allowance'], JSON_UNESCAPED_UNICODE), Logger::INFO);

        $userService = new UserService();
        // 获取六个用户的信息
        $userColumns = 'id, user_name, real_name';
        $wxUserInfo = $userService->getUserArray($couponGroup['wxUserId'], $userColumns, false);
        $supUserInfo = $userService->getUserArray($couponGroup['supplierUserId'], $userColumns, false);
        $channelInfo = $userService->getUserArray($couponGroup['channelId'], $userColumns, false);
        $storeUserInfo = $userService->getUserArray($storeId, $userColumns, false);
        $userInfo = $userService->getUserArray($userId, $userColumns, false);

        // 获取邀请人
        $acquireLogInfo = OtoAcquireLogModel::instance()->getByGiftId($coupon['id'], true);
        $acquireLogId = $this->_logId;
        if ($acquireLogInfo) {
            $acquireLogId = $acquireLogInfo['id'];
            $referUserId = O2OUtils::getReferId($userId, $acquireLogInfo['trigger_mode'], $acquireLogInfo['deal_load_id']);
        } else {
            $referUserId = O2OUtils::getReferId($userId, 0, 0);
        }

        $referUser = $userService->getUserArray($referUserId, $userColumns, false);
        $currentTime = time();
        $coupon['productName'] = $product['productName'];
        // 优先从acquire log里面取交易id，不存在，则取传递的值
        $dealLoadId = isset($acquireLogInfo['deal_load_id']) ? $acquireLogInfo['deal_load_id'] : $this->_dealLoadId;
        $coupon['dealLoadId'] = $dealLoadId;
        // 券来源展示
        $coupon['remark'] = $couponGroup['fromSiteDesc'];

        // 记录是否已经返利过了，用来防止重复推送消息
        $isAllowanced = false;
        $allowanceService = new \core\service\oto\O2OAllowanceService();
        // 处理兑换返利
        foreach ($coupon['allowance'] as $allowance) {
            // 查询返利凭证是否存在
            $token = 'exchange_'.$coupon['id'].'_'.$allowance['mode'];
            $condition = "token = '{$token}'";
            $logInfo = OtoAllowanceLogModel::instance()->findBy($condition, 'id');
            // 已经返利过了，保证操作的幂等
            if ($logInfo) {
                $isAllowanced = true;
                PaymentApi::log('兑换返利已完成', Logger::INFO);
                continue;
            }

            $toUserId = 0;
            $fromUserId = 0;
            $payinUser = array();
            $payoutUser = array();
            if ($allowance['mode'] == CouponGroupEnum::EXCHANGE_WX_SUPPLIER) {
                $fromUserId = $couponGroup['wxUserId'];
                $toUserId = $couponGroup['supplierUserId'];
                $payinUser = $supUserInfo;
                $payoutUser = $wxUserInfo;
            } else if ($allowance['mode'] == CouponGroupEnum::EXCHANGE_WX_CHANNEL) {
                $fromUserId = $couponGroup['wxUserId'];
                $toUserId = $couponGroup['channelId'];
                $payinUser = $channelInfo;
                $payoutUser = $wxUserInfo;
            } else if ($allowance['mode'] == CouponGroupEnum::EXCHANGE_WX_STORE) {
                $fromUserId = $couponGroup['wxUserId'];
                $toUserId = $storeId;
                $payinUser = $storeUserInfo;
                $payoutUser = $wxUserInfo;
            } else if ($allowance['mode'] == CouponGroupEnum::EXCHANGE_SUPPLIER_STORE) {
                $fromUserId = $couponGroup['supplierUserId'];
                $toUserId = $storeId;
                $payinUser = $storeUserInfo;
                $payoutUser = $supUserInfo;
            } else if ($allowance['mode'] == CouponGroupEnum::EXCHANGE_CHANNEL_STORE) {
                $fromUserId = $couponGroup['channelId'];
                $toUserId = $storeId;
                $payinUser = $storeUserInfo;
                $payoutUser = $channelInfo;
            } else if ($allowance['mode'] == CouponGroupEnum::EXCHANGE_WX_OWNER) {
                $fromUserId = $couponGroup['wxUserId'];
                $toUserId = $userId;
                $payinUser = $userInfo;
                $payoutUser = $wxUserInfo;
            } else if ($allowance['mode'] == CouponGroupEnum::EXCHANGE_WX_INVITER) {
                $fromUserId = $couponGroup['wxUserId'];
                $toUserId = $referUserId;
                $payinUser = $referUser;
                $payoutUser = $wxUserInfo;
            } else {
                // 其他的返利方式，非兑换返利，则不进行处理
                continue;
            }

            // 收入方不能为空
            if ($toUserId > 0) {
                // 返利处理
                $allowanceId = $allowanceService->subsidy($payoutUser, $payinUser, $allowance, $coupon['id'], $coupon);

                // 添加触发返利记录
                $data = array();
                $data['from_user_id'] = $fromUserId;
                $data['to_user_id'] = $toUserId;
                $data['site_id'] = $this->_siteId;
                $data['acquire_log_id'] = $acquireLogId;
                $data['gift_id'] = $coupon['id'];
                $data['gift_group_id'] = $coupon['couponGroupId'];
                $data['deal_load_id'] = $dealLoadId;
                $data['action_type'] = OtoAllowanceLogModel::ACTION_TYPE_EXCHANGE;
                $data['create_time'] = $currentTime;
                $data['update_time'] = $currentTime;
                $data['allowance_type'] = $allowance['type'];
                $data['allowance_id'] = $allowanceId ? $allowanceId : 0;

                $allowanceMoney = 0;
                if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY
                    || $allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS
                    || $allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_LUCKYMONEY) {
                    $allowanceMoney = $allowance['money'];
                }
                $data['allowance_money'] = $allowanceMoney;

                $allowanceCoupon = '';
                if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_COUPON) {
                    $allowanceCoupon = $allowance['couponGroupId'];
                } else if ($allowance['type'] == CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT) {
                    $allowanceCoupon = $allowance['discountId'];
                }
                $data['allowance_coupon'] = $allowanceCoupon;

                $data['token'] = $token;
                $data['status'] = OtoAllowanceLogModel::STATUS_DONE;
                $allowanceLogId = OtoAllowanceLogModel::instance()->addRecord($data);
                if (!$allowanceLogId) {
                    throw new \Exception('添加触发返利记录失败');
                }
            }
        }

        if (!$isAllowanced) {
            // 推送消息
            $pushUsers = array(
                'wxUserId'=>$couponGroup['wxUserId'],
                'supplierUserId'=>$couponGroup['supplierUserId'],
                'channelId'=>$couponGroup['channelId'],
                'storeId'=>$storeId,
                'referUserId'=>$referUserId,
                'ownerUserId'=>$userId
            );
            $pushUsersName = array(
                'ownerUserId' => $userInfo['real_name'],
                'referUserId' => $referUser['real_name']
            );
            $allowanceService->pushMsg($pushUsers, $couponGroup['push'], true, $pushUsersName, $coupon['id']);
        }

        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'luzhengshuai@ucfgroup.com');
    }
}
