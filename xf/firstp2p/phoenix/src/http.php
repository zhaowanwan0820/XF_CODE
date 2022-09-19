<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-12 9:27:24
 * @encode UTF-8编码
 */
class P_Http {

    private static $_files = false;
    private static $_get = false;
    private static $_post = false;
    private static $_request = false;

    public static function argv($key = false, $filter_function = 'trim') {
        global $argv;
        if (false !== $key && isset($argv[$key])) {
            return self::var_filter($argv[$key], $filter_function);
        }
        if (false === $key) {
            return self::var_filter($argv, $filter_function);
        }
        return false;
    }
    
    public static function file($key = false) {
        if (false !== $key && isset(self::$_files[$key])) {
            return self::$_files[$key];
        }
        if (false === $key) {
            return self::$_files;
        }
        return false;
    }

    public static function get($key = false, $filter_function = 'trim') {
        if (false !== $key && isset(self::$_get[$key])) {
            return self::var_filter(self::$_get[$key], $filter_function);
        }
        if (false === $key) {
            return self::var_filter(self::$_get, $filter_function);
        }
        return false;
    }

    public static function header_html() {
        header("Content-type: text/html");
    }

    public static function header_json() {
        header("Content-type: application/json");
    }

    public static function json_decode($json, $assoc = true) {
        return json_decode($json, $assoc);
    }

    public static function json_encode($value) {
        return json_encode($value);
    }

    public static function post($key = false, $filter_function = 'trim') {
        if (false !== $key && isset(self::$_post[$key])) {
            return self::var_filter(self::$_post[$key], $filter_function);
        }
        if (false === $key) {
            return self::var_filter(self::$_post, $filter_function);
        }
        return false;
    }

    public static function request($key = false, $filter_function = 'trim') {
        if (false !== $key && isset(self::$_request[$key])) {
            return self::var_filter(self::$_request[$key], $filter_function);
        }
        if (false === $key) {
            return self::var_filter(self::$_request, $filter_function);
        }
        return false;
    }

    public static function reset($filter_function = 'trim') {
        self::$_files = $_FILES;
        self::$_get = self::var_filter($_GET, $filter_function);
        $_GET = array();
        self::$_post = self::var_filter($_POST, $filter_function);
        $_POST = array();
        self::$_request = self::var_filter($_REQUEST, $filter_function);
        $_REQUEST = array();
    }

    public static function server($key = false, $filter_function = 'trim') {
        if (false !== $key && isset($_SERVER[$key])) {
            return self::var_filter($_SERVER[$key], $filter_function);
        }
        if (false === $key) {
            return self::var_filter($_SERVER, $filter_function);
        }
        return false;
    }

    public static function set_get($key, $value) {
        if (!isset(self::$_get[$key]) && strlen(trim($key))) {
            self::$_get[$key] = $value;
            self::$_request[$key] = $value;
            return true;
        }
        return false;
    }

    public static function var_filter($value, $filter_function = 'trim') {
        if (!is_callable($filter_function)) {
            new P_Exception_Http(P_Conf_Globalerrno::$message[P_Conf_Globalerrno::INVALID_VAR_FILTER], P_Conf_Globalerrno::INVALID_VAR_FILTER);
            return false;
        }
        if (!is_array($value)) {
            return $filter_function($value);
        }
        foreach ($value as $k => $v) {
            if (!is_array($v)) {
                $value[$k] = call_user_func($filter_function, $v);
                continue;
            }
            $value[$k] = self::var_filter($v, $filter_function);
        }
        return $value;
    }

}
