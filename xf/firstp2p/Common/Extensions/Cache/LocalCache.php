<?php
namespace NCFGroup\Common\Extensions\Cache;

class LocalCache implements CacheInterface
{
    private $_instance;

    public function getInstance()
    {
        if (!$this->_instance) {
            $this->_instance = new \Yac();
        }
        return $this->_instance;
    }

    public function set($key, $value)
    {
        $this->getInstance()->set($key, $value);
    }

    public function get($key)
    {
        return $this->getInstance()->get($key);
    }

    public function setPrefix($prefix)
    {
        // do nothing
    }

    public function getPrefix()
    {
        return "";
    }

    public function flush()
    {
        $this->getInstance()->flush();
    }
}

?>
