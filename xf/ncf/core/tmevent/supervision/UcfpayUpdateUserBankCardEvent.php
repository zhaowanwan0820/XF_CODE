<?php
/**
 * 超级账户-用户修改银行卡Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

class UcfpayUpdateUserBankCardEvent extends GlobalTransactionEvent {
    /**
     * 用户绑卡数组
     * @var array
     */
    private $userBankcardInfo;
    /**
     * 用户银行卡数组
     * @var array
     */
    private $bankcardInfo;

    public function __construct($userBankcardInfo, $bankcardInfo) {
        $this->userBankcardInfo = $userBankcardInfo;
        $this->bankcardInfo = $bankcardInfo;
    }

    /**
     * 超级账户-用户修改银行卡
     */
    public function execute() {
        $userBankcardService = new \core\service\UserBankcardService();
        $result = $userBankcardService->ucfpayUpdateUserBankCard($this->userBankcardInfo, $this->bankcardInfo);
        if (false === $result['ret']) {
            $this->setError($result['msg']);
            return false;
        }
        return true;
    }
}
