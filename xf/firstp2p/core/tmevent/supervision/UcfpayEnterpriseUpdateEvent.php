<?php
/**
 * 超级账户-企业用户信息修改Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

class UcfpayEnterpriseUpdateEvent extends GlobalTransactionEvent {
    /**
     * 参数列表
     * @var array
     */
    private $params;
    /**
     * 用户ID
     * @var int
     */
    private $userId;

    public function __construct($params) {
        $this->params = $params;
        $this->userId = (int)$params['userId'];
    }

    /**
     * 超级账户-企业用户信息修改
     */
    public function execute() {
        $paymentService = new \core\service\PaymentService();
        $result = $paymentService->companyUpdate($this->params);
        if (is_array($result)) {
            $this->setError('超级账户：' . $result['respMsg']);
            return false;
        }
        return true;
    }

    public function rollback() {
        return true;
    }
}