<?php

/**
 * Block class file
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 */

namespace libs\utils;

/**
 * 处理频次封禁类
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */
class Block {
    const HASH_SPECIAL_USERS = "hash_special_users";
    const HASH_CONCURRENCY_LIMIT = "hash_rules_concurrency_limit";
    const HASH_FREQUENCY_LIMIT = "hash_rules_frequency_limit";
    const FREQUENCY_PREFIX = "fre/";
    const CONCURRENCY_PREFIX = "con/";
    const LIMIT_FORBIDDEN_ACCESS = 0;
    const KEYS_EXPIRATION_TIME = 90;

    // todo 是否需要把封杀规则加到配置中
    private static $_arr_block = array(
        'LOGIN_USERNAME' => array("prefix" => "login_username", "time" => 60, "count" => 3),
        'ENTERPRISE_LOGIN_USERNAME' => array("prefix" => "login_enterprise_username", "time" => 86400, "count" => 5),
        'REGISTER_USER_CODE_TODAY' => array("prefix" => "register_user_code_today", "time" => 86400, "count" => 10),
        'REGISTER_USER_CODE_SECOND' => array("prefix" => "register_user_code_second", "time" => 60, "count" => 1),
        'CLIENT_REGISTER_USER_CODE_SECOND' => array("prefix" => "client_register_user_code_second", "time" => 60, "count" => 1),
        'USERNAME_EXIST' => array('prefix' => 'username_exist', 'time' => 10, 'count' => 10),
        'WEB_LOGIN_USERNAME' => array("prefix" => "web_login_username", "time" => 60, "count" => 3),
        'WEB_LOGIN_IP' => array("prefix" => "web_login_ip", "time" => 300, "count" => 100),
        'OLDPWD_CHECK_HOURS' => array("prefix" => "pwd_modify", "time" => 7200, "count" => 5),//修改密码功能中旧密码的频率限制，两小时最多输错5次
        'MODIFYPWD_CHECK_IDNO_HOURS' => array("prefix" => "idno_pwd_modify", "time" => 7200, "count" => 5),//修改密码功能中身份证号的频率限制，两小时最多输错5次
        //由于市场部活动 暂时将每天IP频次限制更改为300
        'SEND_SMS_IP_TODAY' => array("prefix" => "send_sms_today", "time" => 86400, "count" => 2000),
        'SEND_SMS_IP_MINUTE' => array("prefix" => "send_sms_minute", "time" => 60, "count" => 20),
        'SEND_SMS_PHONE_HOUR' => array("prefix" => "send_sms_phone_hour", "time" => 3600, "count" => 20),

        'USERNAME_CHECK_MINUTE'=>array("prefix" => "username_check_minute", "time" => 60, "count" => 10),
        'USERNAME_CHECK_HOUR'=>array("prefix" => "username_check_hour", "time" => 3600, "count" => 20),
        'USERNAME_CHECK_VCODE_MINUTE'=>array("prefix" => "username_check_vcode_minute", "time" => 60, "count" => 10),
        'USERNAME_IP_CHECK_MINUTE'=>array("prefix" => "username_ip_check_minute", "time" => 60, "count" => 30),
        'USERNAME_IP_CHECK_HOUR'=>array("prefix" => "username_ip_check_hour", "time" => 3600, "count" => 200),
        'USERNAME_IP_CHECK_VCODE_MINUTE'=>array("prefix" => "username_ip_check_vcode_minute", "time" => 60, "count" => 30),
        'USER_SUMMARY_BLOCK'=>array("prefix" => "user_summary_block", "time" => 60, "count" => 10),
        'YEEPAY_BIND_BANKCARD_SECOND' => array('prefix' => 'yeepay_bind_bankcard_second', 'time' => 60, 'count' => 1), // 易宝-绑卡请求接口频率，60s请求1次
        'SM_LOGIN_CODE_VERIFY_RV_CN_IP' => array('prefix' => 'sm_login_code_verify_rv_cn_ip', 'time' => 60, 'count' => 4), // 短信登录，接受短信后，验证次数 WebDoLogin使用
        'SM_LOGIN_CODE_VERIFY_RV_CN_PHONE' => array('prefix' => 'sm_login_code_verify_rv_cn_phone', 'time' => 60, 'count' => 4), // 短信登录，接受短信后，验证次数，WebDoLogin使用
        'SM_LOGIN_CODE_VERIFY_RV_CN_PHONE_LAST' => array('prefix' => 'sm_login_code_verify_rv_cn_phone_last', 'time' => 60, 'count' => 3), // 短信登录，接受短信后，验证次数,SmDoLogin使用
        'SM_LOGIN_CODE_VERIFY_RV_CN_IP_LAST' => array('prefix' => 'sm_login_code_verify_rv_cn_ip_last', 'time' => 60, 'count' => 3), // 短信登录，接受短信后，验证次数，SmDoLogin使用
        'WESHARE_CONTRACT_DOWN_MINUTE' => array('prefix' => 'weshare_contract_down_minute', 'time' => 60, 'count' => 150), // 资产端下载合同接口频率限制
        'RETAIL_GETREPAYPLAN_DOWN_MINUTE' => array('prefix' => 'retail_getrepayplan_down_minute', 'time' => 60, 'count' => 60), // 零售信贷请求还款计划接口频率限制，一分钟请求60次
        'CREDIT_GET_DEALS_BY_STATUS_DOWN_MINUTE' => array('prefix' => 'credit_get_deals_by_status_down_minute', 'time' => 60, 'count' => 60), // 信贷请求根据状态获取标的的放款审批单号频率限制，一分钟请求60次
        'DSD_REPAYTRIAL_DOWN_MINUTE' => array('prefix' => 'dsd_repaytrial_down_minute', 'time' => 60, 'count' => 100), // 电商贷请求还款试算接口频率限制，一分钟请求60次
        'DSD_REPAY_DOWN_MINUTE' => array('prefix' => 'dsd_repay_down_minute', 'time' => 60, 'count' => 100), // 电商贷请求还款接口频率限制，一分钟请求60次
        'USER_LOGIN_IP_LIMIT' => array('prefix' => 'user_login_ip_limit', 'time' => 120, 'count' => 10), //同一ip登陆失败次数限制
    );

