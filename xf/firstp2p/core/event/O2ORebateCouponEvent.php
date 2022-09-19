<?php
namespace core\event;

use core\event\BaseEvent;
use core\service\O2OService;
use core\exception\O2OException;
use libs\utils\Logger;
use libs\utils\PaymentApi;

// 返礼券接口
class O2ORebateCouponEvent extends BaseEvent {
    private $userId;
    private $couponGroupId;
    private $token;
    private $dealLoadId;
    private $rebateAmount;
    private $rebateLimit;

    public function __construct($userId, $couponGroupId, $token, $dealLoadId = 0, $rebateAmount = 0, $rebateLimit = 0) {
        $this->userId = $userId;
        $this->couponGroupId = $couponGroupId;
        $this->token = $token;
        $this->dealLoadId = $dealLoadId;
        $this->rebateAmount = $rebateAmount;
        $this->rebateLimit = $rebateLimit;
    }

    public function execute() {
        $params = array(
            'userId'=>$this->userId,
            'couponGroupId'=>$this->couponGroupId,
            'token'=>$this->token,
            'dealLoadId'=>$this->dealLoadId,
            'rebateAmount'=>$this->rebateAmount,
            'rebateLimit'=>$this->rebateLimit
        );
        PaymentApi::log("O2OService.O2ORebateCouponEvent: execute, params"
            .json_encode($params, JSON_UNESCAPED_UNICODE), Logger::INFO);

        $o2oService = new O2OService();
        // 需要保证acquireAllowanceCoupon操作的幂等
        $res = $o2oService->acquireAllowanceCoupon($this->couponGroupId, $this->userId,
            $this->token, '', $this->dealLoadId, $this->rebateAmount, $this->rebateLimit);

        if ($res === false) {
            $errMsg = $o2oService->getErrorMsg();
            PaymentApi::log("O2OService.O2ORebateCouponEvent: ".$errMsg, Logger::ERR);
            throw new O2OException('O2O礼券返利失败, '.$errMsg, $o2oService->getErrorCode());
        }
        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'luzhengshuai@ucfgroup.com');
    }
}