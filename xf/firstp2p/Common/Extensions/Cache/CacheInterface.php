<?php
namespace NCFGroup\Common\Extensions\Cache;

interface CacheInterface
{
    public function set($key, $val);

    public function get($key);

    public function flush();

    public function setPrefix($prefix);

    public function getPrefix();
}
