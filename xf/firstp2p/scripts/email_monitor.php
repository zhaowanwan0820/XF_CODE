<?php
/**
 * 邮件队列监控
 * 每15分钟运行一次，监控半小时内的邮件服务情况
 *
 * @date 2014-09-03
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

//crontab: */15 * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php email_monitor.php

require_once(dirname(__FILE__) . '/../app/init.php');
require_once(dirname(__FILE__) . '/../system/utils/logger.php');
require_once dirname(__FILE__) . '/../libs/common/functions.php';
require_once dirname(__FILE__) . '/../libs/common/dict.php';

use core\dao\DealMsgListModel;


class EmailMonitor {

    /**
     * 统计几分钟前开始的发送记录
     */
    private static $stat_minutes_start = 60;

    /**
     * 统计几分钟前结束的发送记录
     */
    private static $stat_minutes_end = 30;

    /**
     * 发送率阀值，低于则报警
     */
    private static $alarm_ratio_send = 70;


    /**
     * 送达率阀值，低于则报警
     */
    private static $alarm_ratio_received = 50;

    /**
     * 超过最小数报警，
     * @var int
     */
    private static $alarm_count_email_min_queue = 50;

    /**
     * 邮件队列积压数阀值，超过则报警
     */
    private static $alarm_count_email_queue = 100;

    /**
     * 监控执行
     */
    public function run() {
        set_time_limit(180);
        if (!empty($GLOBALS['sys_config']['EMAIL_ALARM_THRESHOLD'])){
            $alarm_threshold = $GLOBALS['sys_config']['EMAIL_ALARM_THRESHOLD'];
            $alarm_threshold_array = explode('|', $alarm_threshold);
            if(!empty($alarm_threshold_array)){
                self::$stat_minutes_start = empty($alarm_threshold_array[0]) ? self::$stat_minutes_start : $alarm_threshold_array[0];
                self::$stat_minutes_end = empty($alarm_threshold_array[1]) ? self::$stat_minutes_end : $alarm_threshold_array[1];
                self::$alarm_ratio_send = empty($alarm_threshold_array[2]) ? self::$alarm_ratio_send : $alarm_threshold_array[2];
                self::$alarm_ratio_received = empty($alarm_threshold_array[3]) ? self::$alarm_ratio_received : $alarm_threshold_array[3];
                self::$alarm_count_email_min_queue = empty($alarm_threshold_array[4]) ? self::$alarm_count_email_min_queue : $alarm_threshold_array[4];

            }
        }
        //$this->countQueue();
        $this->countSend();
    }

    /**
     * 邮件队列积压监控
     */
    private function countQueue() {
        $count = SiteApp::init()->prior_queue->len();
        $msg = '前'.self::$stat_minutes_start.'-'.self::$stat_minutes_end."分钟内队列积压:{$count}";
        $msg .= " ".date('d号H:i');
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $msg)));
        if ($count > self::$alarm_count_email_queue) {
            Logger::warn(implode(" | ", array(__CLASS__, __FUNCTION__, $msg)));
            $this->alarm($msg);
        }
    }

    /**
     * 下发送达率监控
     */
    private function countSend() {
        $dealMsgListModel = new DealMsgListModel();
        $time_end = get_gmtime() - (self::$stat_minutes_end * 60);
        $time_start = get_gmtime() - (self::$stat_minutes_start * 60);
        $condition_total = "`send_type`=1 and create_time>{$time_start} and create_time<={$time_end}";
        $condition_success_send = "{$condition_total} and `is_success`=1";
        $condition_success_received = "{$condition_total} and `is_received`=1";
        $count_total = $dealMsgListModel->countViaSlave($condition_total);
        $count_success_send = $dealMsgListModel->countViaSlave($condition_success_send);
        //$count_success_received = $dealMsgListModel->count($condition_success_received);
        $ratio_success_send = $count_total ? round($count_success_send * 100 / $count_total, 1) : 0;
        //$ratio_success_received = $count_total ? round($count_success_received * 100 / $count_total, 1) : 0;
        $msg = '前'.self::$stat_minutes_start.'-'.self::$stat_minutes_end."分钟内下发:{$ratio_success_send}%={$count_success_send}/{$count_total}";
        $msg .= " ".date('d号H:i');
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $msg)));
        if ($ratio_success_send < self::$alarm_ratio_send && $count_total > self::$alarm_count_email_min_queue) {
            Logger::warn(implode(" | ", array(__CLASS__, __FUNCTION__, $msg)));
            $this->alarm($msg);
        }
    }


    /**
     * 发送报警短信
     *
     * @param $msg 报警内容
     */
    private function alarm($msg) {
        $mobiles = dict::get('EMAIL_QUEUE_WARN');
        foreach ($mobiles as $mobile) {
            $rs = SiteApp::init()->sms->send($mobile, $msg, $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']['TPL_SMS_EMAIL_QUEUE_WARN'], 0);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $msg, json_encode($rs))));
        }
    }

}

$emailMonitor = new EmailMonitor();
$emailMonitor->run();


