<?php
/**
 * 发送红包组给指定用户
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
require_once dirname(__FILE__).'/../../app/init.php';
use core\service\BonusService;
set_time_limit(0);

define('RAND_KEY_PREFIX', 'BONUS_XQL_');

$bonus_service = new BonusService();
$bonus_service->getSuperConfList();

$next_day = 86400;
$now_hour = date("H");
$now = time();
$active_time  = mktime(0, 0, 0) + $next_day;
$sql = sprintf('SELECT * FROM `firstp2p_bonus_super` WHERE `start_time` < %s AND `end_time` > %s AND `status` = 1', $active_time + 86400, $active_time);
$list = $GLOBALS['db']->get_slave()->getAll($sql);
foreach ($list as $item) {
    $frequency = $item['frequency'];
    list($hour_start, $hour_end) = explode("|", $item['hour_section']);
    $start_time = mktime($hour_start, 0, 0) + $next_day;
    $end_time   = mktime($item_end, 0, 0) + $next_day;
    $times = ceil(($hour_end - $hour_start) * 60 / $frequency);
    $frequency = $frequency * 60;//间隔的秒数
    for($i = 0 ; $i < $times; $i++) {
        $section_start = $start_time + $frequency * $i;
        $section_end   = $start_time + $frequency * ($i + 1);
        $key = RAND_KEY_PREFIX . $item['id'] . '_' . $section_start . '_' . $section_end;
        $timestamp = \SiteApp::init()->cache->get($key);
        if ($timestamp == false) {
            $timestamp = rand($section_start, $section_end - 1);
            SiteApp::init()->cache->set($key, $timestamp, 172800);
            echo "key=$key|value=", $timestamp, "|start=$section_start|end=$section_end|format_value=", date('Y-m-d H:i:s', $timestamp), "\n";
            continue;
        }
        echo "已经生成key=", $key, "|value=", "$timestamp|", "|start=$section_start|end=$section_end|format_value=", date('Y-m-d H:i:s', $timestamp), "\n";
    }
}

echo "done\n";

