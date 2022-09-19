<?php

/**
 * CRedisCache class file
 *
 * @author Gustavo Salom� <gustavonips@gmail.com>
 * @modifier liuhaiyang@xxx.com
 * @license http://www.opensource.org/licenses/gpl-3.0.html
 */
class CRedisCache extends CCache
{
    /**
     * @var CRedisCache 当前操作使用的 redis 连接实例
     */
    protected $_cache = null;
    /**
     * @var array redis 连接集群
     */
    protected $_cluster = [];
    /**
     * @var array redis 集群配置
     */
    public $servers = [];
    /**
     * @var bool 强制使用某个 Mod 值 (某台 Server)
     */
    public $forceKey = false;
    /**
     * @var int 重连次数
     */
    public $retry = 2;

    public function init()
    {
        // 反转 server 配置，兼容旧 mod 算法顺序不同的问题。
        $this->servers = array_reverse($this->servers);
    }

    /**
     * @param int $index
     * @return CRedisCache
     */
    public function getRedis($index)
    {
        return $this->_cluster[$index];
    }

    /**
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key a unique key identifying the cached value
     * @return string the value stored in cache, false if the value is not in the cache or expired.
     */
    protected function getValue($key)
    {
        return $this->_cache ? $this->_cache->get($key) : false;
    }

    /**
     * Retrieves multiple values from cache with the specified keys.
     * @param array $keys a list of keys identifying the cached values
     * @return array a list of cached values indexed by the keys
     * @since 1.0.8
     */
    protected function getValues($keys)
    {
        return $this->_cache ? $this->_cache->mget($keys) : false;
    }

    /**
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means
     *     never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key, $value, $expire = 86400)
    {
        return $this->_cache ? $this->_cache->setex($key, $expire, $value) : false;
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means
     *     never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function addValue($key, $value, $expire)
    {
        return $this->exists($key) ? false : $this->setValue($key, $value, $expire);
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key of the value to be deleted
     * @return boolean if no error happens during deletion
     */
    protected function deleteValue($key)
    {
        return $this->_cache ? $this->_cache->del($key) : 0;
    }

    /**
     * Deletes all values from cache.
     * This is the implementation of the method declared in the parent class.
     * @return boolean whether the flush operation was successful.
     * @since 1.1.5
     */
    protected function flushValues()
    {
        $this->flush();
    }

    /**
     * call unusual method
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $key = isset($args[0]) ? $args[0] : '';
        $result = $this->setCacheServer($key);
        return $result ? call_user_func_array([$this->_cache, $method], $args) : false;
    }

    protected function generateUniqueKey($key)
    {
        $key = parent::generateUniqueKey($key);
        $result = $this->setCacheServer($key);
        return $result ? $key : false;
    }

    public function setForceKey($key)
    {
        $this->forceKey = $key;
        return true;
    }

    public function flush()
    {
        foreach ($this->_cluster as $redis) {
            if ($redis instanceof Redis) {
                $redis->flushAll();
            }
        }
    }

    /**
     * 根据参数 $index 参数使用指定的 redis server
     * @param $key
     * @return bool
     * @throws Exception
     * @internal param int $index
     */
    private function setCacheServer($key)
    {
        /* 强制指定 Key 参数 (固定为某个 Server , 用于开启会话等功能。)*/
        $key = $this->forceKey !== false ? $this->forceKey : $key;
        /* 根据 $key 计算求模选择 Redis server */
        $mod = $this->calMod($key);
        try {
            if ($mod === false) {
                Yii::log('无可用 Redis 配置', CLogger::LEVEL_ERROR, __CLASS__);
                throw new RedisException('There is no server configuration could be used!');
            }
            /*  如果指定的 server 未被实例化使用过则从 $this->servers 配置中获取并实例  */
            if (!isset($this->_cluster[$mod])) {
                $server = $this->servers[$mod];
                $cache = new Redis();

                $rs = false;
                $retry = isset($server['retry']) ? $server['retry'] : $this->retry;
                while ($retry--) {
                    $rs = $cache->connect($server['host'], $server['port'], $server['timeout']);
                    if ($rs || $retry == 0) {
                        break;
                    } else {
                        Yii::log("Connect failure. retrying ... remain {$retry} times.", CLogger::LEVEL_ERROR, __CLASS__);
                        usleep(100 * 1000);
                    }
                }
                if (!$rs) {
                    $serverWithoutPass = $server;
                    unset($serverWithoutPass['password']);
                    throw new InvalidArgumentException('所有重试后失败。 ' . print_r($serverWithoutPass, true));
                } else {
                    if (isset($server['password'])) {
                        if (!$cache->auth($server['password'])) {
                            throw new InvalidArgumentException('Failed to Auth connection');
                        }
                    }
                    if (isset($server['database'])) {
                        $cache->select($server['database']);
                    }
                }

                $this->_cluster[$mod] = $cache;
            }
        } catch (Exception $e) {
            Yii::log("Redis Connect failure: {$e->getMessage()}", CLogger::LEVEL_ERROR, __CLASS__);
            return false;
        }

        $this->_cache = $this->_cluster[$mod];
        return true;
    }

    public function get($id)
    {
        $value = $this->getValue($this->generateUniqueKey($id));
        if ($value === false || $this->serializer === false)
            return $value;
        if ($this->serializer === null) {
            $originValue = $value;
            $value = unserialize($value);
            if ($value === false) {
                return $originValue;
            }
        } else {
            $value = call_user_func($this->serializer[1], $value);
        }
        if (is_array($value) && (!$value[1] instanceof ICacheDependency || !$value[1]->getHasChanged())) {
            Yii::trace('Serving "' . $id . '" from cache', 'system.caching.' . get_class($this));
            return $value[0];
        } else
            return false;
    }

    /**
     * hash string convert to int
     * @param $str
     * @return int
     */
    protected function hashString2int($str)
    {
        $hash = 0;
        $n = strlen($str);
        if ($n > 6) {
            $str = substr($str, 0, 3) . substr($str, -3);
            $n = 6;
        }
        for ($i = 0; $i < $n; $i++) {
            $hash += ($hash << 5) + ord($str[$i]);
        }
        return $hash % 701819;
    }

    /**
     * 根据 $key 值计算求模，用于选择 redis server
     * 当 servers 配置为空时，直接返回 false。
     * @param $key
     * @return bool|int
     */
    private function calMod($key)
    {
        $count = count($this->servers);
        if ($count === 0) {
            return false;
        } else {
            return $this->hashString2int($key) % $count;
        }
    }
}
