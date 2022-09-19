<?php

namespace NCFGroup\Common\Library\Registry;

use NCFGroup\Common\Library\CommonLogger;
use NCFGroup\Common\Library\Registry\Etcd;

class Registry
{

    public static $config = array();

    public static function getConfig()
    {
        if (empty(self::$config)) {
            self::$config = getDi()->getConfig()->registry->etcd->toArray();
        }
        return self::$config;
    }

    public static $serviceInfo = array();

    public static function getServiceInfo($serviceNamePrefix)
    {
        if (empty(self::$serviceInfo[$serviceNamePrefix])) {
            $config = self::getConfig();
            $etcd = new Etcd($config['hosts'], $config['username'], $config['password']);
            self::$serviceInfo[$serviceNamePrefix] = $etcd->getServiceInfo($serviceNamePrefix);
        }

        return self::$serviceInfo[$serviceNamePrefix];
    }

}
