<?php
/**
 * 存管系统-用户解绑银行卡Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use libs\common\ErrCode;
use core\enum\SupervisionEnum;
use core\service\supervision\SupervisionAccountService;

class SupervisionCardUnbindEvent extends GlobalTransactionEvent {
    /**
     * 账户ID
     * @var int
     */
    private $accountId;
    /**
     * 银行卡号
     * @var string
     */
    private $bankcardNo;

    public function __construct($accountId, $bankcardNo) {
        $this->accountId = intval($accountId);
        $this->bankcardNo = trim($bankcardNo);
    }

    /**
     * 存管系统-用户解绑银行卡
     */
    public function commit() {
        if (empty($this->accountId) || empty($this->bankcardNo)) {
            return true;
        }
        $service = new SupervisionAccountService();
        $result = $service->unbindCard($this->accountId, $this->bankcardNo);
        if (SupervisionEnum::RESPONSE_SUCCESS === $result['status'] || $result['respCode'] === ErrCode::getCode('ERR_BANKCARD_NOT_EXIST')) {
            return true;
        }
        throw new \Exception('存管账户：' . $result['respMsg']);
    }
}