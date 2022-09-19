<?php
namespace NCFGroup\Common\Library\Lock;

/**
 * LockFactory 悲观锁工厂
 *
 * @author jingxu<jingxu@ucfgroup.com>
 * @package libs\lock
 */
class LockFactory
{
    const TYPE_REDIS = 'RedisLock';

    private static $validTypes = array(
        self::TYPE_REDIS,
        );

    /**
     * create 创建锁
     *
     * @param  mixed    $type  锁的类型
     * @param  mixed    $cache 锁所用到的数据库, 比如reids, memcached
     * @static
     * @access public
     * @return LockAble 锁
     */
    public static function create($type, $cache)
    {
        if (!in_array($type, self::$validTypes)) {
            throw new LockException('lock type is invalid');
        }

        static $ins = array();

        $key = md5($type.serialize($cache));
        if (isset($ins[$key])) {
            return $ins[$key];
        }

        if ($type == self::TYPE_REDIS) {
            $ins[$key] = new RedisLock($cache);
        }

        return $ins[$key];
    }

    public static function createByRedis(\Redis $redis)
    {
        return self::create(self::TYPE_REDIS, $redis);
    }
}
