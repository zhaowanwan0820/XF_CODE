<?php
/**
 * BonusModel class file.
 *
 * @author pengchagnlu@ucfgroup.com
 */

namespace core\dao;

use core\dao\BonusModel;

/**
 * 红包配置
 *
 * @author pengchanglu@ucfgroup.com
 */
class BonusConfModel extends BaseModel
{
    /**
     * 规则集合
     * @var null
     */
    static private $_rules = null;

    /**
     * 互斥键名
     * @var array
     */
    static private $_mutexKeys = array(
        'MUTEX_RULE_NAME', 'MUTEX_RULE_OUT_GROUPID',
        'MUTEX_RULE_TYPE', 'MUTEX_RULE_GROUPID',
    );

    /**
     * 互斥集合
     * @var array
     */
    static private $_mutex = array();

    /**
     * changlu
     * 通过 key 获取配置
     * @param $key
     */
    public static function get($key) {
        if (self::$_rules == null) {
            self::getStatic();
        }
        if (isset(self::$_rules[$key])) {
            return self::$_rules[$key];
        }
        return false;
    }

    private static function getStatic()
    {
        $list = BonusConfModel::instance()->findAllViaSlave('is_effect = 1', true, '`name`,`value`,`end_time`,`start_time`');
        if (!$list) {
            return false;
        }
        $now = get_gmtime();
        foreach ($list as $k => $v) {
            if ($v['end_time'] < $now || $now < $v['start_time']) {
                continue;
            }
            self::$_rules[$v['name']] = $v['value'];
        }
    }

    /**
     * 获取互斥配置
     * @return [type] [description]
     */
    public static function getMutexConf()
    {
        if (count(self::$_mutex) > 0) return self::$_mutex;
        foreach (self::$_mutexKeys as $key) {
            $conf = self::getConfLike($key);
            $tmp = array();
            foreach ($conf as &$item) {
                $item = explode('|', $item);
                $tmp = array_merge($tmp, $item);
            }
            // 将红包类型字符转为类型
            if ($key == 'MUTEX_RULE_TYPE') {
                $bonus = new BonusModel();
                foreach ($tmp as &$k) {
                    $k = constant(get_class($bonus) .'::'.trim($k));
                }
            }
            self::$_mutex[$key] = $tmp;
        }
        return self::$_mutex;
    }

    public static function getConfLike($key)
    {
        if (self::$_rules == null) {
            self::getStatic();
        }
        $keys = array_keys(self::$_rules);
        $res = array();
        foreach ($keys as $k) {
            if (strpos($k, $key) !== false) {
                $res[$k] = self::$_rules[$k];
            }
        }
        return $res;
    }

}
