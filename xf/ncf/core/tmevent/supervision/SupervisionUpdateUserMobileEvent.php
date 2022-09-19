<?php
/**
 * 存管系统-修改手机号Event
 *
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\supervision\SupervisionAccountService;

class SupervisionUpdateUserMobileEvent extends GlobalTransactionEvent {
    /**
     * 账户ID
     * @var int
     */
    private $accountId;
    /**
     * 用户手机号
     * @var int
     */
    private $mobile;

    public function __construct($accountId, $mobile) {
        $this->accountId = intval($accountId);
        $this->mobile = addslashes($mobile);
    }

    /**
     * 存管系统-修改手机号
     */
    public function commit() {
        $supervisionAccountService = new SupervisionAccountService();
        $supervisionAccountService->memberPhoneUpdate($this->accountId, $this->mobile);
        return true;
    }
}