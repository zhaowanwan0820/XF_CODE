<?php
/**
 * 超级账户-用户解绑银行卡Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

class UcfpayCardUnbindEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;
    /**
     * 用户银行卡号
     * @var string
     */
    private $bankcardNo;

    public function __construct($userId, $bankcardNo) {
        $this->userId = intval($userId);
        $this->bankcardNo = trim($bankcardNo);
    }

    /**
     * 超级账户-用户解绑银行卡
     */
    public function execute() {
        $paymentService = new \core\service\PaymentService();
        $ret = $paymentService->unbindCard($this->userId, $this->bankcardNo);
        if (false === $ret) {
            $this->setError('超级账户：解绑银行卡失败');
            return false;
        }
        return true;
    }
}