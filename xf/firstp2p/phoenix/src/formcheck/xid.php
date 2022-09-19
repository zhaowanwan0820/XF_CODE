<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-23 17:28:10
 * @encode UTF-8编码
 */
class P_Formcheck_Xid extends P_Formcheck_Abstract {

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
        if (!isset($args[P_Conf_Formcheck::XID_INDEX_KEY], $args[P_Conf_Formcheck::XID_INDEX_CLASS], $args[P_Conf_Formcheck::XID_INDEX_ARGS])) {
            new P_Exception_Formcheck('invalid args', P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        if (is_scalar($args[P_Conf_Formcheck::XID_INDEX_KEY])) {
            $args[P_Conf_Formcheck::XID_INDEX_KEY] = array(array($args[P_Conf_Formcheck::XID_INDEX_KEY]));
        } else if (is_array($args[P_Conf_Formcheck::XID_INDEX_KEY]) && isset($args[P_Conf_Formcheck::XID_INDEX_KEY][P_Conf_Formcheck::XID_INDEX_KEY])) {
            $args[P_Conf_Formcheck::XID_INDEX_KEY] = array($args[P_Conf_Formcheck::XID_INDEX_KEY]);
        } else {
            new P_Exception_Formcheck('invalid args', P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        $decrypt = false;
        foreach ($args[P_Conf_Formcheck::XID_INDEX_KEY] as $item) {
            if (!is_array($item) || !isset($item[P_Conf_Formcheck::XID_INDEX_KEY])) {
                continue;
            }
            $xid_key = trim(strval($item[P_Conf_Formcheck::XID_INDEX_KEY]));
            if (isset($item[P_Conf_Formcheck::XID_INDEX_TOKEN])) {
                $token = intval($item[P_Conf_Formcheck::XID_INDEX_TOKEN]);
            } else {
                $token = false;
            }
            $obj_xid = new P_Crypt_Xid($xid_key, $token);
            if (false !== ($decrypt = $obj_xid->decrypt($value))) {
                $value = $decrypt;
                break;
            }
        }
        if (false === $decrypt) {
            new P_Exception_Formcheck('invalid value for decrypt', P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::FRAMEWORK_PREFIX, P_Conf_Formcheck::CLASS_INFFIX, $args[P_Conf_Formcheck::XID_INDEX_CLASS]));
        if (!class_exists($class)) {
            new P_Exception_Formcheck("invalid class={$class}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        $obj = new $class;
        if (false === $obj->valid(P_Conf_Formcheck::METHOD_CUSTOM, $key, $args[P_Conf_Formcheck::XID_INDEX_ARGS], array($key => $value), $optional, $default)) {
            return false;
        }
        return array($key => $value);
    }

}
