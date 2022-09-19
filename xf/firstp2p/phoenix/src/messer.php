<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-23 13:25:05
 * @encode UTF-8编码
 */
class P_Messer {
    
    private static $_class = '';
    private static $_obj = null;
    private static $_value = array();

    public static function C($key, $args = array()) {
        if (isset(self::$_value[$key])) {
            return self::$_value[$key];
        }
        if (empty(self::$_class)) {
            self::$_class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONFIG_PREFIX, ucfirst(strtolower(M::D('APP')))));
        }
        if (!class_exists(self::$_class)) {
            return false;
        }
        if (is_null(self::$_obj)) {
            self::$_obj = new self::$_class;
        }
        self::$_value[$key] = false;
        $obj = new ReflectionClass(self::$_class);
        if ($obj->hasProperty($key)) {
            self::$_value[$key] = $obj->getProperty($key)->getValue(self::$_obj);
        }
        if ($obj->hasMethod($key)) {
            self::$_value[$key] = $obj->getMethod($key)->invokeArgs(self::$_obj, $args);
        }
        if ($obj->hasConstant($key)) {
            self::$_value[$key] = $obj->getConstant($key);
        }
        return self::$_value[$key];
    }

    public static function D($key, $value = null) {
        if (defined($key)) {
            return constant($key);
        }
        if (!is_null($value)) {
            define($key, $value);
            return $value;
        }
        return '';
    }

    public static function I($key, $value) {
        if (ini_get($key)) {
            return ini_set($key, $value);
        }
        return false;
    }

    public static function R($file, $use_require = false) {
        if (!$use_require) {
            $ret = @include_once($file);
        } else {
            $ret = @require_once($file);
        }
        return $ret;
    }

}

class_alias('P_Messer', 'M');
