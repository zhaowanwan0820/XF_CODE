<?php

namespace NCFGroup\Common\Library\GTM\Toolkit;

use NCFGroup\Common\Library\CommonLogger;
use NCFGroup\Common\Library\RedisCreator;

/**
 * 异常测试助手
 */
class ExceptionTester
{

    private $gtmName = '';

    /**
     * 默认配置
     */
    private $defaultConfig = array(
        //方法调用次数
        'CALL_TIMES' => 1,
        //调用前异常概率
        'BEFORE_CALL_EXCEPTION' => 0,
        //调用前返回失败概率
        'BEFORE_CALL_FAILED' => 0,
        //调用后异常概率
        'AFTER_CALL_EXCEPTION' => 0,
    );

    /**
     * 全局配置Key
     */
    const REDIS_GLOBAL_KEY = 'H_GTM_TESTER_CONFIG';

    /**
     * 事务配置前缀
     */
    const REDIS_TRANS_PREFIX = 'H_GTM_TESTER_TRANS_';

    /**
     * 参与者配置前缀
     */
    const REDIS_METHOD_PREFIX = 'H_GTM_TESTER_METHOD_';

    /**
     * 参与者配置前缀
     */
    const REDIS_COV_PREFIX = 'H_GTM_TESTER_COV_';

    private static $instance = array();

    /**
     * 根据事务名单例化
     */
    public static function instance($gtmName)
    {
        if (empty(self::$instance[$gtmName])) {
            self::$instance[$gtmName] = new self($gtmName);
        }

        return self::$instance[$gtmName];
    }

    private function __construct($gtmName)
    {
        $this->gtmName = $gtmName;
    }

    /**
     * 测试场景调用入口
     */
    public function call($object, $method)
    {
        if (!$this->isEnable()) {
            return call_user_func(array($object, $method));
        }

        $eventName = get_class($object);

        //Event执行前异常
        $this->throwBeforeException($eventName, $method);

        //执行前直接返回失败
        if ($this->beforeFailed($eventName, $method)) {
            return false;
        }

        //Event重复执行
        $times = $this->getMethodCallTimes($eventName, $method);
        $times = max($times, 1);
        CommonLogger::info("gtm tester called. times:".$times);

        for ($i = 0; $i < $times; $i++) {
            $result = call_user_func(array($object, $method));
        }

        //重试覆盖标记
        if ($times > 1) {
            $this->setCoverage($eventName, $method, 'REPEAT', 1);
        }

        //Event执行完成后异常
        $this->throwAfterException($eventName, $method);

        if ($result === true) {
            $this->setCoverage($eventName, $method, 'SUCCESS', 1);
        }

        return $result;
    }

    /**
     * 执行前失败
     */
    private function beforeFailed($event, $method)
    {
        $result = $this->getConfigValue($event, $method, 'BEFORE_CALL_FAILED');

        if (mt_rand(0, 99) < $result) {
            $this->setCoverage($event, $method, 'FAILED', 1);
            return true;
        }

        return false;
    }

    /**
     * 执行前异常
     */
    private function throwBeforeException($event, $method)
    {
        $result = $this->getConfigValue($event, $method, 'BEFORE_CALL_EXCEPTION');

        if (mt_rand(0, 99) < $result) {
            $this->setCoverage($event, $method, 'EXCEPTION', 1);
            throw new \Exception("ExceptionTester::throwBeforeException happens. event:{$event}, method:{$method}");
        }

        return true;
    }

    /**
     * 执行后异常
     */
    private function throwAfterException($event, $method)
    {
        $result = $this->getConfigValue($event, $method, 'AFTER_CALL_EXCEPTION');

        if (mt_rand(0, 99) < $result) {
            throw new \Exception("ExceptionTester::throwAfterException happens. event:{$event}, method:{$method}");
        }

        return true;
    }

    /**
     * 获取Method应该执行的次数
     */
    private function getMethodCallTimes($event, $method)
    {
        $result = $this->getConfigValue($event, $method, 'CALL_TIMES');

        if (strpos($result, ',') > 0) {
            $result = explode(',', $result);
            $result = mt_rand($result[0], $result[1]);
        }

        return $result;
    }

    /**
     * 获取配置值
     */
    private function getConfigValue($event, $method, $key)
    {
        //取方法配置
        $config = $this->getMethodConfig($event, $method);
        if (isset($config[$key])) {
            return $config[$key];
        }

        //取事务配置
        $config = $this->getTransactionConfig();
        if (isset($config[$key])) {
            return $config[$key];
        }

        //取全局配置
        $config = $this->getGlobalConfig();
        if (!empty($config[$key])) {
            return $config[$key];
        }

        //取默认配置
        return $this->defaultConfig[$key];
    }

