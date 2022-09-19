<?php
/**
 * 网信理财-修改手机号Event
 *
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use libs\utils\Logger;
use core\service\user\UserService;

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
        UserService::updateWxUserInfo($this->data);
        if (UserService::hasError()) {
            $errMsg = UserService::getErrorMsg();
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('userData:%s, errorMsg:%s', json_encode($this->data), $errMsg))));
            throw new \Exception($errMsg);
        }
        return true;
    }
}