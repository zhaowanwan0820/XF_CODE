<?php
/**
 * 合并发送合同、放款、还款消息new
 * 针对预约标的
 */
require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\ReserveDealLoansMsgEvent;
use core\event\ReserveDealRepayMsgEvent;
use core\event\ReserveDealContractMsgEvent;
use core\dao\ReservationCacheModel;
use libs\utils\Script;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

$startTime = mktime(-8, 0, 0, date('m'), date('d') - 1, date('Y'));
$endTime = mktime(-8, 0, -1, date('m'), date('d'), date('Y'));

Script::start();

//发送合同
$result = ReservationCacheModel::instance()->getReserveDealContractUserIds($startTime + date('Z')); //这里用的是北京时区
foreach ($result as $userId) {
    $obj = new GTaskService();
    $event = new ReserveDealContractMsgEvent($userId, $startTime + date('Z'), $endTime + date('Z'));
    $obj->doBackground($event, 1);
}

//发送放款短信
$result = ReservationCacheModel::instance()->getReserveDealLoansUserIds($startTime + date('Z'));
foreach ($result as $userId) {
    $obj = new GTaskService();
    $event = new ReserveDealLoansMsgEvent($userId, $startTime, $endTime, true);
    $obj->doBackground($event, 1);
}

//发送回款短信
$result = ReservationCacheModel::instance()->getReserveDealRepayUserIds($startTime + date('Z'));
foreach ($result as $userId) {
    $obj = new GTaskService();
    $event = new ReserveDealRepayMsgEvent($userId, $startTime, $endTime, true);
    $obj->doBackground($event, 1);
}

Script::end();