    /**
     * 用于进行频次封禁
     * @param string $key 封杀规则
     * @param string $value key对应的值
     * @param bool $is_check true-只检查不封杀 false-封杀并检查
     * @param bool $return_times 是否返回记录次数
     * @return bool true-正常 false-被封禁
     */
    public static function check($key, $value, $check_only = false, $return_times = false) {
        $arr_block = self::$_arr_block[$key];
        $data = \SiteApp::init()->cache->get($arr_block["prefix"] . "_" . $value);
        if ($data) {
            $arr = json_decode($data, true);
            // 过滤每个封禁时间，如果时间超过了预定时间，则删除此次记录
            foreach ($arr as $time) {
                if ($time < get_gmtime() - $arr_block["time"]) {
                    array_shift($arr);
                } else {
                    break;
                }
            }
        } else {
            $arr = array();
        }

        // 如果只检查不封杀
        if ($check_only !== false) {
            // 判断频次是否超过限制
            if (count($arr) >= $arr_block["count"]) {
                return false;
            } else {
                return true;
            }
        }

        $arr[] = get_gmtime();
        $res = true;

        // 过滤后若记录数超过预期，则返回false
        if (count($arr) > $arr_block["count"]) {
            // 为了避免使用内存过大，如果超过频次限制，则保持数组内只有限制个数
            array_shift($arr);
            $res = false;
        } elseif (count($arr) == $arr_block["count"]) {
            $res = false;
        }

        $data_new = json_encode($arr);
        \SiteApp::init()->cache->set($arr_block["prefix"] . "_" . $value, $data_new, $arr_block["time"]);
        return $return_times ? count($arr) : $res;
    }

    /**
     * 访问限制检查
     */
    public static function checkAccessLimit($userId) {
        if (empty($userId)) {
            return true;
        }

        $rule = self::getRuleName();
        $result = self::limitConcurrency($rule, $userId) && self::limitFrequency($rule, $userId);
        if ($result === false) {
            \libs\utils\Logger::info("AccessLimited. userId:{$userId}, rule:{$rule}");
        }

        return $result;
    }

    public static function addSpecialUser($userId, $value = 1) {
        return \SiteApp::init()->dataCache->getRedisInstance()->hSet(self::HASH_SPECIAL_USERS, $userId, $value);
    }

    public static function delSpecialUser($userId) {
        return \SiteApp::init()->dataCache->getRedisInstance()->hDel(self::HASH_SPECIAL_USERS, $userId);
    }

    /**
     * 是否特殊用户
     */
    public static function isSpecialUser($userId) {
        if (empty($userId)) {
            return false;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            return false;
        }

        $result = $redis->hExists(self::HASH_SPECIAL_USERS, $userId);
        if (!empty($result)) {
            \libs\utils\Logger::info("SpecialUserAccess. userId:{$userId}");
        }

        return $result;
    }

