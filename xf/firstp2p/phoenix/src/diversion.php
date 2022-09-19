<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 14:47:59
 * @encode UTF-8编码
 */
class P_Diversion {

    private static function _diversion_abtest() {
        return '';
    }

    private static function _diversion_version($rules) {
        if (isset($rules['prefix'])) {
            $prefix = ucfirst(strtolower(trim($rules['prefix'])));
        } else {
            $prefix = '';
        }
        $version = P_Http::server(P_Conf_Diversion::DIVERSION_VERSION_KEY);
        foreach (P_Conf_Diversion::$diversion_version_vars as $var) {
            if (false !== $version) {
                break;
            }
            $version = P_Http::get($var);
        }
        if (false === $version) {
            return '';
        }
        return $prefix . $version;
    }

    public static function diversion() {
        $diversion_rules = M::C('diversion_rules');
        if (!is_array($diversion_rules) || !isset($diversion_rules['type']) || !in_array($diversion_rules['type'], P_Conf_Diversion::$valid_diversion)) {
            return M::D('APP');
        }
        $function = $diversion_rules['type'];
        return self::$function($diversion_rules);
    }

}
