<?php
/**
 * 网信理财-账户销户的Event
 * 
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\account\AccountService;

class WxCancelUserEvent extends GlobalTransactionEvent {
    /**
     * 账户ID
     * @var int
     */
    private $accountId;

    public function __construct($accountId) {
        $this->accountId = intval($accountId);
    }

    /**
     * 网信理财-账户销户
     */
    public function commit() {
        AccountService::removeAccount($this->accountId);
        return true;
    }
}