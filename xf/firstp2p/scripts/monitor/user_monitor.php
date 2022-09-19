<?php
/**
 * @desc 监控用户 注册、实名认证、绑卡、充值、投资 行为
 * ----------------------------------------------------------------------------
 * 监控每5分钟运行一次 分别和上一个小时及前一天进行对比 超过阈值 则报警
 * ----------------------------------------------------------------------------
 * 1、统计5分钟内注册用户数
 * 2、统计5分钟内实名认证用户数
 * 3、统计5分钟内绑卡用户数
 * 4、统计5分钟内充值用户数
 * 5、统计5分钟内投标用户数
 * ----------------------------------------------------------------------------
 */

set_time_limit(0);
ini_set('memory_limit','1024M');

require_once dirname(__FILE__)."/../../app/init.php";

use libs\utils\Curl;

class UserMonitor {

    const DELTA = 500000;


    public $smsUser = array(
        'jinhaidong' => 18601165130,
        'daiyuxin' => 15313660021,
        'u1'=>13552357356,
        'u2'=>18611187809,
        'u3'=>15810716568,
        'u4'=>18611088379,
        'u5'=>18611955272,
    );

    public $db;
    public $time;
    static $sms = array();

    public function __construct() {
        $this->db = $GLOBALS["db"]->get_slave();
    }

    /**
     * 注册
     */
    public function monitorUserRegister() {
        $threshold = 80;
        $minute = 5;
        $data = $this->compareDay(DB_PREFIX.'user', 'create_time', $minute);
        if(abs($data['percent']) >= $threshold) {
            $data['threshold'] = $threshold;
            self::addSms("register", $data);
        }
    }

    /**
     * 实名认证
     */
    public function monitorUserIdcCardPassed() {
        $threshold = 80;
        $minute = 5;
        $data = $this->compareDay(DB_PREFIX.'user', 'idcardpassed_time', $minute);
        if(abs($data['percent']) >= $threshold) {
            $data['threshold'] = $threshold;
            self::addSms("idcCardPassed", $data);
        }
    }

    /**
     * 绑卡
     */
    public function monitorUserBankCard() {
        $threshold = 80;
        $minute = 5;
        $data = $this->compareDay(DB_PREFIX.'user_bankcard', 'create_time', $minute);
        if(abs($data['percent']) >= $threshold) {
            $data['threshold'] = $threshold;
            self::addSms("bankCard", $data);
        }
    }

    /**
     * 充值
     */
    public function monitorUserPayment() {
        $threshold = 80;
        $minute = 5;
        $data = $this->compareDay(DB_PREFIX.'payment_notice', 'create_time', $minute);
        if(abs($data['percent']) >= $threshold) {
            $data['threshold'] = $threshold;
            self::addSms("payment", $data);
        }
    }

    /**
     * 投资
     */
    public function monitorUserDealLoad() {
        $threshold = 80;
        $minute = 5;
        $data = $this->compareDay(DB_PREFIX.'deal_load', 'create_time', $minute);
        if(abs($data['percent']) >= $threshold) {
            $data['threshold'] = $threshold;
            self::addSms("dealLoad", $data);
        }
    }

    /**
     * 和昨天比数据
     * @param string $table 表名
     * @param string $field 要比较的字段
     * @param int $minute 分钟--统计几分钟内的数据
     * @return percent 涨跌百分比
     */
    private function compareDay($table,$field,$minute) {
        $time_n_e = $this->time;                // 现在时间
        $time_n_b = $time_n_e - $minute * 60;   // 上一个时段时间
        $sameTermTime = $this->getSameTermTime($minute);
        $time_y_b = $sameTermTime['begin'];
        $time_y_e = $sameTermTime['end'];

        $query = $this->db->query("SELECT MAX(id) as max_id FROM " . $table);
        $max_id = $this->db->fetchRow($query);
        $id = $max_id['max_id'] - self::DELTA;

        $sql_y = "select count(*) as cn from " . $table . " where $field between $time_y_b and $time_y_e AND `id` > '{$id}'";
        $sql_now = "select count(*) as cn from " . $table . " where $field between $time_n_b and $time_n_e AND `id` > '{$id}'";
        $result_y = $this->db->query($sql_y);
        $result_now = $this->db->query($sql_now);
        $count_y = $this->db->fetchRow($result_y);
        $count_now = $this->db->fetchRow($result_now);
        $percent = $this->calcPercent($count_y['cn'], $count_now['cn']);

        return array(
            'count_yesterday' => $count_y['cn'],
            'count_now'=>$count_now['cn'],
            'percent' => $percent
        );
    }

    /**
     * 取得比较的同期时间 默认取上一天
     * 如果是周六、周日、 则和上周同时间比较
     * @param int $minute
     */
    private function getSameTermTime($minute) {
        // 0 周日 1 ,星期一  ,6 星期六
        $time = array(
            'begin'=>$this->time - $minute * 60 - 86400,
            'end' => $this->time - 86400
        );
        $week = date('w',$this->time);
        if(in_array($week, array(0,1,6))) {
            $time['begin'] = $this->time - (7*86400) - ($minute * 60);
            $time['end'] = $this->time - (7*86400);
        }
        return $time;
    }

    /**
     * 比较百分比 $todayNum > $yesterdayNum 正增长
     * @param string $todayNum 当前时段数量
     * @param string $yesterdayNum 昨天同时段数量
     * @return unknown|Ambigous <number, string>
     */
    private function calcPercent($yesterdayNum,$todayNum) {
        $numDiff = abs($todayNum - $yesterdayNum);
        if($todayNum > $yesterdayNum || $numDiff < 30) {
            return 0; // 业务增长较快，暂时不监控增加的量   相差太小忽略
        }
        $maxNum = max($todayNum,$yesterdayNum);
        return number_format($numDiff/$maxNum * 100);
    }

    private function addSms($key,$data) {
        $week = date('w',$this->time);
        $dayStr = in_array($week, array(0,1,6)) ? "上周" : "昨日";
        $msg = "超出阈值".$data['threshold']. $dayStr."同期:".$data['count_yesterday'].",今日:".$data['count_now'].",";
        self::$sms[$key] = $msg;
    }

    private function smsNotice() {
        if(empty(self::$sms)) return;
        $smsContent = "";
        foreach (self::$sms as $key=>$msg) {
            $smsContent.=$key.":".$msg;
        }
        $smsContent.="时戳:".$this->time;
        echo $smsContent."\n";
        foreach($this->smsUser as $userName=>$userMobile) {
            $res = \libs\sms\SmsServer::sendAlertSms($userMobile,$smsContent);
            echo json_encode($res);
        }
    }

    public function run() {
        global $argv;
        $this->time = time() - 8 * 3600;    // 线上时间比真实时间晚
        $this->monitorUserRegister();       // 注册
        $this->monitorUserIdcCardPassed();  //实名认证
        $this->monitorUserBankCard();       // 绑卡
        $this->monitorUserPayment();        // 充值
        $this->monitorUserDealLoad();       // 投标
        $this->smsNotice();
    }
}

echo "begin:".date('Y-m-d H:i:s')."\n";
$monitor = new UserMonitor();
$monitor->run();
echo "end:".date('Y-m-d H:i:s')."\n";
