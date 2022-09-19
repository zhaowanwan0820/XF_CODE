<?php
/**
 *-----------------------------------------------------------------------
 * 1、AR红包
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../../app/init.php';
require_once dirname(__FILE__).'/../../../system/libs/msgcenter.php';

$count = $argv[1];
$taskId = $argv[2];
$money = $argv[3];
$startCount = $argv[4];
$endCount = $argv[5];
$expiredAt = $argv[6];
if (!$money) {
    $money = 30;
}
if (!$startCount) {
    $startCount = 5;
}
if (!$endCount) {
    $endCount = 10;
}
$createdAt = time();
if (!$expiredAt) {
    $sendDay = 1;
    $expiredAt = $createdAt + $sendDay * 86400;
    $expiredAt = strtotime(date("Y-m-d", $expiredAt)) + (86400 - 1);
}

if ($expiredAt <= $createdAt) {
    exit("参数错误\n");
}

$url = app_conf('API_BONUS_SHARE_HOST').'/hongbao/GetHongbao?sn=';
for($i = 0; $i < $count; $i++) {
    $bonusCount = rand($startCount, $endCount);
    $insertSql = 'INSERT INTO `firstp2p_bonus_group` (`bonus_type_id`, `money`, `count`, `created_at`, `expired_at`, `task_id`) VALUES (%s, %s, %s, %s, %s, %s)';
    $insertSql = sprintf($insertSql, 1, $money, $bonusCount, $createdAt, $expiredAt, $taskId);
    $result = $GLOBALS['db']->query($insertSql);
    if ($result) {
        $id = $GLOBALS['db']->insert_id();
        echo "$id", " | ", $url.(new \core\service\BonusService())->encrypt($id, 'E'), "\n";
        $numSuccess++;
    }

}

echo '共生成',$numSuccess,"个红包组\n";
