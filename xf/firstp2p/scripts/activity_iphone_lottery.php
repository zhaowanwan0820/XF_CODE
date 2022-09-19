<?php

/*
 * @anstract 
 * 抓取福彩号码，并完成抽奖
 * @date    2014-10-14
 * @author  yutao<yutao@ucfgroup.com>
 * 
 * @crontab 50 20 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php activity_iphone_lottery.php
 */

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');
require_once(dirname(__FILE__) . '/../libs/utils/Logger.php');
require_once dirname(__FILE__) . '/../libs/common/functions.php';

use core\service\ActivityIphoneService;

//$todayStr = isset($argv[1]) ? date('Y-m-d', strtotime($argv[1])) : date('Y-m-d');
$todayStr = date('Y-m-d');

class ActivityIphoneLottery {

    private $_lotteryUrl = "http://www.baidu.com/s?wd=%E7%A6%8F%E5%BD%A93D&rsv_spt=1&issp=1&f=8&rsv_bp=0&ie=utf-8&tn=baiduhome_pg&rsv_enter=0&rsv_sug3=10&rsv_sug4=456&rsv_sug1=12&rsp=2&inputT=4045&rsv_sug=2";
    private $_messageUrl = 'http://fastsms.corp.ncfgroup.com/service?route=licai_alert&token=m6kghthd&func=ss';
    private $_db;
    private $_todayStr;
    private $_service;

    function __construct($_db, $_todayStr) {
        $this->_db = $_db;
        $this->_todayStr = $_todayStr;
        $this->_service = new ActivityIphoneService();
    }

    public function run($lotteryStr, $time) {
        if (empty($time)) {
            $time = time();
        }
        if (empty($lotteryStr)) {
            $lotteryStr = $this->getLotteryNum();
        }
        var_dump($lotteryStr);
        if (empty($lotteryStr)) {
            //message
            $this->message("18611955272", "$this->_todayStr,lottery:彩票抓取异常");
            $this->message("15313660021", "$this->_todayStr,lottery:彩票抓取异常");
            exit;
        }
        if ($this->insertLottery($lotteryStr, $time) <= 0) {
            //彩票插入异常
            $this->message("18611955272", "$this->_todayStr,lottery:彩票插入异常");
            $this->message("15313660021", "$this->_todayStr,lottery:彩票插入异常");
            exit;
        }

        $winNum = $this->calcUserWin($time);
        var_dump($winNum);
        if ($this->updateWinUser($time, $winNum) == 1) {
            var_dump("success");
        } else {
            $this->message("18611955272", "$this->_todayStr,lottery:得奖用户更新失败");
            $this->message("15313660021", "$this->_todayStr,lottery:得奖用户更新失败");
            var_dump("failed");
        }
    }

    public function updateWinUser($time, $winNum) {
        $ret = $this->_service->updateUserWin($time, $winNum);
        return $ret;
    }

    public function message($to, $message) {
        $url = $this->_messageUrl . "&to=$to&msg=$message";
        $this->getCurl($url);
    }

    public function calcUserWin($time) {
        $lotteryList = $this->_service->getLottery();
        $todayTime = strtotime(date("Y-m-d", $time));
        $yesterdayTime = $todayTime - 24 * 60 * 60;
        $lotteryValue = $lotteryList[$todayTime]['lottery_num'] . $lotteryList[$yesterdayTime]['lottery_num'];
        var_dump($lotteryValue);
        if (strlen($lotteryValue) != 6) {
            //message
            $this->message("18611955272", "$this->_todayStr,lottery:抽奖值计算失败");
            $this->message("15313660021", "$this->_todayStr,lottery:抽奖值计算失败");
            exit;
        }

        if ($lotteryValue === "000000") {
            return "00001";
        } else {
            $count = $this->_service->getUserCount($time);
            if ($count <= 0) {
                $this->message("18611955272", "$this->_todayStr,lottery:获取今日投资数量失败");
                $this->message("15313660021", "$this->_todayStr,lottery:获取今日投资数量失败");
                exit;
            }
            $ret = $lotteryValue % $count;
            if ($ret === 0) {
                $ret = $count;
            }
            return substr("0000" . $ret, -5);
        }
    }

    public function insertLottery($lotteryNum, $time) {
        return $this->_service->insertLottery($lotteryNum, $time);
    }

    public function getLotteryNum() {
        $content = $this->getCurl($this->_lotteryUrl);

        if (strlen($content) > 0) {
            $searchString = "开奖日期：" . $this->_todayStr;
            $pattern = "/$searchString.*?<span.*?op_caipiao_ball_red.*?>([0-9]{1})<\/span>.*?<span.*?op_caipiao_ball_red.*?>([0-9]{1})<\/span>.*?<span.*?op_caipiao_ball_red.*?>([0-9]{1})<\/span>/si";
            $ret = preg_match($pattern, $content, $match);
            if (count($match) == 4) {
                unset($match[0]);
                return implode(',', $match);
            }
        }

        return NULL;
    }

    public function getCurl($url) {
        if (empty($url)) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, '');
        curl_setopt($ch, CURLOPT_REFERER, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $error = curl_errno($ch);
        //var_dump($error);
        curl_close($ch);
        return $result;
    }

}

$activity = new ActivityIphoneLottery($GLOBALS['db'], $todayStr);
if (isset($argv[1]) && isset($argv[2])) {
    for ($i = 0; $i < strlen($argv[2]); $i++) {
        $lottery[] = $argv[2][$i];
    }
    $lotteryStr = implode(",", $lottery);
    $time = strtotime($argv[1]);
    if (strlen($argv[2]) === 3 && $time > 0) {
        $activity->run($lotteryStr, $time);
        //$service = new ActivityIphoneService();
        //$service->insertLottery($argv[2], $time);
        var_dump("补当天彩票数据成功");
    } else {
        var_dump("参数错误");
        exit;
    }
} else {
    $activity->run();
}

