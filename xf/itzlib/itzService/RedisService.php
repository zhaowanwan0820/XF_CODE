<?php

/**
 * 使用 PhpRedis 扩展连接的 Redis Client
 *
 * 相关命令请参考： http://redis.io/commands   http://redisdoc.com/index.html
 *
 * 目前已知的 PhpRedis 与官方 cli 命令不同有以下：
 * - set()， 扩展方法需要第三参数，expireTime；
 * - lRem(), 扩展方法与 cli 命令的第 2 ，3 参数顺序相反；
 *
 * @file RedisService.php
 * @author (dinglingjie@xxx.com)
 * @modifier (liuhaiyang@xxx.com)
 *
 */
class RedisService
{
    protected static $instance = null;

    protected $rcache = null;

    public function __construct($rcache = null)
    {
        $this->rcache = $rcache;
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }

    /**
     * @param CRedisCache $rcache
     * @return RedisService
     */
    public static function getInstance($rcache = null)
    {
        if (null === static::$instance) {
            static::$instance = new static($rcache);
        }

        return static::$instance;
    }

    /**
     * 设置指定 forceKey (固定一个 Key 值,选择固定的 Redis Server, 用于开启事务等)
     * 当不需要时可设置为 false 。
     * @param string|false $key 当为 string 时用于固定 key 值选 Server , false 时取消该功能
     * @return bool
     */
    public function setForceKey($key)
    {
        return $this->getHashCache()->setForceKey($key);
    }

    /**
     * 指定使用的 rcache 实例
     * @param $rcache
     */
    public function setRcache($rcache)
    {
        $this->rcache = $rcache;
    }

    /**
     * @return CRedisCache
     * @throws Exception
     */
    private function getHashCache()
    {
        return $this->rcache ? $this->rcache : Yii::app()->rcache;
    }

    /**
     * 设置 key 过期时间
     * @param $key
     * @param $time
     * @return int
     */
    public function setExpireTime($key, $time)
    {
        return $this->expire($key, $time);
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->getHashCache(), $method], $args);
    }


    public function set($key, $value, $expire = 86400)
    {
        return $this->getHashCache()->set($key, $value, $expire);
    }

    /**
     * 旧方法默认参数兼容 (默认加 1)
     * @param string $key
     * @param string $field
     * @param int $increment
     * @return int
     */
    public function hIncrBy($key, $field, $increment = 1)
    {
        return $this->getHashCache()->hIncrBy($key, $field, $increment);
    }

    /**
     * 根据参数 count 的值，移除列表中与参数 value 相等的元素。
     * count 的值可以是以下几种：
     *          count > 0 : 从表头开始向表尾搜索，移除与 value 相等的元素，数量为 count 。
     *          count < 0 : 从表尾开始向表头搜索，移除与 value 相等的元素，数量为 count 的绝对值。
     *          count = 0 : 移除表中所有与 value 相等的值。
     * @param $key
     * @param $count
     * @param $value
     * @return int 被移除元素的数量。
     *              因为不存在的 key 被视作空表(empty list)，所以当 key 不存在时， LREM 命令总是返回 0 。
     * @throws Exception
     */
    public function lRem($key, $count, $value)
    {
        return $this->getHashCache()->lRem($key, $value, $count);
    }
}
