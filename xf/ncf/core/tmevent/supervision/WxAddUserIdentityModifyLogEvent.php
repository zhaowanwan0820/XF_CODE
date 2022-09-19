<?php
/**
 * 网信理财-添加实名信息修改日志
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

class WxAddUserIdentityModifyLogEvent extends GlobalTransactionEvent {
    /**
     * 参数列表
     * @var array
     */
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * 网信理财-添加实名信息修改日志
     */
    public function commit() {
        $logModel = new \core\dao\UserIdentityModifyLogModel();
        return $logModel->saveLog($this->data);
    }
}
