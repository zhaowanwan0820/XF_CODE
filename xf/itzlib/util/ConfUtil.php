<?php

/**
 * 环境配置获取工具类
 * Class ConfUtil
 * @author liuhaiyang
 */

class ConfUtil
{
    public static $hasSet = false;

    public static function get($key, $isArr = false)
    {
        if (!extension_loaded('env') && !self::$hasSet) {
            self::setConfig();
        }

        $value = getenv($key);

        if ($isArr) {
            return json_decode($value, true);
        } else {
            return $value;
        }
    }

    public static function setConfig()
    {
        $ini = dirname(dirname(__DIR__)) . "/env.ini";
        $configs= parse_ini_file($ini);
        foreach ($configs as $k => $v) {
            putenv("{$k}={$v}");
        }

        self::$hasSet = true;
    }

}
