<?php

namespace core\event;

use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\event\BaseEvent;
use core\dao\OtoAcquireLogModel;
use core\service\UserService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\oto\O2OUtils;
use core\dao\OtoAllowanceLogModel;

// o2o领券返利
class O2ORebateAcquireEvent extends BaseEvent {
    private $_couponGroup;
    private $_coupon;
    private $_product;
    private $_dealLoadId;
    private $_siteId;
    private $_logId;

    public function __construct($response, $dealLoadId = 0, $siteId = 1) {
        $this->_couponGroup = $response['couponGroup'];
        $this->_coupon = $response['coupon'];
        $this->_product = $response['product'];
        $this->_dealLoadId = $dealLoadId;
        $this->_siteId = $siteId;
        $this->_logId = isset($response['logId']) ? $response['logId'] : 0;
    }

    public function execute() {
        $userId = $this->_coupon['ownerUserId'];
        $couponGroup = $this->_couponGroup;
        $coupon = $this->_coupon;
        $product = $this->_product;

        PaymentApi::log("O2OService.O2ORebateAcquireEvent领券返利, couponId: ".$coupon['id'].', allowance: '
            .json_encode($coupon['allowance'], JSON_UNESCAPED_UNICODE), Logger::INFO);

        // 获取邀请人
        $acquireLogInfo = OtoAcquireLogModel::instance()->getByGiftId($coupon['id'], true);
        $acquireLogId = $this->_logId;
        if ($acquireLogInfo) {
            $acquireLogId = $acquireLogInfo['id'];
            $referUserId = O2OUtils::getReferId($userId, $acquireLogInfo['trigger_mode'], $acquireLogInfo['deal_load_id']);
        } else {
            $referUserId = O2OUtils::getReferId($userId, 0, 0);
        }

        $userService = new UserService();
        $userColumns = 'id, user_name, real_name';
        $user = $userService->getUserArray($userId, $userColumns, false);
        $referUser = $userService->getUserArray($referUserId, $userColumns, false);
        $wxUser = $userService->getUserArray($couponGroup['wxUserId'], $userColumns, false);

        $allowanceService = new \core\service\oto\O2OAllowanceService();
        $currentTime = time();
        $coupon['productName'] = $product['productName'];
        // 先从acquire log里面获取dealLoadId，如果不存在，再取传递的值
        $dealLoadId = $acquireLogInfo ? $acquireLogInfo['deal_load_id'] : $this->_dealLoadId;
        $coupon['dealLoadId'] = $dealLoadId;
        // 券来源展示
        $coupon['remark'] = $couponGroup['fromSiteDesc'];

        $isAllowanced = false;
        // 处理领取返利
        foreach ($coupon['allowance'] as $allowance) {
            // 查询返利凭证是否存在
            $token = 'acquire_'.$coupon['id'].'_'.$allowance['mode'];
            $condition = "token = '{$token}'";
            $logInfo = OtoAllowanceLogModel::instance()->findBy($condition, 'id');
            // 已经返利过了，保证操作的幂等
            if ($logInfo) {
                $isAllowanced = true;
                PaymentApi::log('兑换返利已完成', Logger::INFO);
                continue;
            }

            // 开始处理返利
            $fromUserId = 0;
            $toUserId = 0;
            $payinUser = array();
            $payoutUser = array();
            if ($allowance['mode'] == CouponGroupEnum::ACQUIRE_WX_INVITER) {
                // 给邀请人返利
                $fromUserId = $couponGroup['wxUserId'];
                $toUserId = $referUserId;
                $payinUser = $referUser;
                $payoutUser = $wxUser;
            } else if ($allowance['mode'] == CouponGroupEnum::ACQUIRE_WX_OWNER) {
                // 给投资人返利
                $fromUserId = $couponGroup['wxUserId'];
                $toUserId = $userId;
                $payinUser = $user;
                $payoutUser = $wxUser;
            } else {
                // 其他的返利方式，非领取返利，则不进行处理
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
                $data['action_type'] = OtoAllowanceLogModel::ACTION_TYPE_ACQUIRE;
                $data['create_time'] = $currentTime;
                $data['update_time'] = $currentTime;
                $data['allowance_type'] = $allowance['type'];
                $data['allowance_id'] = $allowanceId;

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
                'storeId'=>'',
                'referUserId'=>$referUserId,
                'ownerUserId'=>$userId
            );
            $pushUsersName = array(
                'ownerUserId' => $user['real_name'],
                'referUserId' => $referUser['real_name']
            );
            $allowanceService->pushMsg($pushUsers, $couponGroup['push'], false, $pushUsersName, $coupon['id']);
        }

        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'luzhengshuai@ucfgroup.com');
    }
}
