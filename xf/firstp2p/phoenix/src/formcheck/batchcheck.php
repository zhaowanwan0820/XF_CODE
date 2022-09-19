<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-19 14:31:05
 * @encode UTF-8编码
 */
class P_Formcheck_Batchcheck extends P_Formcheck_Abstract {

    public function valid($method, $key, $args, $values, $optional, $default) {
        $value = $this->get_value($method, $key, $values);
        if ($value === false) {
            if ($optional) {
                $value = $default;
                return array($key => $value);
            }
            new P_Exception_Formcheck('invalid optional value', P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        if (!is_array($value) || !isset($args[P_Conf_Formcheck::BC_INDEX_CLASS], $args[P_Conf_Formcheck::BC_INDEX_ARGS])) {
            new P_Exception_Formcheck('invalid args', P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::FRAMEWORK_PREFIX, P_Conf_Formcheck::CLASS_INFFIX, $args[P_Conf_Formcheck::BC_INDEX_CLASS]));
        if (!class_exists($class)) {
            new P_Exception_Formcheck("invalid class={$class}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        $obj = new $class;
        foreach ($value as $k => $v) {
            if (false === ($ret = $obj->valid(P_Conf_Formcheck::METHOD_CUSTOM, $key, $args[P_Conf_Formcheck::BC_INDEX_ARGS], array($key => $v), $optional, $default))) {
                return false;
            }
            $value[$k] = $ret[$key];
        }
        return array($key => $value);
    }

}
