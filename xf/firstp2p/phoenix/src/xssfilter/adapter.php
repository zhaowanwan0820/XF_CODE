<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-11 12:06:20
 * @encode UTF-8编码
 */
class P_Xssfilter_Adapter {

    public static function filter($input, $config = false) {
        if ($config === false) {
            $config = array(
                "white_tags" => P_Conf_Xssfilter::$xss['white_tags'],
            );
        }
        $filter_obj = new P_Xssfilter_Filter();
        return $filter_obj->parse($input, $config);
    }

}
