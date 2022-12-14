<?php
namespace NCFGroup\Common\Extensions\Cache;

class RedisCache implements CacheInterface
{
    private $_instance;

    private $_prefix = '';

    const NS_SEPARATOR = ':';

    public function getInstance()
    {
        if (!$this->_instance) {
            $this->_instance = new \Redis();
            $redisConf = getDI()->get("config")->redis;
            $this->_instance->connect($redisConf->host, $redisConf->port);
        }
        return $this->_instance;
    }

    public function set($key, $value)
    {
        $key = $this->_prefix . $key;

        switch(gettype($value)) {
            case "array":
            case "object":
                $newValue = (array) $value;
                $this->getInstance()->hMSet($key, $newValue);
                break;
            case "boolean":
                $newValue = ($value === true ? "true" : "false");
                $this->getInstance()->set($key, $value);
                break;
            case "NULL":
                // do nothing
                break;
            case "string":
            case "double":
            case "integer":
                $this->getInstance()->set($key, $value);
                break;
        }
        return true;
    }

    public function get($key)
    {
        $key = $this->_prefix . $key;
        switch($this->getInstance()->type($key)) {
            case \Redis::REDIS_STRING:
                $val = $this->getInstance()->get($key);
                switch($val) {
                    case 'true':
                        return true;
                        break;
                    case 'false':
                        return false;
                        break;
                    default:
                        return $val;
                }
                break;
            case \Redis::REDIS_HASH:
                $val = $this->getInstance()->hGetAll($key);
                return $val?$val:array();
                break;
            case \Redis::REDIS_NOT_FOUND:
                return null;
                break;
        }
    }

    public function setPrefix($prefix)
    {
        // ???????????????':'??????
        $prefix = rtrim($prefix, self::NS_SEPARATOR);

        // ??????prefix????????????????????????????????????':'
        if(!empty($prefix)) {
            $this->_prefix = $prefix . self::NS_SEPARATOR;
        }
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function flush()
    {
        // ???????????????varz?????????key???????????????
        $keys = $this->getInstance()->keys("varz:*");
        return count($keys) == $this->getInstance()->delete($keys);
    }
}

?>
