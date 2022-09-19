<?php
/**
 *-------------------------------------------------------
 * O2O异步重试
 *-------------------------------------------------------
 * 2015-07-22 18:23:55
 *-------------------------------------------------------
 */

namespace core\event;

use libs\utils\PaymentApi;
use core\event\BaseEvent;
use core\service\O2OService;
use core\exception\O2OException;

/**
 * O2ORetryEvent
 * O2O重试任务
 *
 * @uses AsyncEvent
 * @package default
 */
class O2ORetryEvent extends BaseEvent {
    /**
     * 方法名
     */
    public $functionName;

    /**
     * 请求参数
     */
    public $params;

    public function __construct($functionName, $params) {
        $this->functionName = $functionName;
        $this->params = $params;
    }

    /**
     * 请求支付接口
     */
    public function execute() {
        $functionName = $this->functionName;
        $o2oService = new O2OService();
        try {
            $res = call_user_func_array(array($o2oService, $this->functionName), $this->params);
            if ($res === false) {
                throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
            }
        } catch(\Exception $e) {
            PaymentApi::log('O2ORetryEvent failed, msg: '.$e->getMessage()
                . ', funcName: ' . $this->functionName
                . ', params: ' . json_encode($this->params, JSON_UNESCAPED_UNICODE));

            // 对于超时的业务，继续重试处理
            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                throw $e;
            }
        }

        return true;
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'luzhengshuai@ucfgroup.com');
    }
}
