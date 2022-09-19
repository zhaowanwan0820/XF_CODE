<?php
/*
 * 获取数据字典内容
 * @author wangyiming@ucfgroup.com
 */
FP::import("libs.common.site");
class dict {
    const CACHE_PREFIX = "dict_";
    const EXPIRE_DEFAULT = 86400;
    /*
     * 根据key获取字典内容
     * @param $key string
     * @return array 字典内容
     *         false 数据库中无内容
     */
    public static function get($key) {
        $value = self::get_cache_by_key($key);
        if ($value) {
            return json_decode($value, true);
        } else {
            $dict = self::get_data_by_key($key);
            if (!$dict) {
                return false;
            }
            self::set_cache($key, json_encode($dict['value']));
            return $dict['value'];
        }
    }

    /*
     * 根据key清除字典内容
     * @param $key string
     * @return boolean
     */
    public static function del($key) {
        return SiteApp::init()->cache->delete(self::CACHE_PREFIX . $key);
    }

    /*
     * 根据key获取数据库中的字典内容
     * @param $key string
     * @return array
     */
    private static function get_data_by_key($key) {
        $result = $GLOBALS['db']->get_slave()->getRow("SELECT * FROM " . DB_PREFIX . "dictionary WHERE `key`='{$key}'");
        if (!$result) {
            return false;
        }
        $values = $GLOBALS['db']->get_slave()->getAll("SELECT * FROM " . DB_PREFIX . "dictionary_value WHERE `key_id`='{$result['id']}'");
        if (!$values) {
            $result['value'] = array();
        } else {
            $arr_val = array();
            foreach ($values as $v) {
                $arr_val[] = $v['value'];
            }
            $result['value'] = $arr_val;
        }
        return $result;
    }

    /*
     * 根据key获取缓存中的字典内容
     * @param $key string
     * @return string
     */
    public static function get_cache_by_key($key) {
        return SiteApp::init()->cache->get(self::CACHE_PREFIX . $key);
    }

    /*
     * 将key与value存进缓存中
     * @param $key string
     * @param $value string
     * @return boolean
     */
    private static function set_cache($key, $value) {
        return SiteApp::init()->cache->set(self::CACHE_PREFIX . $key, $value, self::EXPIRE_DEFAULT);
    }

}
