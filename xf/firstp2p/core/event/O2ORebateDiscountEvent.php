<?php
namespace core\event;

use core\event\BaseEvent;
use core\service\oto\O2ODiscountService;
use core\exception\O2OException;
use libs\utils\Logger;
use libs\utils\PaymentApi;

// 返投资券的event，方便异步去领取投资券
class O2ORebateDiscountEvent extends BaseEvent
{
    private $userId;
    private $discountGroupId;
    private $token;
    private $dealLoadId;
    private $remark;
    private $rebateAmount;
    private $rebateLimit;

    public function __construct($userId, $discountGroupId, $token, $dealLoadId,
                                $remark = '', $rebateAmount = 0, $rebateLimit = 0) {
        $this->userId = $userId;
        $this->discountGroupId = $discountGroupId;
        $this->token = $token;
        $this->dealLoadId = $dealLoadId;
        $this->remark = $remark;
        $this->rebateAmount = $rebateAmount;
        $this->rebateLimit = $rebateLimit;
    }

    public function execute() {
        $params = array(
            'userId'=>$this->userId,
            'discountGroupId'=>$this->discountGroupId,
            'token'=>$this->token,
            'dealLoadId'=>$this->dealLoadId,
            'remark'=>$this->remark,
            'rebateAmount'=>$this->rebateAmount,
            'rebateLimit'=>$this->rebateLimit
        );
        PaymentApi::log("O2OService.O2ORebateDiscountEvent: execute, params"
            .json_encode($params, JSON_UNESCAPED_UNICODE), Logger::INFO);

        $o2oService = new O2ODiscountService();
        $discount = $o2oService->acquireDiscount($this->userId, $this->discountGroupId,
            $this->token, $this->dealLoadId, $this->remark, $this->rebateAmount, $this->rebateLimit);

        if ($discount === false) {
            $errMsg = $o2oService->getErrorMsg();
            PaymentApi::log("O2OService.O2ORebateDiscountEvent: ".$errMsg, Logger::ERR);
            throw new O2OException('O2O礼券返利失败, '.$errMsg, $o2oService->getErrorCode());
        }
        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'luzhengshuai@ucfgroup.com');
    }
}