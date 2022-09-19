<?php
namespace NCFGroup\Common\Extensions\Cache;

class NullCache implements CacheInterface
{
    private $_instance;

    private $_prefix = '';

    public function getInstance()
    {
        if (!$this->_instance) {
            $this->_instance = $this;
        }
        return $this->_instance;
    }

    public function keys()
    {
        return array();
    }

    public function set($key, $value)
    {
        return true;
    }

    public function get($key)
    {
        return null;
    }

    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function flush()
    {
        return true;
    }
}
