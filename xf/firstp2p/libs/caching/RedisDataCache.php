<?php

/**
 * author yutao
 * RedisDataCache class file
 */

namespace libs\caching;

use libs\caching\DataCache;
use libs\caching\NcfRedis;
use libs\utils\FileCache;
use libs\utils\Logger;

class RedisDataCache extends DataCache {

    //private static $_instance;
    private $_redis = null;
    public $hostname = 'localhost';
    public $port = 6379;
    public $database = 0;
    public $sentinels = array();
    public $masterName = '';
    const NON_DATACACHE = 'NON_DATACACHE';

    public function connect() {
        try {
             if (class_exists("\libs\caching\NcfRedis")) {
                 $ncfRedis = new NcfRedis();
                 // 非集群Redis
                 // $cacheConf = $GLOBALS['components_config']['components']['cache'];
                 // $client = $ncfRedis->getSingleConnection($cacheConf['hostname'], $cacheConf['port']);
                 // $client->select($cacheConf['database']);

                 $ncfRedis->setSentinels($this->sentinels);
                 $clusters = array();
                 $this->_redis = $ncfRedis->getClusterConnection($this->masterName, $clusters, $this->database, true);

                 /*
                 if(!$client->exists("system:config:redis-clusters")) {
                     $this->_redis = $ncfRedis->getClusterConnection($this->masterName, $clusters, $this->database, true);
                     $client->set("system:config:redis-clusters", json_encode($clusters));
                 } else {
                     $clusterString = trim($client->get("system:config:redis-clusters"));
                     $clusters = json_decode($clusterString, true);
                     $this->_redis = $ncfRedis->getClusterConnection($this->masterName, $clusters, $this->database, false);
                     if(json_encode($clusters) != $clusterString) {
                         $client->set("system:config:redis-clusters", json_encode($clusters));
                     }
                 }
                 */
             } else {
                 throw new \Exception("NcfRedis do not exit ");
             }
        } catch (\Exception $exc) {
            $this->_redis = NULL;
            $message = array(
                "ip" => get_client_ip(),
                'flag' => 'redis exception',
                "errorCode" => $exc->getCode(),
                "errorMsg" => $exc->getMessage(),
            );
            \libs\utils\Logger::error(implode(" | ", $message));
        }
    }

    const CALL_SETNX_EXPIRE_TIME = 3;

    /**
     * @param type $moduleInstance 查询对象实例
     * @param type $function       方法名
     * @param type $param          参数
     * @param type $expire    缓存时间：秒
     * @param boolean $forceRefresh 强制刷新缓存
     * @param boolean $writeLock 写锁(防止缓存穿透)
     * @return mix
     */
    public function call($moduleInstance, $function, $param, $expire = 10, $forceRefresh = false, $writeLock = false)
    {
        $site_id = app_conf('TEMPLATE_ID');

        if ($moduleInstance instanceof \libs\rpc\Rpc) {
            $cacheKey = sprintf("DATACACHE_%s_%s_%s_%s_%s", $site_id, get_class($moduleInstance), $function, $param[0], md5(serialize($param)));
        } else {
            $cacheKey = sprintf("DATACACHE_%s_%s_%s_%s", $site_id, get_class($moduleInstance), $function, md5(serialize($param)));
        }

        //如果不强制刷新
        if ($forceRefresh === false) {
            try {
                $cacheData = $this->get($cacheKey);
                if ($cacheData !== FALSE) {
                    return $cacheData;
                }
            } catch (\Exception $e) {
                Logger::error('RedisDataCache_GetFailed. message:'.$e->getMessage());
            }

            //如果redis无法找到，则在文件缓存查找
            $cacheFileData = FileCache::getInstance()->get($cacheKey);
            if ($cacheFileData !== false) {
                Logger::info("RedisDataCache_File get cache from file Key:".$cacheKey);
                return $cacheFileData;
            }
        }

        $lockKey = $cacheKey.'_lock';
        $lockValue = mt_rand(1, 999999);

        //没有获得写锁，从文件缓存强制读出数据
        if ($writeLock === true && !$this->setNx($lockKey, $lockValue, self::CALL_SETNX_EXPIRE_TIME)) {
            return FileCache::getInstance()->get($cacheKey, FileCache::IGNORE_EXPRIE_TIME);
        }

        $result = call_user_func_array(array($moduleInstance, $function), $param);

        //取消缓存false的规则 add by yutao 1217
        if (empty($result)) {
            return $result;
        }
        Logger::info("RedisDataCache_DB get data from db key:{$cacheKey}");
        try {
            $this->set($cacheKey, $result, $expire);
            //双缓存，文件缓存和分布式缓存同时使用
            FileCache::getInstance()->set($cacheKey, $result, $expire);
        } catch (\Exception $e) {
            \libs\utils\Logger::error('RedisDataCache_SetFailed. message:'.$e->getMessage());
        }

        //清除写锁 (对比value是当逻辑处理时间大于setNx过期时间时，防止被之前刚处理完的进程删除正在处理进程的锁)
        if ($writeLock === true && $this->get($lockKey) == $lockValue) {
            $this->remove($lockKey);
        }

        return $result;
    }

