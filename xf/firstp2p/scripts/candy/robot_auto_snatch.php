<?php

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\candy\CandySnatchService;
use core\service\candy\CandyUtilService;
use libs\utils\Logger;
use libs\db\Db;

//每次夺宝的比例
$expectAmountPercent = isset($argv[1]) ? floatval($argv[1]) : 0.01;
if ($expectAmountPercent > 0.1) {
    exit('每次夺宝不能超过10%');
}

//超过这个数的商品才自动夺宝
$codeTotalLimit = isset($argv[2]) ? intval($argv[2]) : 500;

//给某一期自动夺宝
$periodId = isset($argv[3]) ? intval($argv[3]) : 0;

//每次夺宝成功的概率
$snacthActionPossibility = 0.5;

$snatchservice = new CandySnatchService();
$periodInfo = Db::getInstance('candy')->getAll("SELECT * FROM snatch_period WHERE status=".CandySnatchservice::PERIOD_STATUS_PROCESS);
foreach ($periodInfo as $key => $value) {
    if (mt_rand(1, 100) > 20) {
        continue;
    }

    if ($value['code_total'] < $codeTotalLimit) {
        continue;
    }

    if ($periodId != $value['id'] && $periodId != 0) {
        continue;
    }

    $userInfo = CandyUtilService::getRandRobotUserInfo();

    $remainCode = $value['code_total'] - $value['code_used'];
    $range = ($value['code_total'] * $expectAmountPercent) * 2 - 1;
    $amount = $remainCode <= $range ? $remainCode : mt_rand(0, $range);
    if ($amount < 1) {
        $amount = 1;
    }

    try {
        $snatchservice->snatchAction($userInfo['id'], $value['id'], $amount);
    } catch(\Exception $e) {
        Logger::info("candySnatch robotSnatch fail. msg:" . $e->getMessage());
    }
}
