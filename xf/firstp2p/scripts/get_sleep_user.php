<?php

/**
 * 获得沉睡用户脚本
 * 建议在主从切换后，更改为查询从库
 * 
 * 业务逻辑：比较user表和deal_load表中，从未投资的用户，将用户IDS写入缓存并排除特殊用户
 * 定时任务：每隔一小时，执行一次脚本
 * 
 * add by yutao
 */
ini_set('memory_limit', '512M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');
require_once(dirname(__FILE__) . '/../libs/utils/Logger.php');
require_once dirname(__FILE__) . '/../libs/common/functions.php';

class SleepUserTask {

    private $_db;
    private $_redis;

    const QUERY_SIZE = 2;
    const RedisKey = 'FIRSTP2P_SLEEP_USER';

    function __construct($db, $redis) {
        $this->_db = $db;
        $this->_redis = new Redis();
        $this->_redis->connect($redis['hostname'], $redis['port']);
        $this->_redis->select($redis['database']);
    }

    public function run() {
        $sleepUsers = $this->getSleepUserId($this->getSpecialGroupIds(),"create_time");
        $this->saveRedis(self::RedisKey."_CREATE",$sleepUsers);
        $sleepUsers = $this->getSleepUserId($this->getSpecialGroupIds(),"login_time");
        $this->saveRedis(self::RedisKey."_LOGIN",$sleepUsers);
        $sleepUsers = $this->getSleepUserId(NULL,"create_time");
        $this->saveRedis(self::RedisKey."_CREATE_GROUP",$sleepUsers);
        $sleepUsers = $this->getSleepUserId(NULL,"login_time");
        $this->saveRedis(self::RedisKey."_LOGIN_GROUP",$sleepUsers);
        $this->closeRedis();
    }

    /*
     * 获得投资的用户ID
     * return array or NULL
     */

    public function getUserIdDeal() {
        $sql = "SELECT DISTINCT user_id FROM " . DB_PREFIX . "deal_load";
        $res = $this->_db->query($sql);
        while ($row = $this->_db->fetch_array($res)) {
            $ret[] = $row['user_id'];
        }

        if (!empty($ret) && count($ret) > 0) {
            return $ret;
        }
        return NULL;
    }

    /*
     * 获得未投资用户ID（排除特殊用户组）
     * return array or NULL
     */

    public function getSleepUserId($groupIds,$order) {
        $ret = array();
        //$queryUserIds = implode(',', $dealUserIds);
        $queryUserIds = "SELECT DISTINCT user_id FROM " . DB_PREFIX . "deal_load";
        $sql = "SELECT id FROM " . DB_PREFIX . "user WHERE real_name != '' and login_time != 0 and id NOT IN ({$queryUserIds}) ";
        if (!empty($groupIds)) {
            $sql .= "and group_id NOT IN ({$groupIds})";
        }
        $sql .= " ORDER BY {$order} ASC";
        echo $sql."\n";
        $res = $this->_db->query($sql);
        while ($row = $this->_db->fetch_array($res)) {
            $ret[] = $row['id'];
        }
        if (!empty($ret) && count($ret) > 0) {
            return $ret;
        }
        return NULL;
    }

    /*
     * 获得特殊用户组ID
     * return array or NULL
     */

    public function getSpecialGroupIds() {
        $groupIds = $GLOBALS['sys_config']['SPECIAL_COUPON_USER_GROUP'];

        if (!empty($groupIds)) {
            return $groupIds;
        }
        return NULL;
    }

    /*
     * 结果存入redis
     */

    public function saveRedis($redisKey,$userIds) {
        $this->delRedis($redisKey);
        foreach ($userIds as $key => $value) {
            $this->_redis->rPush($redisKey, $value);
        }
    }

    public function delRedis($key) {
        $this->_redis->del($key);
        
    }

    public function closeRedis() {
        return $this->_redis->close();
    }

}

$sleepUserTask = new SleepUserTask($GLOBALS['db'], $GLOBALS['components_config']['components']['dataCache']);
$sleepUserTask->run();
