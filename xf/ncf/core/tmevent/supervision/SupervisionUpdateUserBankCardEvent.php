<?php
/**
 * 存管系统-修改银行卡Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\SupervisionAccountService;

class SupervisionUpdateUserBankCardEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;
    /**
     * 参数列表
     * @var array
     */
    private $cardInfo;

    /**
     *
     * @param integer  $userId
     * @param array $cardInfo
     *
     */
    public function __construct($userId, $cardInfo = []){
        $this->userId = intval($userId);
        $this->cardInfo = $cardInfo;
    }

    /**
     * 存管系统-修改银行卡
     */
    public function commit() {
        $supervisionAccountObj = new SupervisionAccountService();
        $supervisionAccountObj->memberCardUpdate($this->userId, $this->cardInfo);
        return true;
    }
}
