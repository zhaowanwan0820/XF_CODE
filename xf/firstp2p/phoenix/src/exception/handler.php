<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-24 13:31:00
 * @encode UTF-8编码
 */
class P_Exception_Handler {

    private static $_last_errno = false;
    private static $_last_error = false;
    private static $_trace = array();

    public static function get_exception() {
        return self::$_trace;
    }

    public static function get_last_errno() {
        return self::$_last_errno;
    }

    public static function get_last_error() {
        return self::$_last_error;
    }

    public static function set_exception($exception) {
        $trace = $exception->getTrace();
        $item = array_shift($trace);
        $item[P_Conf_Exception::INDEX_CODE] = self::$_last_errno = $exception->getCode();
        $item[P_Conf_Exception::INDEX_LEVEL] = $exception->get_level();
        $item[P_Conf_Exception::INDEX_MESSAGE] = self::$_last_error = $exception->getMessage();
        array_push(self::$_trace, $item);
    }

}

set_exception_handler("P_Exception_Handler::set_exception");
