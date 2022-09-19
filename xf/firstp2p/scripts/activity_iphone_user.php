<?php

/*
 * @anstract 
 * 获得具有iphone6抽奖资格的用户名单
 * @date    2014-10-11 
 * @author  yutao<yutao@ucfgroup.com>
 * 
 * @crontab 01 18 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php activity_iphone_user.php
 */

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');
require_once(dirname(__FILE__) . '/../libs/utils/Logger.php');
require_once dirname(__FILE__) . '/../libs/common/functions.php';

use core\service\ActivityIphoneService;

//$todayStr = isset($argv[1]) ? date('Y-m-d', strtotime($argv[1])) : date('Y-m-d');
$todayStr = date('Y-m-d');

class ActivityIphoneUser {

    private $_messageToDAI = 'http://fastsms.corp.ncfgroup.com/service?route=licai_alert&token=m6kghthd&func=ss&to=15313660021&msg=iphone抽奖用户统计当天异常';
    private $_messageToTAO = 'http://fastsms.corp.ncfgroup.com/service?route=licai_alert&token=m6kghthd&func=ss&to=18611955272&msg=iphone抽奖用户统计当天异常';
    private $_db;
    private $_todayStr;
    private $_service;

    function __construct($_db, $_todayStr) {
        $this->_db = $_db;
        $this->_todayStr = $_todayStr;
        $this->_service = new ActivityIphoneService();
    }

    public function run() {
        $userList = $this->getDealUser();
        $userList = $this->processUserArray($userList);
        return $this->insertUsers($userList);
    }

    /**
     * 获取前天18:00到当天18:00的投资金额大于100的用户
     * return array
     */
    public function getDealUser() {
        $yesterdayStr = date("Y-m-d", strtotime("-1 day", strtotime($this->_todayStr)));
        //由于deal_load表中时间戳提前8h，故减去
        $beginTimestamp = strtotime($yesterdayStr . " 18:00:00") - 8 * 60 * 60;
        //$beginTimestamp = 0;
        $endTimestamp = strtotime($this->_todayStr . " 18:00:00") - 8 * 60 * 60;
        $sql = "SELECT user_id,user_name,create_time as deal_time,COUNT(DISTINCT user_id) as count FROM firstp2p_deal_load WHERE create_time >= '{$beginTimestamp}' and create_time < '{$endTimestamp}' and money >= 100 and source_type IN (3,4) GROUP BY user_id ORDER BY create_time ASC";

        echo $sql . "\n";
        $res = $this->_db->query($sql);
        while ($row = $this->_db->fetch_array($res)) {
            $ret[] = $row;
        }
        echo "$yesterdayStr 18:00:00 - $this->_todayStr 18:00:00 lottery user count is " . count($ret) . "\n";
        if (!empty($ret) && count($ret) > 0) {
            return $ret;
        }
        return NULL;
    }

    /**
     * 
     * @param type $userArray
     * @return array
     */
    public function processUserArray($userArray) {
        if (!empty($userArray)) {
            foreach ($userArray as $key => $value) {
                unset($userArray[$key]['count']);
                $userArray[$key]['user_lottery_num'] = substr('0000' . ($key + 1), -5);
                //$userArray[$key]['stat_time'] = strtotime($this->_todayStr);
            }
            return $userArray;
        }
        return NULL;
    }

    public function insertUsers($userArray) {
        return $this->_service->insertUserList($userArray);
    }

    function getCurl($who) {
        if (empty($who)) {
            return false;
        }

        switch ($who) {
            case 'daiyuxin':
                $url = $this->_messageToDAI;
                break;

            case 'yutao':
                $url = $this->_messageToTAO;
                break;
            default:
                break;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, '');
        curl_setopt($ch, CURLOPT_REFERER, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $error = curl_errno($ch);
        var_dump($error);
        curl_close($ch);
        return $result;
    }

}

$activity = new ActivityIphoneUser($GLOBALS['db'], $todayStr);

if ($activity->run() > 0) {
    var_dump("success");
} else {
    $activity->getCurl("yutao");
    $activity->getCurl("daiyuxin");
    var_dump("insert failed");
}

