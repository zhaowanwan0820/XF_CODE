<?php
/**
 * 存管系统-用户解绑银行卡Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\SupervisionAccountService;
use libs\common\ErrCode;

class SupervisionCardUnbindEvent extends GlobalTransactionEvent {
    /**
     * 用户ID
     * @var int
     */
    private $userId;
    /**
     * 银行卡号
     * @var string
     */
    private $bankcardNo;

    public function __construct($userId, $bankcardNo) {
        $this->userId = intval($userId);
        $this->bankcardNo = trim($bankcardNo);
    }

    /**
     * 存管系统-用户解绑银行卡
     */
    public function commit() {
        if (empty($this->userId) || empty($this->bankcardNo)) {
            return true;
        }
        $service = new SupervisionAccountService();
        $result = $service->unbindCard($this->userId, $this->bankcardNo);
        if (SupervisionAccountService::RESPONSE_SUCCESS === $result['status'] || $result['respCode'] === ErrCode::getCode('ERR_BANKCARD_NOT_EXIST')) {
            return true;
        }
        throw new \Exception('存管账户：' . $result['respMsg']);
    }
}
