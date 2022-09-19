<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-23 14:26:15
 * @encode UTF-8编码
 */
abstract class P_Cache_Abstract {

    protected $_handle = false;

    public function __construct($args) {
        return $this->connect($args);
    }

    public function __destruct() {
        $this->close();
    }

    abstract public function close();

    abstract public function delete($key);

    abstract protected function connect($args);

    abstract public function get($key);

    abstract public function get_multi($keys);

    abstract public function set($key, $value, $expire);
}
