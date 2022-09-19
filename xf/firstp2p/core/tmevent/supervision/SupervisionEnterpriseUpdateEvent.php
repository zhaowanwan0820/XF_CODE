<?php
/**
 * 存管系统-企业用户信息修改Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\SupervisionAccountService;

class SupervisionEnterpriseUpdateEvent extends GlobalTransactionEvent {
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
     * 存管系统-企业用户信息修改
     */
    public function execute() {
        $supervisionAccountService = new SupervisionAccountService();
        $result = $supervisionAccountService->enterpriseUpdateApi($this->params);
        if (SupervisionAccountService::RESPONSE_SUCCESS !== $result['status']) {
            $this->setError('存管账户：' . $result['respMsg']);
            return false;
        }
        return true;
    }
}