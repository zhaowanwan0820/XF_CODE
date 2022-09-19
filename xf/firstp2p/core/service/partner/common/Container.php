<?php
/**
 *@author longbo
 */
namespace core\service\partner\common;

use Closure;

class Container
{
    private static $service;

    public static function register($name, Closure $callback)
    {
        self::$service[$name] = $callback;
    }

    public static function book($name)
    {
        if (self::$service[$name] instanceof Closure) {
            $callback = self::$service[$name];
            return $callback();
        } else {
            throw new \Exception($name.' service is not register!');
        }
    }

}

