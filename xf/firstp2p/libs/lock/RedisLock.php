<?php
namespace libs\lock;

use libs\utils\Logger;

/**
 * RedisLock redis悲观锁
 * 
 * @uses Lockable
 * @author jingxu<jingxu@ucfgroup.com>
 * @package libs\lock
 */
class RedisLock implements Lockable {
    private $redis = null;

    public function __construct($redis = null) {
//        $this->redis = $redis;
        //替换底层的redis，默认使用哨兵机制的redis。
        $this->redis = \SiteApp::init()->dataCache->getRedisInstance();
    }

    /**
     * getLock 获得锁
     * 
     * @param string $key 锁key
     * @param int $timeout_sec  锁的失效时间
     * @access public
     * @return bool
     */
    public function getLock($key, $timeout_sec = self::DEFAULT_TIMEOUT_SEC) {
        if ($timeout_sec <= 0) {
            throw new LockException('timeout_sec参数错误');
        }

        $per_waittime_ms = self::WAITTIME_MS;
        $now_waittime_ms = 0;
        $total_waittime_ms = $timeout_sec * 1000000;

        self::registerShutDownReleaseLock($key);
        try {
            while (!$this->redis->setNX($key, 1) && $now_waittime_ms < $total_waittime_ms) {
                usleep($per_waittime_ms);
                $now_waittime_ms += $per_waittime_ms;
            }
            $this->redis->expire($key, 1);
            if ($now_waittime_ms >= $total_waittime_ms) {
                return false;
            }
        }catch (\Exception $e) {
            return false;
        }

        Logger::wLog("获得锁成功 key:{$key} timeout_sec:{$timeout_sec} date:" . date('y-m-d h:i:s',time()), Logger::INFO, Logger::FILE, self::getLogPath());

        return true;
    }

    /**
     * shutDownReleaseLock 注册php进程调用时释放锁
     * 
     * @param string $lockKey 锁key
     * @access private
     */
    private static function registerShutDownReleaseLock($lockKey) {
        register_shutdown_function(function ($lockKey) {
            RedisLock::releaseLock4ShutDown($lockKey);
        }, $lockKey);
    }

    /**
     * releaseLock4ShutDown 为shutdown, 释放锁
     * 
     * @param string $key 锁key
     * @static
     * @access private
     * @return bool
     */
    private static function releaseLock4ShutDown($key) {
        $successFul = \SiteApp::init()->dataCache->getRedisInstance()->del($key);
        $successFulText = $successFul ? '成功' : '失败';
        Logger::wLog("shutDown释放锁{$successFulText} key:{$key} date:" . date('y-m-d h:i:s',time()), Logger::INFO, Logger::FILE, self::getLogPath());
        return $successFul;
    }

    /**
     * releaseLock 释放锁
     * 
     * @param mixed $key  锁key
     * @access public 
     * @return bool
     */
    public function releaseLock($key) {
        try {
            $successFul = $this->redis->del($key);
        } catch (\Exception $e) {
            \SiteApp::init()->dataCache->connect(); //重连
            $successFul = \SiteApp::init()->dataCache->getRedisInstance()->del($key);
        }

        $successFulText = $successFul ? '成功' : '失败';
        Logger::wLog("正常释放锁{$successFulText} key:{$key} date:" . date('y-m-d h:i:s',time()), Logger::INFO, Logger::FILE, self::getLogPath());
        return $successFul;
    }

    /**
     * getLogPath 获得日志路径
     * 
     * @static
     * @access private
     * @return string 日志路径
     */
    private static function getLogPath() {
        $todayStr = date('y_m_d',time());
        return APP_ROOT_PATH . "log/logger/redislock.log.{$todayStr}";
    }

}
