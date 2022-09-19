<?php
/**
 * 存管实名信息信息修改Event
 *
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\enum\SupervisionEnum;
use core\service\SupervisionAccountService;

class SupervisionMemberInfoModifyEvent extends GlobalTransactionEvent {
    /**
     * @param 更新信息
     */
    private $params;

    public function __construct($params) {
        $this->params = $params;
    }

    /**
     * 个人及港澳台信息修改
     *
     */
    public function execute() {
        $supervisionAccountObj = new SupervisionAccountService();
        $result = $supervisionAccountObj->memberInfoModify($this->params);
        if (SupervisionEnum::RESPONSE_SUCCESS !== $result['status']) {
            $this->setError($result['respMsg']);
            return false;
        }
        return true;
    }
}