    /**
     * 获取所有特殊用户
     */
    public static function getAllSpecialUsers() {
        return \SiteApp::init()->dataCache->getRedisInstance()->hKeys(self::HASH_SPECIAL_USERS);
    }

    public static function addConcurrencyRule($rule, $count) {
        return \SiteApp::init()->dataCache->getRedisInstance()->hSet(self::HASH_CONCURRENCY_LIMIT, $rule, $count);
    }

    public static function delConcurrencyRule($rule) {
        return \SiteApp::init()->dataCache->getRedisInstance()->hDel(self::HASH_CONCURRENCY_LIMIT, $rule);
    }

    public static function getConcurrencyRule($rule) {
        return \SiteApp::init()->dataCache->getRedisInstance()->hGet(self::HASH_CONCURRENCY_LIMIT, $rule);
    }

    /**
     * 获取所有并发限制规则
     */
    public static function getAllConcurrencyRules() {
        return \SiteApp::init()->dataCache->getRedisInstance()->hGetAll(self::HASH_CONCURRENCY_LIMIT);
    }

    public static function addFrequencyRule($rule, $count) {
        return \SiteApp::init()->dataCache->getRedisInstance()->hSet(self::HASH_FREQUENCY_LIMIT, $rule, $count);
    }

    public static function delFrequencyRule($rule) {
        return \SiteApp::init()->dataCache->getRedisInstance()->hDel(self::HASH_FREQUENCY_LIMIT, $rule);
    }

    public static function getFrequencyRule($rule) {
        return \SiteApp::init()->dataCache->getRedisInstance()->hGet(self::HASH_FREQUENCY_LIMIT, $rule);
    }

    /**
     * 获取所有频率限制规则
     */
    public static function getAllFrequencyRules() {
        return \SiteApp::init()->dataCache->getRedisInstance()->hGetAll(self::HASH_FREQUENCY_LIMIT);
    }

    /**
     * 根据接口来做限制，这个函数获取访问的接口。
     * @return string
     */
    public static function getRuleName() {
        $uriPath = explode("?", $_SERVER['REQUEST_URI']);
        if(is_array($uriPath)) {
            $uriPath = $uriPath[0];
        }
        return rtrim($uriPath, "/");
    }

    /**
     * 限制用户在1min中访问的频率
     * @param $rule: 限制规则的名称
     * @return bool
     */
    public static function limitFrequency($rule, $userId, $period = 60) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            return true;
        }

        $frequency = $redis->hGet(self::HASH_FREQUENCY_LIMIT, $rule);
        if ($frequency === false || is_null($frequency)) {
            return true;
        }

        $frequency = intval($frequency);
        if($frequency <= self::LIMIT_FORBIDDEN_ACCESS) {
            return false;
        }

        $key = self::FREQUENCY_PREFIX . $rule . "/" . $userId;
        $nowTime = microtime(true);
        $count = $redis->lLen($key);

        if ($frequency > $count) {//队列没满，可以直接访问
            $redis->rPush($key, $nowTime);
            $redis->expire($key, self::KEYS_EXPIRATION_TIME);
            return true;
        }

        $firstAccessTime = $redis->lIndex($key, 0);
        $diffTime = round(($nowTime - $firstAccessTime), 2);  //diffTime seconds
        if($diffTime < $period) { //当前计时周期
            return false;
        }

        $redis->lPop($key);
        $redis->rPush($key, $nowTime);
        $redis->expire($key, self::KEYS_EXPIRATION_TIME);
        return true;
    }

    /**
     * 限制用户的并发访问
     * @param $rule: 限制规则
     * @return bool
     */
    public static function limitConcurrency($rule, $userId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if(!$redis) {
            return true;
        }
        $concurrency = $redis->hGet(self::HASH_CONCURRENCY_LIMIT, $rule);
        if($concurrency === false || is_null($concurrency)) {
            return true;
        }

        $concurrency = intval($concurrency);
        if($concurrency <= self::LIMIT_FORBIDDEN_ACCESS) {
            return false;
        }

        $key = self::CONCURRENCY_PREFIX . $rule . "/" . $userId;
        $concurrencyCount = $redis->incr($key);
        $redis->expire($key, self::KEYS_EXPIRATION_TIME);
        register_shutdown_function(array("\\libs\\utils\\Block", "decrConcurrencyCount"), $key);
        if($concurrencyCount === false) {
            return true;
        }

        if($concurrencyCount > $concurrency) {
            return false;
        }

        return true;
    }

    public static function decrConcurrencyCount($key) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            $redis->decr($key);
        }
    }
}
