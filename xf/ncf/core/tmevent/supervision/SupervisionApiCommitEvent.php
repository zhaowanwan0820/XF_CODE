<?php
/**
 * 存管系统-调用存管Api的Event
 *
 */
namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use libs\utils\Alarm;
use libs\utils\PaymentApi;
use core\enum\SupervisionEnum;

class SupervisionApiCommitEvent extends GlobalTransactionEvent {
    /**
     * 对象
     * @var string
     */
    private $object;
    /**
     * 方法名
     * @var string
     */
    private $function;
    /**
     * 参数
     * @var array
     */
    private $params;

    public function __construct($object, $function, $params) {
        $this->object = $object;
        $this->function = addslashes(trim($function));
        $this->params = $params;
    }

    public function commit() {
        try{
            if (empty($this->object) || !is_object($this->object) || empty($this->function) || !is_array($this->params)) {
                throw new \Exception('参数不正确');
            }
            if (!method_exists($this->object, $this->function)) {
                throw new \Exception($this->function . '不存在');
            }

            $result = call_user_func_array(array($this->object, $this->function), [$this->params]);
            if ($result['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                throw new \Exception($result['respMsg']);
            }

            PaymentApi::log(sprintf('%s | %s, 调用存管Api接口成功, apiFunction:%s, apiParams:%s, apiResult:%s', __CLASS__, __FUNCTION__, $this->function, json_encode($this->params), json_encode($result)));
            return true;
        } catch(\Exception $e) {
            $errMsg = sprintf('function:%s, params:%s, errMsg:%s', $this->function, json_encode($this->params), $e->getMessage());
            Alarm::push('supervision_event_api', '调用存管Api接口失败', $errMsg);
            throw new \Exception(sprintf('调用存管Api接口失败【%s】', $e->getMessage()));
        }
    }
}