    /**
     * 返回redis对象
     */
    public function getRedisInstance() {
        if (!isset($this->_redis)) {
            $this->connect();
        }
        return $this->_redis;
    }

    /**
     * 写缓存
     *
     * @param string $key 组存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     * @return bool
     */
    private function set($key, $value, $expire = 0) {
        if (!$this->getRedisInstance()) {
            return FALSE;
        }
        $value = serialize($value);
        if (strlen($value) < 1) {
            return FALSE;
        }
        if ($expire === 0) {
            $ret = $this->getRedisInstance()->set($key, $value);
        } else {
            $ret = $this->getRedisInstance()->setex($key, intval($expire), $value);
        }
        return $ret;
    }

    /**
     * 读缓存
     *
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string  成功返回数据，失败返回NON_DATACACHE
     */
    private function get($key) {
        if (!$this->getRedisInstance()) {
            return FALSE;
        }
        if (empty($key)) {
            return FALSE;
        }
        $func = is_array($key) ? 'mGet' : 'get';
        $ret = $this->getRedisInstance()->{$func}($key);
        if ("mGet" == $func) {
            $ret = array_map('unserialize', $ret);
            return $ret;
        }
        if ($ret === false || empty($ret)) {
            return FALSE;
        }
        $ret = unserialize($ret);
        return $ret;
    }

    /**
     * 删除缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function remove($key) {
        if (!$this->getRedisInstance()) {
            return FALSE;
        }
        FileCache::getInstance()->delete($key);
        return $this->getRedisInstance()->del($key);
    }

    /**
     * 清除redis缓存
     * @param unknown $moduleInstance
     * @param unknown $function
     * @param unknown $param
     * @param unknown $site_id
     * @return Ambigous <number, boolean>
     */
    public function removeOne($moduleInstance, $function, $param, $site_id){
        if ($moduleInstance instanceof \libs\rpc\Rpc) {
            $cacheKey = sprintf("DATACACHE_%s_%s_%s_%s_%s", $site_id, get_class($moduleInstance), $function, $param[0], md5(serialize($param)));
        } else {
            $cacheKey = sprintf("DATACACHE_%s_%s_%s_%s", $site_id, get_class($moduleInstance), $function, md5(serialize($param)));
        }
        return $this->remove($cacheKey);
    }

    /**
     * 清空数据
     */
    public function flushAll() {
        if (!$this->getRedisInstance()) {
            return FALSE;
        }
        return $this->getRedisInstance()->flushAll();
    }

    /**
     * 值加加操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function incr($key, $defaultInc = 1) {
        if (!$this->getRedisInstance()) {
            return FALSE;
        }
        if ($defaultInc === 1) {
            return $this->getRedisInstance()->incr($key);
        } else {
            return $this->getRedisInstance()->incrBy($key, $defaultInc);
        }
    }

    /**
     * 值减减操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function decr($key, $defaultInc = 1) {
        if (!$this->getRedisInstance()) {
            return FALSE;
        }
        if ($defaultInc === 1) {
            return $this->getRedisInstance()->decr($key);
        } else {
            return $this->getRedisInstance()->decrBy($key, $defaultInc);
        }
    }

    /**
     * key是否存在，存在返回ture
     * @param string $key KEY名称
     */
    public function exists($key) {
        if (!$this->getRedisInstance()) {
            return FALSE;
        }
        return $this->redis->exists($key);
    }

    public function setNx($key,$val,$ttl) {
        $val = serialize($val);
        if (!$this->getRedisInstance()) {
            return FALSE;
        }
        return $this->getRedisInstance()->set($key,$val,'EX',$ttl,'NX');
    }

}
