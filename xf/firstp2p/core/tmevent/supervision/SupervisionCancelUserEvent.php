<?php
/**
 * 存管账户-账户销户的Event
 * 
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\SupervisionAccountService;

class SupervisionCancelUserEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;

    public function __construct($userId) {
        $this->userId = intval($userId);
    }

    /**
     * 发起存管-账户销户请求
     * 
     */
    public function commit() {
        $supervisionAccountObj = new SupervisionAccountService();
        $result = $supervisionAccountObj->supervisionMemberCancel($this->userId);
        if (SupervisionAccountService::RESPONSE_SUCCESS !== $result['status']) {
            throw new \Exception('存管账户：' . $result['respMsg']);
        }
        return true;
    }
}