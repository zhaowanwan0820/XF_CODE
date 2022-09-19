<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-19 14:37:04
 * @encode UTF-8编码
 */
class P_Formcheck_Regex extends P_Formcheck_Abstract {

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
        if (!is_string($value) || !is_string($args) || !preg_match($args, $value)) {
            new P_Exception_Formcheck("invalid args or value={$value}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        return array($key => $value);
    }

}
