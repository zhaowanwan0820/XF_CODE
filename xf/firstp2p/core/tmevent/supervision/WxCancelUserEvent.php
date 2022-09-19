<?php
/**
 * 网信理财-账户销户的Event
 * 
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\UserService;

class WxCancelUserEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;

    public function __construct($userId) {
        $this->userId = intval($userId);
    }

    /**
     * 网信理财-账户销户
     */
    public function commit() {
        $userObj = new UserService();
        $userObj->wxMemberCancel($this->userId);
        return true;
    }
}