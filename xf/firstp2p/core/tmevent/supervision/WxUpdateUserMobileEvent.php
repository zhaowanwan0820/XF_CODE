<?php
/**
 * 网信理财-修改手机号Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

class WxUpdateUserMobileEvent extends GlobalTransactionEvent {
    /**
     * 参数列表
     * @var array
     */
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * 网信理财-修改手机号
     */
    public function commit() {
        $userService = new \core\service\UserService();
        return $userService->updateInfo($this->data);
    }
}