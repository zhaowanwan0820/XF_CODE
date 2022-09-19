<?php
/**
 * 网信理财-解绑银行卡Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\UserBankcardService;

class WxCardUnbindEvent extends GlobalTransactionEvent {
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
     * 网信理财-用户解绑卡
     */
    public function commit() {
        if (empty($this->userId) || empty($this->bankcardNo)) {
            return true;
        }
        // 查询用户是否有绑卡记录
        $userBankcardObj = new UserBankcardService();
        $userBankCardData = $userBankcardObj->getBankcard($this->userId);
        if (empty($userBankCardData) || empty($userBankCardData['bankcard'])) {
            \libs\utils\PaymentApi::log('WxCardUnbindEvent, WxBankCardInfo Is Empty, Not Need Unbind, userId:'.$this->userId);
            return true;
        }
        $userService = new \core\service\UserService();
        return $userService->unbindCard($this->userId, $this->bankcardNo);
    }
}