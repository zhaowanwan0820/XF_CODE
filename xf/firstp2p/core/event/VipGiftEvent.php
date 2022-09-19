<?php

namespace core\event;

use core\event\BaseEvent;
use core\service\vip\VipService;
use core\service\O2OService;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\dao\vip\VipGiftLogModel;
use core\dao\UserModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use core\service\MsgBoxService;
use libs\sms\SmsServer;

class VipGiftEvent extends BaseEvent {
    // vip礼包
    private $userId;
    private $giftLogId;
    private $token;
    private $withSms;
    private $withPush;

    public function __construct($userId, $giftLogId, $withSms, $withPush) {
        $this->userId = $userId;
        $this->giftLogId = $giftLogId;
        $this->withSms = $withSms;
        $this->withPush = $withPush;
    }

    public function execute() {
        // 记录返利信息
        $logParams = array('userId'=>$this->userId, 'giftLogId'=>$this->giftLogId);
        PaymentApi::log('vipGift executing, params: '.json_encode($logParams, JSON_UNESCAPED_UNICODE));

        // 幂等判断
        $condition = "id=".$this->giftLogId;
        $logInfo = VipGiftLogModel::instance()->findBy($condition, 'id, create_time, gift_type, gift_info, token, award_type');
        if (empty($logInfo)) {
            PaymentApi::log('vip礼包记录不存在', Logger::WARN);
            return true;
        }

        // 幂等判断
        if ($logInfo['status'] > 0) {
            PaymentApi::log("vip礼包已处理", Logger::INFO);
            return true;
        }

        $o2oService = new O2OService();
        $vipService = new vipService();
        // 根据vip奖励类型获取对应的礼包信息
        $giftInfo = json_decode($logInfo['gift_info'], true);
        $giftGroupId = $giftInfo['groupId'];
        $this->token = $logInfo['token'];
        if ($logInfo['gift_type'] == CouponGroupEnum::ALLOWANCE_TYPE_COUPON) {
            // 需要保证acquireAllowanceCoupon操作的幂等
            $res = $o2oService->acquireCoupons($this->userId, $giftGroupId, $this->token, '', $this->giftLogId, true);
            if ($res === false) {
                $errMsg = $o2oService->getErrorMsg();
                PaymentApi::log("VipService.VipGiftEvent: ".$errMsg, Logger::ERR);
                throw new \Exception('vip礼包发送失败, '.$errMsg, $o2oService->getErrorCode());
            }

            $couponIds = array();
            foreach ($res as $couponGroupId=>$coupon) {
                $couponIds[] = $coupon['coupon']['id'];
            }

            $coupon_id = implode(',', $couponIds);
            // 根据返回的coupon_id更新vip礼包记录
            $updateCond = "id = '{$this->giftLogId}' AND status=0";
            $updateRows = VipGiftLogModel::instance()->updateAll(array('allowance' => $coupon_id, 'status' => 1), $updateCond, true);
            if ($updateRows != 1) {
                throw new \Exception('更新vip礼包状态失败');
            }
        } else if ($logInfo['gift_type'] == CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT) {
            $res = $o2oService->acquireDiscounts($this->userId, $giftGroupId, $this->token, $this->giftLogId, 'vip_gift_log_'.$this->giftLogId, true);
            if ($res === false) {
                $errMsg = $o2oService->getErrorMsg();
                PaymentApi::log("VipService.VipGiftEvent: ".$errMsg, Logger::ERR);
                throw new \Exception('vip礼包发送失败, '.$errMsg, $o2oService->getErrorCode());
            }

            $discountIds = array();
            foreach ($res as $discountGroupId=>$discount) {
                $discountIds[] = $discount['id'];
            }

            $coupon_id = implode(',', $discountIds);
            // 根据返回的coupon_id更新vip礼包记录
            $updateCond = "id = '{$this->giftLogId}' AND status=0";
            $updateRows = VipGiftLogModel::instance()->updateAll(array('allowance' => $coupon_id, 'status' => 1), $updateCond, true);
            if ($updateRows != 1) {
                throw new \Exception('更新vip礼包状态失败');
            }
        }

        // 推送相关，只有升级需要发短信，其他只需要app推送
        $vipInfo = $vipService->getVipGrade($this->userId);
        if (!empty($giftInfo['pushMsg']) && $this->withPush) {
            PaymentApi::log('VipService.pushMsg , userId|'.$this->userId.' data|pushMsg'.$giftInfo['pushMsg']);
            $searchName = array('{time}', '{name}');
            $replaceName = array(date('Y-m-d H:i:s'), $vipInfo['name']);
            $pushMsg = str_replace($searchName, $replaceName, $giftInfo['pushMsg']);
            $msgBoxService = new MsgBoxService();
            $msgInfo = $this->getVipMsgTitleAndType($logInfo['award_type']);
            $msgTitle = $msgInfo['title'];
            $msgType = $msgInfo['type'];
            $extraContent = array(
#                'turn_type' => ($logInfo['award_type'] == VipGiftLogModel::VIP_AWARD_TYPE_UPGRADE) ? MsgBoxEnum::TURN_TYPE_VIP : MsgBoxEnum::TURN_TYPE_DISCOUNT//app跳转类型标识
            );
            $msgBoxService->create($this->userId, $msgType, $msgTitle, $giftInfo['pushMsg'], $extraContent);
        }

        $userInfo = UserModel::instance()->findViaSlave($this->userId, 'mobile, real_name');
        if ($logInfo['award_type'] == VipGiftLogModel::VIP_AWARD_TYPE_UPGRADE) {
            if (!empty($giftInfo['smsId']) && $this->withSms) {
                // 发送短信
                $contentData = array(
                    'gradeName' => $vipInfo['name'],
                );
                SmsServer::instance()->send($userInfo['mobile'], $giftInfo['smsId'], $contentData, $this->userId);
                PaymentApi::log("VipService.pushMsg success, userId:".$this->userId.' mobile: '.$userInfo['mobile'].' data:'.$giftInfo['smsId']);
            }
        }

        if ($logInfo['award_type'] == VipGiftLogModel::VIP_AWARD_TYPE_BIRTHDAY) {
            //生日祝福短信
            $birthdayTpl = app_conf('VIP_BIRTHDAY_SMS');
            if ($birthdayTpl) {
                $contentData = array(
                );
                SmsServer::instance()->send($userInfo['mobile'], $birthdayTpl, $contentData, $this->userId);
                PaymentApi::log("VipService.pushBirthdaySms success, userId:".$this->userId.' mobile: '.$userInfo['mobile'].' data:'.$birthdayTpl);
            }
        }

        return true;
    }

    private function getVipMsgTitleAndType($awardType) {
        $title = '会员服务消息';
        $type = MsgBoxEnum::TYPE_VIP_UPGRADE;
        switch ($awardType) {
            case VipGiftLogModel::VIP_AWARD_TYPE_UPGRADE:
                $title = '会员升级';
                break;
            case VipGiftLogModel::VIP_AWARD_TYPE_BIRTHDAY:
                $title = '会员生日';
                $type = MsgBoxEnum::TYPE_VIP_BIRTHDAY;
                break;
            case VipGiftLogModel::VIP_AWARD_TYPE_ANNIVERSARY:
                $title = '会员满周年';
                $type = MsgBoxEnum::TYPE_VIP_ANNIVERSARY;
                break;
        }
        return array('type' => $type, 'title' => $title);
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com');
    }
}
