<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-19 14:36:25
 * @encode UTF-8编码
 */
class P_Formcheck_Int extends P_Formcheck_Abstract {

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
        $int_value = intval($value);
        if (strval($int_value) != $value) {
            new P_Exception_Formcheck("invalid type value={$value}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        if (isset($args[P_Conf_Formcheck::NUM_INDEX_LOWER], $args[P_Conf_Formcheck::NUM_INDEX_UPPER])) {
            $args[P_Conf_Formcheck::NUM_INDEX_LOWER] = intval($args[P_Conf_Formcheck::NUM_INDEX_LOWER]);
            $args[P_Conf_Formcheck::NUM_INDEX_UPPER] = intval($args[P_Conf_Formcheck::NUM_INDEX_UPPER]);
            if ($int_value < $args[P_Conf_Formcheck::NUM_INDEX_LOWER] || $int_value > $args[P_Conf_Formcheck::NUM_INDEX_UPPER]) {
                new P_Exception_Formcheck("invalid range value={$value}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
                return false;
            }
        }
        return array($key => $value);
    }

}
