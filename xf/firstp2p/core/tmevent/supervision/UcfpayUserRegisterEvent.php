<?php
/**
 * 超级账户-用户开户注册Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use \libs\utils\Alarm;
use core\service\PaymentService;

class UcfpayUserRegisterEvent extends GlobalTransactionEvent {
    /**
     * 参数列表
     * @var array
     */
    private $params;

    public function __construct($params) {
        $this->params = $params;
    }

    /**
     * 超级账户-用户开户注册
     */
    public function execute() {
        $result = PaymentApi::instance()->request('register', $this->params);
        if($result['respCode'] == '00' && ($result['status'] == '00' || $result['status'] == PaymentService::REGISTER_USER_EXISTS)){
            return true;
        }

        $errorMsg = "超级账户：用户ID：{$this->params['userId']}，注册失败";
        PaymentService::log($errorMsg, Logger::WARN);
        Alarm::push('payment', 'register', $errorMsg);
        $this->setError('身份验证失败,如需帮助请联系客服');
        return false;
    }
}