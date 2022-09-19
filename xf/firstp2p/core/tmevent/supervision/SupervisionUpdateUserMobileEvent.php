<?php
/**
 * 存管系统-修改手机号Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\SupervisionAccountService;

class SupervisionUpdateUserMobileEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;
    /**
     * 用户手机号
     * @var int
     */
    private $mobile;

    public function __construct($userId, $mobile) {
        $this->userId = intval($userId);
        $this->mobile = addslashes($mobile);
    }

    /**
     * 存管系统-修改手机号
     */
    public function commit() {
        $supervisionAccountService = new SupervisionAccountService();
        $supervisionAccountService->memberPhoneUpdate($this->userId, $this->mobile);
        return true;
    }
}