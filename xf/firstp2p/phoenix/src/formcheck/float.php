<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-19 14:35:31
 * @encode UTF-8编码
 */
class P_Formcheck_Float extends P_Formcheck_Abstract {

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
        $float_value = floatval($value);
        if (strval($float_value) != $value) {
            new P_Exception_Formcheck("invalid type value={$value}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        if (isset($args[P_Conf_Formcheck::NUM_INDEX_LOWER], $args[P_Conf_Formcheck::NUM_INDEX_UPPER])) {
            $args[P_Conf_Formcheck::NUM_INDEX_LOWER] = floatval($args[P_Conf_Formcheck::NUM_INDEX_LOWER]);
            $args[P_Conf_Formcheck::NUM_INDEX_UPPER] = floatval($args[P_Conf_Formcheck::NUM_INDEX_UPPER]);
            if ($float_value < $args[P_Conf_Formcheck::NUM_INDEX_LOWER] || $float_value > $args[P_Conf_Formcheck::NUM_INDEX_UPPER]) {
                new P_Exception_Formcheck("invalid range value={$value}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
                return false;
            }
        }
        if (isset($args[P_Conf_Formcheck::NUM_INDEX_ROUND])) {
            $value = round($float_value, intval($args[P_Conf_Formcheck::NUM_INDEX_ROUND]));
        }
        return array($key => $value);
    }

}
