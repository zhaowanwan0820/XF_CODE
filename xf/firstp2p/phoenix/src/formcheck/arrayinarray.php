<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-17 17:51:25
 * @encode UTF-8编码
 */
class P_Formcheck_Arrayinarray extends P_Formcheck_Abstract {

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
        if (!is_array($value)) {
            new P_Exception_Formcheck('invalid value array', P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        if (!is_array($args)) {
            new P_Exception_Formcheck('invalid args', P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        foreach ($value as $v) {
            if (!in_array($v, $args)) {
                new P_Exception_Formcheck("invalid value={$v}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
                return false;
            }
        }
        return array($key => $value);
    }

}
