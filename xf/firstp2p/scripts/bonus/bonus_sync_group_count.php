<?php
/**
 * 发送红包组给指定用户
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
require_once dirname(__FILE__).'/../../app/init.php';
use core\service\BonusService;
use core\dao\BonusGroupModel;
set_time_limit(0);

$startID = intval($argv[1]);

$page = 1;
$size = 1000;

$sql = "SELECT count(*) FROM `firstp2p_bonus_group` WHERE `id` >= {$startID}";
$count = $GLOBALS['db']->get_slave()->getOne($sql);
$totalPage = ceil($count / $size);

while (true) {
    $now = time();
    $start = ($page - 1) * $size;
    $sql = "SELECT * FROM `firstp2p_bonus_group` WHERE `id` >= {$startID} LIMIT {$start}, {$size}";
    $list = $GLOBALS['db']->get_slave()->getAll($sql);
    if (empty($list)) break;

    $gids = [];
    foreach ($list as $item) {
        $gids[] = $item['id'];
    }
    BonusGroupModel::instance()->updateCount($gids);
    $sqlGids = implode(',', $gids);
    $sql = "SELECT * FROM `firstp2p_bonus` WHERE `group_id` IN ({$sqlGids}) AND `status` IN (1,2)";
    $list = $GLOBALS['db']->get_slave()->getAll($sql);
    $countInfo = [];
    foreach ($list as $item) {
        $countInfo[$item['group_id']]['get']++;
        if ($item['status'] == 2) $countInfo[$item['group_id']]['used']++;
    }
    foreach ($countInfo as $gid => $count) {
        BonusGroupModel::instance()->setCount($gid, intval($count['used']), intval($count['get']));
    }
    echo "finished:{$page}/{$totalPage}\r";
    $page++;
    usleep(500*1000);
}

echo "\ndone\n";

