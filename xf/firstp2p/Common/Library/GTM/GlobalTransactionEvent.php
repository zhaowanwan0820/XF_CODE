<?php

namespace NCFGroup\Common\Library\GTM;

/**
 * Event基类
 */
abstract class GlobalTransactionEvent
{

    protected $__tid = null;

    public $hasPrepare = null;

    public $hasCommit = null;

    public $hasRollback = null;

    /**
     * 是否异步执行
     */
    public $async = false;

    /**
     * 设置事务Id(事务管理器中会调用)
     * @param int $tid
     */
    public function setTid($tid)
    {
        $this->__tid = $tid;
    }

    /**
     * 获取事务Id
     * @return int
     */
    protected function getTid()
    {
        return $this->__tid;
    }

    private $errorInfo = '';

    /**
     * 设置错误信息
     * @param string $errorInfo
     */
    protected function setError($errorInfo)
    {
        $this->errorInfo = $errorInfo;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorInfo;
    }

    /**
     * 判断方法是否存在
     * @param string $method 方法名
     * @return bool
     */
    public function methodExists($method)
    {
        return method_exists($this, $method);
    }

    /**
     * 随机返回值，用于测试最终一致改
     */
    protected function randomReturn($trueWeight = 1, $falseWeight = 1, $exceptionWeight = 1)
    {
        $sum = $trueWeight + $falseWeight + $exceptionWeight;
        $result = mt_rand(0, $sum * 1000) / 1000;

        if ($result <= $trueWeight) {
            return true;
        } elseif ($result <= $trueWeight + $falseWeight) {
            return false;
        } else {
            throw new \Exception('random exception');
        }
    }

}
