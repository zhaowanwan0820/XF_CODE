<?php
/**
 * 网信理财-用户实名信息修改Event
 *
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use \core\service\payment\PaymentService;

class WxUpdateUserIdentityByLogEvent extends GlobalTransactionEvent {
    /**
     * 参数列表-新
     * @var array
     */
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * 网信理财-用户信息修改
     */
    public function execute() {
        return PaymentService::updateUserIdentityByLog($this->data);
    }
}