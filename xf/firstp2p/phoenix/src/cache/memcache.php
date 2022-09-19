<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-23 14:25:30
 * @encode UTF-8编码
 */
class P_Cache_Memcache extends P_Cache_Abstract {

    public function close() {
        (false !== $this->_handle) ? $this->_handle->close() : null;
    }

    public function delete($key) {
        $key = trim(strval($key));
        return (false !== $this->_handle && !empty($key)) ? $this->_handle->delete($key) : false;
    }

    protected function connect($args) {
        if (!class_exists(P_Conf_Cache::ENGINE_MEMCACHE) || !is_array($args) || !isset($args[P_Conf_Cache::CACHE_SERVERS]) || !is_array($args[P_Conf_Cache::CACHE_SERVERS])) {
            new P_Exception_Cache('invalid arguments', P_Conf_Globalerrno::INVALID_CACHE_ENGINE);
            return false;
        }
        $this->_handle = new Memcache();
        $success = 0;
        foreach ($args[P_Conf_Cache::CACHE_SERVERS] as $arg) {
            if (isset($arg[P_Conf_Cache::CACHE_HOST], $arg[P_Conf_Cache::CACHE_PORT])) {
                if ($this->_handle->addServer($arg[P_Conf_Cache::CACHE_HOST], $arg[P_Conf_Cache::CACHE_PORT])) {
                    $success++;
                }
            }
        }
        if (!$success) {
            new P_Exception_Cache('fail to create handler', P_Conf_Globalerrno::INVALID_CACHE_ENGINE);
        }
        return (bool) $success;
    }

    public function get($key) {
        if (is_string($key)) {
            $key = trim(strval($key));
        }
        return (false !== $this->_handle && !empty($key)) ? $this->_handle->get($key, MEMCACHE_COMPRESSED) : false;
    }

    public function get_multi($keys) {
        if (!is_array($keys)) {
            $keys = array(trim(strval($keys)));
        }
        foreach ($keys as $k => $v) {
            $tmp = trim(strval($v));
            if (empty($tmp)) {
                continue;
            }
            $keys[$k] = $tmp;
        }
        if (empty($keys)) {
            return false;
        }
        return $this->get($keys);
    }

    public function set($key, $value, $expire) {
        $key = trim(strval($key));
        return (false !== $this->_handle && !empty($key)) ? $this->_handle->set($key, $value, MEMCACHE_COMPRESSED, $expire) : false;
    }

}
