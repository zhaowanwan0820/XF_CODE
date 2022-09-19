<?php
/**
 * 积分计算与分配
 * 每天0点跑一次，建议1点后重复执行1-2次，有幂等
 */
ini_set('display_errors', 'On'); error_reporting(E_ALL);
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\candy\CandyAccountService;
use core\service\candy\CandyActivityService;
use core\service\candy\CandyProduceService;
use libs\db\Db;
use libs\utils\logger;

$accountService = new CandyAccountService();
$activityService = new CandyActivityService();
$produceService = new CandyProduceService();

// 昨天时间
$start = isset($argv[1]) ? strtotime($argv[1]) : strtotime(date('Ymd')) - 86400;
$end = $start + 86400;

// 当天积分分配总数
$candyTotal = $produceService->calcCandyTotalByTime($start);

// 当天信力总数 (总信力池要乘一定系数)
$userActivityRows = $activityService->getAllUserActivity($start, $end);
$activityTotal = intval(array_sum($userActivityRows) * max(1, app_conf('CANDY_ACTIVITY_POOL_RATE')));

// 批次号做幂等
$batchNo = date('Ymd', $start);

// 任务开始
Logger::info("candy produce start. batch:{$batchNo}, start:{$start}, end:{$end}");
if ($produceService->isBatchProduceDone($batchNo)) {
    Logger::info("candy produce finish. has done. batch:{$batchNo}");
    echo "batchNo:{$batchNo} has done\n";
    exit();
}

$produceService->batchProduceStart($batchNo, count($userActivityRows), $activityTotal, $candyTotal);

foreach ($userActivityRows as $userId => $activity) {
    // 计算信宝
    $amount = $produceService->calcUserCandy($activity, $activityTotal, $candyTotal);
    if ($amount < 0.001) {
        Logger::info("candy produce user skipped. amount too less. userId:{$userId}, activity:{$activity}, amount:{$amount}");
        continue;
    }

    // 是否已发放
    if ($produceService->existsProduceUserLog($userId, $batchNo)) {
        Logger::info("candy produce user skipped. exists produce log. userId:{$userId}");
        continue;
    }

    // 发放信宝
    Db::getInstance('candy')->startTrans();
    $produceService->createProduceUserLog($userId, $batchNo, $activity, $amount);
    $accountService->changeAmount($userId, $amount, '信力结算信宝', "{$batchNo}-{$activity}-{$amount}");
    Db::getInstance('candy')->commit();

    Logger::info("candy produce user done. userId:{$userId}, activity:{$activity}, amount:{$amount}");
}

$produceService->batchProduceFinish($batchNo);
Logger::info("candy produce finish. batch:{$batchNo}");
echo "candy produce finish. batch:{$batchNo}\n";