    /**
     * method配置
     */
    private $methodConfig = array();

    /**
     * 获取method配置
     */
    public function getMethodConfig($event, $method)
    {
        $eventConfig = $this->getEventConfig($event);

        if (isset($eventConfig[$method])) {
            return $eventConfig[$method];
        }

        return array();
    }

    /**
     * 获取Event全部配置
     */
    public function getEventConfig($event)
    {
        if (isset($this->methodConfig[$event]) === false) {
            $this->methodConfig[$event] = array();

            $result = $this->HGETALL(self::REDIS_METHOD_PREFIX."{$this->gtmName}_{$event}");
            foreach ($result as $index => $value) {
                $keyArray = explode('_', $index, 2);
                $methodName = $keyArray[0];
                $keyName = $keyArray[1];

                $this->methodConfig[$event][$methodName][$keyName] = $value;
            }
        }

        return $this->methodConfig[$event];
    }

    /**
     * 事务配置
     */
    private $transactionConfig = null;

    /**
     * 获取事务配置
     */
    public function getTransactionConfig()
    {
        if ($this->transactionConfig === null) {
            $this->transactionConfig = $this->HGETALL(self::REDIS_TRANS_PREFIX.$this->gtmName);
        }

        return $this->transactionConfig;
    }

    /**
     * 全局配置
     */
    private static $globalConfig = null;

    /**
     * 获取全局配置
     */
    public function getGlobalConfig()
    {
        if (self::$globalConfig === null) {
            self::$globalConfig = $this->HGETALL(self::REDIS_GLOBAL_KEY);
        }

        return self::$globalConfig;
    }

    /**
     * 测试模式是否开启
     */
    public function isEnable()
    {
        $config = $this->getGlobalConfig();

        return empty($config['ENABLE']) ? false : true;
    }

    /**
     * 设置全局配置
     */
    public function setGlobalConfig($key, $value)
    {
        return $this->getRedis()->HSET(self::REDIS_GLOBAL_KEY, $key, $value);
    }

    /**
     * 设置事务配置
     */
    public function setTransactionConfig($key, $value)
    {
        return $this->getRedis()->HSET(self::REDIS_TRANS_PREFIX.$this->gtmName, $key, $value);
    }

    /**
     * 设置方法配置
     */
    public function setMethodConfig($event, $method, $key, $value)
    {
        return $this->getRedis()->HSET(self::REDIS_METHOD_PREFIX."{$this->gtmName}_{$event}", $method.'_'.$key, $value);
    }

    /**
     * 删除方法配置
     */
    public function deleteMethodConfig($event)
    {
        return $this->getRedis()->DEL(self::REDIS_METHOD_PREFIX."{$this->gtmName}_{$event}");
    }

    /**
     * 设置覆盖数据
     */
    public function setCoverage($event, $method, $key, $value)
    {
        return $this->getRedis()->HSET(self::REDIS_COV_PREFIX."{$this->gtmName}_{$event}", $method.'_'.$key, $value);
    }

    /**
     * 获取覆盖数据
     */
    public function getCoverage($event)
    {
        $data = array();

        $result = $this->HGETALL(self::REDIS_COV_PREFIX."{$this->gtmName}_{$event}");
        foreach ($result as $index => $value) {
            $keyArray = explode('_', $index, 2);
            $methodName = $keyArray[0];
            $keyName = $keyArray[1];

            $data[$methodName][$keyName] = $value;
        }

        return $data;
    }

    /**
     * 清除覆盖数据
     */
    public function deleteCoverage($event)
    {
        return $this->getRedis()->DEL(self::REDIS_COV_PREFIX."{$this->gtmName}_{$event}");
    }

    /**
     * 获取Hash全部，如果为空返回array()
     */
    private function HGETALL($key)
    {
        $value = $this->getRedis()->HGETALL($key);

        if (empty($value)) {
            return array();
        }

        return $value;
    }

    private $redis = null;

    /**
     * 获取Redis实例
     */
    private function getRedis()
    {
        if ($this->redis === null) {
            $config = getDi()->getConfig()->redis_gtm;
            $this->redis = RedisCreator::getRedis($config->sentinels);
            if (isset($config->password)) {
                $this->redis->auth($config->password);
            }
        }

        return $this->redis;
    }

}
