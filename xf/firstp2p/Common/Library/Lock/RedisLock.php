<?php
namespace NCFGroup\Common\Library\Lock;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Library\Logger;

/**
 * RedisLock redis悲观锁
 *
 * @uses Lockable
 * @author jingxu<jingxu@ucfgroup.com>
 * @package libs\lock
 */
class RedisLock implements Lockable
{
    private $redis = null;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * getLock 获得锁
     *
     * @param  string $key         锁key
     * @param  int    $timeout_sec 锁的失效时间
     * @access public
     * @return bool
     */
    public function getLock($key, $timeout_sec = self::DEFAULT_TIMEOUT_SEC)
    {
        if ($timeout_sec <= 0) {
            throw new LockException('timeout_sec参数错误');
        }

        self::registerShutDownReleaseLock($key);

        $per_waittime_ms = self::WAITTIME_MS;
        $now_waittime_ms = 0;
        $total_waittime_ms = $timeout_sec * 1000000;
        while (!$this->redis->set($key, 1, array('nx', 'ex' => $timeout_sec)) && $now_waittime_ms < $total_waittime_ms) {
            usleep($per_waittime_ms);
            $now_waittime_ms += $per_waittime_ms;
        }

        if ($now_waittime_ms >= $total_waittime_ms) {
            return false;
        }


        Logger::info("获得锁成功 key:{$key} timeout_sec:{$timeout_sec} date:" . XDateTime::now()->toString());
        return true;
    }

    /**
     * isGetLock 是否获得锁
     *
     * @param string $key
     * @param int    $timeout_sec
     * @return bool
     */
    public function isGetLock($key, $timeout_sec = self::DEFAULT_TIMEOUT_SEC)
    {
        if ($timeout_sec <= 0) {
            throw new LockException('timeout_sec参数错误');
        }

        if ($lock = $this->redis->set($key, 1, array('nx', 'ex' => $timeout_sec))) {
            self::registerShutDownReleaseLock($key);
        }

        return $lock;

    }

    private static function shutDownReleaseLock($lockKey)
    {
        register_shutdown_function(function ($lockKey) {
            $lock = LockFactory::create(LockFactory::TYPE_REDIS, getDI()->getRedis());
            $lock->releaseLock($lockKey);
        }, $lockKey);
    }

    /**
     * shutDownReleaseLock 注册php进程调用时释放锁
     *
     * @param string $lockKey 锁key
     * @access private
     */
    private static function registerShutDownReleaseLock($lockKey)
    {
        register_shutdown_function(function ($lockKey) {
            RedisLock::releaseLock4ShutDown($lockKey);
        }, $lockKey);
    }

    /**
     * releaseLock4ShutDown 为shutdown, 释放锁
     *
     * @param  string $key 锁key
     * @static
     * @access private
     * @return bool
     */
    private static function releaseLock4ShutDown($key)
    {
        $successFul = getDI()->getRedis()->delete($key);
        $successFulText = $successFul ? '成功' : '失败';
        Logger::info("shutDown释放锁{$successFulText} key:{$key} date:" . date('y-m-d h:i:s',time()));
        return $successFul;
    }

    /**
     * releaseLock 释放锁
     *
     * @param  mixed $key 锁key
     * @access public
     * @return bool
     */
    public function releaseLock($key)
    {
        $successFul = $this->redis->delete($key);
        $successFulText = $successFul ? '成功' : '失败';

        Logger::info("正常释放锁{$successFulText} key:{$key} date:" . date('y-m-d h:i:s',time()));
        return $successFul;
    }

    /**
     * getLogPath 获得日志路径
     *
     * @static
     * @access private
     * @return string 日志路径
     */
    private static function getLogPath()
    {
        return APP_ROOT_PATH . "log/logger/redislock.log";
    }

}
