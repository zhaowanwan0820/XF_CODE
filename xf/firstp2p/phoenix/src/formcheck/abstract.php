<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 18:26:10
 * @encode UTF-8编码
 */
abstract class P_Formcheck_Abstract {

    public function get_value($method, $key, $values) {
        switch ($method) {
            case P_Conf_Formcheck::METHOD_CUSTOM:
                return isset($values[$key]) ? P_Http::var_filter($values[$key]) : false;
                break;
            case P_Conf_Formcheck::METHOD_FILE:
                return P_Http::file($key);
                break;
            case P_Conf_Formcheck::METHOD_GET:
                return P_Http::get($key);
                break;
            case P_Conf_Formcheck::METHOD_POST:
                return P_Http::post($key);
                break;
            default:
                new P_Exception_Formcheck(P_Conf_Globalerrno::$message[P_Conf_Globalerrno::FORM_CHECK_ERROR], P_Conf_Globalerrno::FORM_CHECK_ERROR);
                return false;
                break;
        }
    }

    abstract public function valid($method, $key, $args, $values, $optional, $default);
}
