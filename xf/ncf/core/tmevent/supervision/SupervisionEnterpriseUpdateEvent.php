<?php
/**
 * 存管系统-企业用户信息修改Event
 *
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\enum\SupervisionEnum;
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
    public function commit() {
        $supervisionAccountService = new SupervisionAccountService();
        $result = $supervisionAccountService->enterpriseUpdateApi($this->params);
        if (SupervisionEnum::RESPONSE_SUCCESS !== $result['status']) {
            throw new \Exception('存管账户：' . $result['respMsg']);
        }
        return true;
    }
}