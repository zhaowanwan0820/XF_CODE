<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-20 10:45:25
 * @encode UTF-8编码
 */
abstract class P_Dao_Abstract {

    private $_base_prefix = '';
    private $_data = array();
    protected $_handle = false;
    protected $_handles = array();
    protected $_prefix = false;
    protected $_prefixes = array();
    private $_type = false;

    public function __construct($type) {
        $config = M::C($type);
        if (!is_array($config)) {
            new P_Exception_Dao("invalid config type={$type}", P_Conf_Globalerrno::INVALID_DAO_CONFIG);
            return false;
        }
        $this->_type = $type;
        $this->_base_prefix = ($type == P_Conf_Dao::DAO_CACHE) ? M::D('APP') . P_Conf_Dao::DAO_VAR_GLUE . M::D('APP_ENV') : P_Conf_Dao::DAO_DEFAULT_BASE_PREFIX;
        foreach ($config as $key => $args) {
            if (!is_array($args)) {
                new P_Exception_Dao("invalid config={$key}", P_Conf_Globalerrno::INVALID_DAO_CONFIG);
                return false;
            }
            list($handle, $prefix) = $this->_get_connect($args);
            $this->_handles[$key] = $handle;
            $this->_prefixes[$key] = trim(strval($prefix));
        }
        $this->_handle = $this->_get_default_handle();
        $this->_prefix = $this->_get_default_prefix();
    }

    public function __call($method, $args) {
        if (isset($this->_handles[$method])) {
            $this->set_handle($this->get_handles($method));
            $this->set_prefix($this->get_prefixes($method));
        }
        return $this;
    }

    protected function add_prefix($var) {
        $ret = array();
        if (!empty($this->_base_prefix)) {
            $ret[] = $this->_base_prefix;
        }
        if (!empty($this->_prefix)) {
            $ret[] = $this->_prefix;
        }
        $ret[] = $var;
        return implode(P_Conf_Dao::DAO_VAR_GLUE, $ret);
    }

    abstract protected function _get_connect($args);

    protected function _get_data() {
        return $this->_data;
    }

    protected function _get_handle() {
        return $this->_handle;
    }

    protected function get_handles($key) {
        if (isset($this->_handles[$key])) {
            return $this->_handles[$key];
        }
        new P_Exception_Dao("invalid handle", P_Conf_Globalerrno::INVALID_DAO_CONFIG);
        return false;
    }

    protected function _get_default_handle() {
        if (isset($this->_handles[P_Conf_Dao::DAO_HANDLE_DEFAULT])) {
            return $this->_handles[P_Conf_Dao::DAO_HANDLE_DEFAULT];
        }
        return reset($this->_handles);
    }

    protected function _get_default_prefix() {
        if (isset($this->_prefixes[P_Conf_Dao::DAO_HANDLE_DEFAULT])) {
            return $this->_prefixes[P_Conf_Dao::DAO_HANDLE_DEFAULT];
        }
        return reset($this->_prefixes);
    }

    protected function get_prefixes($key) {
        if (isset($this->_prefixes[$key])) {
            return $this->_prefixes[$key];
        }
        new P_Exception_Dao("invalid prefix", P_Conf_Globalerrno::INVALID_DAO_CONFIG);
        return false;
    }

    protected function _set_data($data) {
        $this->_data = $data;
        return $data;
    }

    protected function set_handle($handle) {
        if (is_object($handle)) {
            $this->_handle = $handle;
        }
    }

    protected function set_prefix($prefix) {
        if (is_string($prefix)) {
            $this->_prefix = $prefix;
        }
    }

    protected function _set_prefixes($prefixes) {
        if (!is_array($prefixes)) {
            new P_Exception_Dao("invalid prefixes arguments", P_Conf_Globalerrno::INVALID_DAO_CONFIG);
            return false;
        }
        foreach ($prefixes as $key => $prefix) {
            $prefix = trim(strval($prefix));
            if (!strlen($prefix) || !isset($this->_prefixes[$key])) {
                continue;
            }
            $this->_prefixes[$key] = $prefix;
        }
        $this->_handle = $this->_get_default_handle();
        $this->_prefix = $this->_get_default_prefix();
        return true;
    }

}
