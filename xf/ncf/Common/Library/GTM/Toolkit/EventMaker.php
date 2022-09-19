<?php

namespace NCFGroup\Common\Library\GTM\Toolkit;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;

/**
 * Event生成器
 * 可以使开发者不必实现自己的Event类，只需要传入现有的方法即可生成一个Event
 */
class EventMaker extends GlobalTransactionEvent
{

    /**
     * 函数列表
     */
    public $functions = array();

    public function __construct(array $functions)
    {
        $this->functions = $functions;
    }

    /**
     * 执行
     */
    public function execute()
    {
        return $this->call('execute');
    }

    /**
     * 提交
     */
    public function commit()
    {
        return $this->call('commit');
    }

    /**
     * 回滚
     */
    public function rollback()
    {
        return $this->call('rollback');
    }

    /**
     * 方法调用
     * @param string $function 函数名
     */
    private function call($function)
    {
        if (!isset($this->functions[$function])) {
            throw new \Exception('Event has no {$function}');
        }

        $object = isset($this->functions[$function][0]) ? $this->functions[$function][0] : null;
        if (empty($object)) {
            throw new \Exception("object is empty");
        }

        $method = isset($this->functions[$function][1]) ? $this->functions[$function][1] : null;
        if (empty($method)) {
            throw new \Exception("method is empty");
        }

        $params = isset($this->functions[$function][2]) ? $this->functions[$function][2] : array();
        if (!is_array($params)) {
            throw new \Exception("params of [{$method}] is not array");
        }

        return call_user_func_array(array($object, $method), $params);
    }

    /**
     * 方法存在判断重写
     * @param string $method 方法名
     */
    public function methodExists($method)
    {
        return isset($this->functions[$method]);
    }

}
