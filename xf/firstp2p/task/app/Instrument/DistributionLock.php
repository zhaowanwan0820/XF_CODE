<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/3/30
 * Time: 15:55
 */

namespace NCFGroup\Task\Instrument;

class DistributionLock {

    private $_redis;
    static private $_instance = null;
    const LOCK_TIMEOUT_TIME = 3;//锁超时时间，3sec。
    const LOCK_WAITING_INTERVAL = 500; //500us,

    private function __construct() {
        $this->_redis = getDI()->get('taskRedis');
    }

    static public function getInstance() {
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 非阻塞的获取分布式锁，如果没有拿到，则直接返回
     * @param $key
     * @return mixed
     */
    public function getLockOnce($key) {
        $timeVal = microtime(true);
        $timeKey = md5($key);
        //记录锁的时间戳。
        $this->{$timeKey} = $timeVal;
        $luaScript = "
            local result = redis.call('SETNX', KEYS[1], ARGV[1])
            --get lock
            if result == 1 then
                return true
            end
            local time_val = redis.call('GET', KEYS[1])
            -- someone release lock, so return and let it retry
            if time_val == nil then
                return false
            end
             --lock not timeout
            if tonumber(time_val) + tonumber(ARGV[2]) > tonumber(ARGV[1]) then
                return false
            end
            --lock timeout
            local get_set_val = redis.call('GETSET', KEYS[1], ARGV[1])
            -- someone else got the lock
            if get_set_val ~= time_val then
                return false
            end
            return true
        ";
        try {
            $result = $this->_redis->eval($luaScript, [$key, $timeVal, self::LOCK_TIMEOUT_TIME], 1);
            return $result;
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * 阻塞的获取分布式锁，最多等待3s。
     * @param $key
     * @return bool
     */
    public function getLockWait($key, $timeout = self::LOCK_TIMEOUT_TIME) {
        $waitingTime = 0;
        while($waitingTime <= $timeout * 100000) {
            if($this->getLockOnce($key)) {
                getDI()->get('taskLogger')->info("get Lock: {$key}");
                return true;
            }
            $waitingTime += self::LOCK_WAITING_INTERVAL;
            usleep(self::LOCK_WAITING_INTERVAL);
        }
        return false;
    }

    /**
     * 阻塞的获取分布式锁，如果获得锁，则在进程退出的时候自动释放。
     * @param $key
     * @return bool
     */
    public function getAutoReleasedLock($key, $timeout = self::LOCK_TIMEOUT_TIME) {
        $isLocked = $this->getLockWait($key, $timeout);
        if($isLocked) {
            register_shutdown_function(array($this, 'releaseLock'), $key);
        }
        return $isLocked;
    }

    /**
     * 主动释放锁
     * @param $key
     * @return mixed
     */
    public function releaseLock($key) {
        $timeKey = md5($key);
        $timeVal = $this->{$timeKey};
        //在释放锁的时候，判断释放的是否是自己持有的锁
        //判断的依据：根据锁的值
        $luaScript = "
            local time_val = redis.call('GET', KEYS[1])
            if time_val ~= ARGV[1] then
                return false
            end
            return redis.call('DEL', KEYS[1])
        ";
        getDI()->get('taskLogger')->info("release Lock: {$key}");
        try {
            $result = $this->_redis->eval($luaScript, [$key, $timeVal], 1);
            return $result;
        } catch(\Exception $e) {
            return false;
        }
    }
}