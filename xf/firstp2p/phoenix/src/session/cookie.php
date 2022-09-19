<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-2 9:44:24
 * @encode UTF-8编码
 */
class P_Session_Cookie {

    public static function clear() {
        unset($_COOKIE);
    }

    public static function delete($key) {
        $key = trim(strval($key));
        return self::set($key, '', 0);
    }

    public static function get($key) {
        $key = trim(strval($key));
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        } else {
            return false;
        }
    }

    public static function is_set($key) {
        $key = trim(strval($key));
        return isset($_COOKIE[$key]);
    }

    public static function set($key, $value = '', $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false) {
        $key = trim(strval($key));
        $expire = !empty($expire) ? get_gmtime() + $expire : 0;
        return setcookie($key, $value, $expire, $path, $domain, (bool) $secure, (bool) $httponly);
    }

}
