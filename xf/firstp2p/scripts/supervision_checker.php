<?php
/**
 * 存管对账
 */
ini_set('memory_limit', '4096M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');
use core\service\SupervisionCheckService;

$date = date('Y-m-d', strtotime('-1 day')); //默认D-1对账

//指定日期
$opts = getopt ("d:" );
if (isset($opts['d'])) {
    $date = $opts['d'];
}

$supervisionCheckService = new SupervisionCheckService($date);
$supervisionCheckService->check();
