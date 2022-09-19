<?php
/**
 * 发送红包组给指定用户
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;

set_time_limit(0);


$next_day = strtotime('+1 day');
define('RAND_KEY_PREFIX', 'BONUS_XQL_'.date('m_d_', $next_day));

//不在红包活动范围内则停止发送
$now = date('Y-m-d');
$date_range = app_conf('BONUS_XQL_RANGE_DATE');
if (!$date_range) {
    exit("无活动时间范围控制配置。\n");
}
list($date_start, $date_end) = explode('|', $date_range);
if($now < $date_start || $now > $date_end){
    exit("中奖红包活动已结束\n");
}

$hour_range = app_conf('BONUS_XQL_RANGE_HOURS');
if (!$hour_range) {
    exit("无活动时间范围控制配置。\n");
}
list($hour_start, $hour_end) = explode('|', $hour_range);

if(!is_numeric($hour_start) || !is_numeric($hour_end)){
    exit("中奖红包活动已结束.\n");
}

$times = app_conf('BONUS_XQL_TIMES');//超级红包产生的频率，每几个小时产生一次
if($times <= 0) exit;

$time_start = strtotime(date('Y-m-d ', $next_day).str_pad($hour_start, 2, "0", STR_PAD_LEFT).':00:00');
$time_end = strtotime(date('Y-m-d ', $next_day).str_pad($hour_end, 2, "0", STR_PAD_LEFT). ':00:00');

for ($i = 0; $i < 24; $i++) {
    $start = $time_start + 3600 * $i * $times;
    $end = $start + $times * 3600;
    if ($time_end <= $start) {
        break;
    }
    $key = RAND_KEY_PREFIX . ($hour_start + $i * $times) . '_' . (($hour_start + ($i + 1) * $times) < 24 ? ($hour_start + ($i + 1) * $times) : 24);
    $timestamp = SiteApp::init()->cache->get($key);
    if ($timestamp == false) {
        $timestamp = rand($start, $end - 1);
        SiteApp::init()->cache->set($key, $timestamp);
        echo "$key=", $timestamp, "\n";
        continue;
    }
    echo "已经生成", $key, "=", "$timestamp\n";

}

echo "done\n";

