<?php

namespace core\event;

use core\event\BaseEvent;
use core\service\vip\VipService;
use core\service\O2OService;
use libs\utils\PaymentApi;
use core\dao\vip\VipRateLogModel;
use libs\utils\Logger;

class VipRebateEvent extends BaseEvent {
    // vip投资返利
    private $userId;
    private $dealLoadId;
    private $token;

    public function __construct($userId, $dealLoadId, $token) {
        $this->userId = $userId;
        $this->dealLoadId = $dealLoadId;
        $this->token = $token;
    }

    public function execute() {
        if (empty($this->token)) {
            throw new \Exception('vip返利异常:token不能为空');
        }
        // 记录返利信息
        $logParams = array('userId'=>$this->userId, 'dealLoadId'=>$this->dealLoadId, 'token'=>$this->token);
        PaymentApi::log('vipRebate executing, params: '.json_encode($logParams, JSON_UNESCAPED_UNICODE));

        // 幂等判断
        $condition = "token='{$this->token}'";
        $logInfo = VipRateLogModel::instance()->findBy($condition, 'id, create_time, coupon_id, coupon_group_id, allowance_money');
        if (empty($logInfo)) {
            PaymentApi::log('vip返利记录不存在token:'.$this->token, Logger::WARN);
            return true;
        }

        // 幂等判断
        if ($logInfo['coupon_id'] > 0) {
            PaymentApi::log("vip返利已处理token:".$this->token, Logger::INFO);
            return true;
        }
        $o2oService = new O2OService();
        // 需要保证acquireAllowanceCoupon操作的幂等
        $res = $o2oService->acquireAllowanceCoupon($logInfo['coupon_group_id'], $this->userId,
            $this->token, '', $this->dealLoadId, $logInfo['allowance_money']);

        if ($res === false) {
            $errMsg = $o2oService->getErrorMsg();
            PaymentApi::log("VipService.VipRebateEvent: ".$errMsg, Logger::ERR);
            throw new \Exception('vip返利失败,token '. $this->token. 'ERR:'.$errMsg, $o2oService->getErrorCode());
        }
        $coupon_id = $res['coupon']['id'];
        // 根据返回的coupon_id更新vip返利记录
        $updateCond = "token = '{$this->token}' AND coupon_id=0";
        $updateRows = VipRateLogModel::instance()->updateAll(array('coupon_id' => $coupon_id), $updateCond, true);
        if ($updateRows != 1) {
            throw new \Exception('更新vip返利状态失败token:'.$this->token);
        }
        return true;
    }


    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com');
    }
}
