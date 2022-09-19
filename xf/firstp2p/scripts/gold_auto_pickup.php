<?php
/**
 * 黄金运营方自动提现脚本-定时任务
 * 定时任务：每天13:00、15：30，执行一次脚本
 * /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/gold_auto_pickup.php
 * 
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Script;
use core\service\GoldChargeService;

Script::start();
// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep gold_auto_pickup.php | grep -v grep | grep -v {$pid} | grep -v vi | grep -v /bin/sh";
$handle = popen($cmd, 'r');
$str = fread($handle, 1024);
if ($str) {
    echo "黄金运营方自动提现进程已经启动\n";
    exit;
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$goldChargeService = new GoldChargeService();
$ret = $goldChargeService->goldAutoPickup();
Script::log($ret['respMsg']);

Script::end();