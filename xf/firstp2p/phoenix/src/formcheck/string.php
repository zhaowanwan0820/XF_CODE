<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-19 14:37:41
 * @encode UTF-8编码
 */
class P_Formcheck_String extends P_Formcheck_Abstract {

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
        if (!is_scalar($value)) {
            new P_Exception_Formcheck("invalid type value={$value}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        $value = strval($value);
        if (!isset($args[P_Conf_Formcheck::NUM_INDEX_LOWER], $args[P_Conf_Formcheck::NUM_INDEX_UPPER])) {
            return array($key => $value);
        }
        if (isset($args[P_Conf_Formcheck::STRING_INDEX_STRLEN]) && !$args[P_Conf_Formcheck::STRING_INDEX_STRLEN]) {
            if (isset($args[P_Conf_Formcheck::STRING_INDEX_ENCODING]) && in_array($args[P_Conf_Formcheck::STRING_INDEX_ENCODING], mb_list_encodings())) {
                $len = mb_strlen($value, $args[P_Conf_Formcheck::STRING_INDEX_ENCODING]);
            } else {
                $len = mb_strlen($value, mb_detect_encoding($value));
            }
        } else {
            $len = strlen($value);
        }
        if ($len < $args[P_Conf_Formcheck::NUM_INDEX_LOWER] || $len > $args[P_Conf_Formcheck::NUM_INDEX_UPPER]) {
            new P_Exception_Formcheck("invalid range value={$value}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        return array($key => $value);
    }

}
