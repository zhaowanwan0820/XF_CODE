<?php
/**
 * 超级账户-修改手机号Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

class UcfpayUpdateUserMobileEvent extends GlobalTransactionEvent {
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
    /**
     * 用户手机号编码
     * @var string
     */
    private $mobileCode;

    public function __construct($userId, $mobile, $mobileCode='') {
        $this->userId = intval($userId);
        $this->mobile = addslashes($mobile);
        $this->mobileCode = addslashes($mobileCode);
    }

    /**
     * 超级账户-用户修改银行卡
     */
    public function execute() {
        $paymentService = new \core\service\PaymentService();
        $result = $paymentService->updateMobile($this->userId, $this->mobile, $this->mobileCode);
        if (false === $result['ret']) {
            $this->setError($result['msg']);
            return false;
        }
        return true;
    }
}