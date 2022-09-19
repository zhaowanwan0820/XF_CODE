<?php
/**
 * 合并发送合同、放款、还款消息
 * 针对预约标的
 */
require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\ReserveDealLoansMsgEvent;
use core\event\ReserveDealRepayMsgEvent;
use core\event\ReserveDealContractMsgEvent;
use core\dao\DealLoadModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealContractModel;

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '2048M');

$startTime = mktime(-8, 0, 0, date('m'), date('d') - 1, date('Y'));
$endTime = mktime(-8, 0, -1, date('m'), date('d'), date('Y'));

Logger::info(sprintf("begin merge send loan repay sms, startTime: %d, endTime: %s", $startTime, $endTime));

//发送合同
$result = DealContractModel::instance()->getSignUserIdsForReserve($startTime + date('Z'), $endTime + date('Z')); //这里用的是北京时区
foreach ($result as $val) {
    $obj = new GTaskService();
    $event = new ReserveDealContractMsgEvent($val['user_id'], $startTime + date('Z'), $endTime + date('Z'));
    $obj->doBackground($event, 1);
}

//发送放款短信
$result = DealLoadModel::instance()->getReserveDealLoanUserIds($startTime, $endTime);
foreach ($result as $val) {
    $obj = new GTaskService();
    $event = new ReserveDealLoansMsgEvent($val['user_id'], $startTime, $endTime);
    $obj->doBackground($event, 1);
}

//发送回款短信
$result = DealLoanRepayModel::instance()->getReserveDealRepayUserIds($startTime, $endTime);
foreach ($result as $val) {
    $obj = new GTaskService();
    $event = new ReserveDealRepayMsgEvent($val['user_id'], $startTime, $endTime);
    $obj->doBackground($event, 1);
}

Logger::info(sprintf("end merge send loan repay sms, startTime: %d, endTime: %s", $startTime, $endTime));
