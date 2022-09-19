<?php
/**
 * 超级账户-账户销户的Event
 * 
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\PaymentUserAccountService;

class UcfpayCancelUserEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;

    public function __construct($userId) {
        $this->userId = intval($userId);
    }

    /**
     * 发起超级账户-账户销户请求
     * 
     */
    public function execute() {
        $paymentUserAccountObj = new PaymentUserAccountService();
        $result = $paymentUserAccountObj->cancelUser($this->userId);
        if (false === $result['ret']) {
            $this->setError('超级账户：' . $result['msg']);
            return false;
        }
        return true;
    }
}