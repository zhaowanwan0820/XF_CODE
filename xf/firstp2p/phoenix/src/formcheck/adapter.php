<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 18:30:16
 * @encode UTF-8编码
 */
class P_Formcheck_Adapter {

    public static function valid($forms) {
        $data = array();
        if (!is_array($forms)) {
            new P_Exception_Formcheck(P_Conf_Globalerrno::$message[P_Conf_Globalerrno::FORM_CHECK_ERROR], P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        foreach ($forms as $form) {
            if (!is_array($form) || count($form) < P_Conf_Formcheck::RULE_ARGS_COUNT) {
                new P_Exception_Formcheck('invalid form rule', P_Conf_Globalerrno::FORM_CHECK_ERROR);
                return false;
            }
            $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::FRAMEWORK_PREFIX, P_Conf_Formcheck::CLASS_INFFIX, $form[P_Conf_Formcheck::INDEX_CLASS]));
            if (!class_exists($class) && !is_callable($form[P_Conf_Formcheck::INDEX_CLASS])) {
                new P_Exception_Formcheck("invalid class={$class} or uncallable={$form[P_Conf_Formcheck::INDEX_CLASS]}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
                return false;
            }
            $method = $form[P_Conf_Formcheck::INDEX_METHOD];
            if (!in_array($method, P_Conf_Formcheck::$method)) {
                new P_Exception_Formcheck("invalid method={$method}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
                return false;
            }
            $key = $form[P_Conf_Formcheck::INDEX_KEY];
            $args = $form[P_Conf_Formcheck::INDEX_ARGS];
            $values = isset($form[P_Conf_Formcheck::INDEX_VALUES]) ? $form[P_Conf_Formcheck::INDEX_VALUES] : P_Conf_Formcheck::DEFAULT_VALUES;
            $optional = isset($form[P_Conf_Formcheck::INDEX_OPTIONAL]) ? $form[P_Conf_Formcheck::INDEX_OPTIONAL] : P_Conf_Formcheck::DEFAULT_OPTIONAL;
            $default = isset($form[P_Conf_Formcheck::INDEX_DEFAULT]) ? $form[P_Conf_Formcheck::INDEX_DEFAULT] : P_Conf_Formcheck::DEFAULT_DEFAULT;
            $errormsg = isset($form[P_Conf_Formcheck::INDEX_ERROR]) ? $form[P_Conf_Formcheck::INDEX_ERROR] : P_Conf_Formcheck::DEFAULT_ERROR;
            if (class_exists($class)) {
                $obj = new $class;
                $ret = $obj->valid($method, $key, $args, $values, $optional, $default);
            } else if (is_callable($form[P_Conf_Formcheck::INDEX_CLASS])) {
                $ret = call_user_func_array($form[P_Conf_Formcheck::INDEX_CLASS], array($method, $key, $args, $values, $optional, $default));
            } else {
                return false;
            }
            if (false === $ret) {
                new P_Exception_Formcheck($errormsg, P_Conf_Globalerrno::FORM_CHECK_ERROR);
                return false;
            }
            $data = array_merge($data, $ret);
        }
        return $data;
    }

}
