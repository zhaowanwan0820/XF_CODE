<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-23 15:30:56
 * @encode UTF-8编码
 */
class P_Dao_Cache extends P_Dao_Abstract {

    public function __construct() {
        parent::__construct(P_Conf_Dao::DAO_CACHE);
    }

    public function get($keys) {
        if (M::D('DEBUG')) {
            return false;
        }
        $flag = false;
        if (is_string($keys)) {
            $flag = true;
        }
        if (!is_array($keys)) {
            $keys = array(trim(strval($keys)));
        }
        foreach ($keys as $k => $v) {
            $tmp = trim(strval($v));
            if (empty($tmp)) {
                continue;
            }
            $keys[$k] = $this->add_prefix($tmp);
        }
        if (empty($keys)) {
            new P_Exception_Cache('empty keys', P_Conf_Globalerrno::INVALID_CACHE_EXECUTION);
            return false;
        }
        if (!($ret = $this->_handle->get_multi($keys))) {
            return false;
        }
        if ($flag && count($ret) == 1) {
            return array_pop($ret);
        }
        return $ret;
    }

    protected function _get_connect($args) {
        $engine = isset($args[P_Conf_Cache::CACHE_ENGINE]) ? $args[P_Conf_Cache::CACHE_ENGINE] : P_Conf_Cache::ENGINE_MEMCACHE;
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::FRAMEWORK_PREFIX, P_Conf_Cache::DEFAULT_INFFIX, ucfirst(strtolower($engine))));
        if (!class_exists($class)) {
            new P_Exception_Cache(P_Conf_Globalerrno::$message[P_Conf_Globalerrno::INVALID_CACHE_ENGINE], P_Conf_Globalerrno::INVALID_CACHE_ENGINE);
            return false;
        }
        $prefix = isset($args[P_Conf_Cache::CACHE_PREFIX]) ? trim(strval($args[P_Conf_Cache::CACHE_PREFIX])) : P_Conf_Cache::DEFAULT_PREFIX;
        return array(new $class($args), $prefix);
    }

    public function set($key, $value, $expire = P_Conf_Cache::DEFAULT_EXPIRE) {
        $key = trim(strval($key));
        if (empty($key)) {
            new P_Exception_Cache('empty keys', P_Conf_Globalerrno::INVALID_CACHE_EXECUTION);
            return false;
        }
        if (!$this->_handle->set($this->add_prefix($key), $value, $expire)) {
            new P_Exception_Cache("fail to set memcache", P_Conf_Globalerrno::INVALID_CACHE_EXECUTION);
            return false;
        }
        return true;
    }

}
