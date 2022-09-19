<?php
/**
 * 存管账户-账户销户的Event
 * 
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\enum\SupervisionEnum;
use core\service\supervision\SupervisionAccountService;

class SupervisionCancelUserEvent extends GlobalTransactionEvent {
    /**
     * 账户ID
     * @var int
     */
    private $accountId;

    public function __construct($accountId) {
        $this->accountId = intval($accountId);
    }

    /**
     * 发起存管-账户销户请求
     */
    public function commit() {
        $supervisionAccountObj = new SupervisionAccountService();
        $result = $supervisionAccountObj->supervisionMemberCancel($this->accountId);
        if (SupervisionEnum::RESPONSE_SUCCESS !== $result['status']) {
            throw new \Exception('存管账户：' . $result['respMsg']);
        }
        return true;
    }
}