<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-20 11:28:04
 * @encode UTF-8编码
 */
class P_Formcheck_Mixstring extends P_Formcheck_Abstract {

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
        $ret = array($key => $value);
        if (!isset($args[P_Conf_Formcheck::NUM_INDEX_LOWER], $args[P_Conf_Formcheck::NUM_INDEX_UPPER])) {
            return array($key => $value);
        }
        $width = (isset($args[P_Conf_Formcheck::MIXSTRING_WIDTH]) && !empty($args[P_Conf_Formcheck::MIXSTRING_WIDTH]))
               ? intval($args[P_Conf_Formcheck::MIXSTRING_WIDTH]) : P_Conf_Formcheck::DEFAULT_MIXSTRING_WIDTH;
        $encoding = mb_detect_encoding($value);
        $mb_len = mb_strlen($value, $encoding);
        $mb_width = mb_strwidth($value, $encoding);
        $len = 0;
        for ($i = 0;$i < $mb_len;$i++) {
            $char = mb_substr($value, $i, 1, $encoding);
            if (mb_strwidth($char, $encoding) > 1) {
                $len += $width;
            } else {
                $len += 1;
            }
        }
        if ($len < $args[P_Conf_Formcheck::NUM_INDEX_LOWER] || $len > $args[P_Conf_Formcheck::NUM_INDEX_UPPER]) {
            new P_Exception_Formcheck("invalid range value={$value}", P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        return $ret;
    }

}
