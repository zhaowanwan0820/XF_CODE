<?php

namespace core\event;

use libs\utils\Logger;
use core\event\BaseEvent;
use libs\utils\PaymentApi;
use core\dao\OtoAllowanceLogModel;
use core\dao\UserModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;
use core\service\MsgBoxService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

/**
 * 游戏抽奖返利
 */
class O2ORebateGameEvent extends BaseEvent {
    private $_userId;
    private $_eventId;
    private $_token;
    private $_prize;
    private $_siteId;

    public function __construct($userId, $eventId, $token, $prize, $siteId =1 ) {
        $this->_userId = $userId;
        $this->_eventId = $eventId;
        $this->_token = $token;
        $this->_prize = $prize;
        $this->_siteId = $siteId;
    }

    public function execute() {
        $gameToken = 'game_'.$this->_token;
        $condition = "token = '{$gameToken}'";
        $logInfo = OtoAllowanceLogModel::instance()->findBy($condition, 'id');
        // 已经返利过了，保证操作的幂等
        if ($logInfo) {
            PaymentApi::log('游戏抽奖返利已完成', Logger::INFO);
            return true;
        }

        $allowanceService = new \core\service\oto\O2OAllowanceService();
        $lotteryPrize = $this->_prize;
        // 现在就一个
        $groupId = intval($lotteryPrize['allowanceGroupId']);
        $allowanceId = 0;
        $allowanceType = 0;
        if ($lotteryPrize['allowanceType'] == GameEnum::ALLOWANCE_TYPE_COUPON) {
            // 返礼券
            $coupons = $allowanceService->rebateCoupons($this->_userId, $groupId, $this->_token);
            $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_COUPON;
            // 获取返的礼券id，多个用逗号进行分割
            if ($coupons) {
                $couponIds = array();
                foreach ($coupons as $item) {
                    $couponIds[] = $item['coupon']['id'];
                }
                $allowanceId = implode(',', $couponIds);
            }
        } else if ($lotteryPrize['allowanceType'] == GameEnum::ALLOWANCE_TYPE_DISCOUNT) {
            // 返投资券
            $discounts = $allowanceService->rebateDiscounts($this->_userId, $groupId, $this->_token, 0, '游戏返投资券');
            $allowanceType = CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT;
            // 获取返的投资券id，多个用逗号进行分割
            if ($discounts) {
                $discountIds = array();
                foreach ($discounts as $discount) {
                    $discountIds[] = $discount['id'];
                }
                $allowanceId = implode(',', $discountIds);
            }
        }

        $currentTime = time();
        // 添加触发返利记录
        $data = array();
        $data['from_user_id'] = $lotteryPrize['id'];  // 对于game表示prizeId
        $data['to_user_id'] = $this->_userId;
        $data['acquire_log_id'] = 0;
        $data['gift_id'] = $this->_eventId;           // 对于game表示eventId
        $data['gift_group_id'] = 0;
        $data['deal_load_id'] = 0;
        $data['action_type'] = OtoAllowanceLogModel::ACTION_TYPE_GAME;
        $data['create_time'] = $currentTime;
        $data['update_time'] = $currentTime;
        $data['allowance_type'] = $allowanceType;
        $data['allowance_money'] = 0;
        $data['allowance_coupon'] = $groupId;
        $data['allowance_id'] = $allowanceId;
        $data['token'] = $gameToken;
        $data['status'] = OtoAllowanceLogModel::STATUS_DONE;
        $data['site_id'] = $this->_siteId;
        $allowanceLogId = OtoAllowanceLogModel::instance()->addRecord($data);

        $params = $data;
        $params['allowanceLogId'] = $allowanceLogId;
        PaymentApi::log("O2OService.O2ORebateGameEvent游戏抽奖返利完成, params: "
            .json_encode($params, JSON_UNESCAPED_UNICODE), Logger::INFO);

        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'luzhengshuai@ucfgroup.com');
    }
}