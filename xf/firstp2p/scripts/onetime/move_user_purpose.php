<?php
/**
 * 将用户中的账户用途，转移到user_third_balance表中
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;
use \libs\utils\PaymentApi;
use \core\dao\EnterpriseModel;

$start = isset($argv[1]) ? intval($argv[1]) : 0;
$end = isset($argv[2]) ? intval($argv[2]) : 0;

if (empty($start) || empty($end)) {
    exit('input start and end');
}

$master = Db::getInstance('firstp2p');
$slave = Db::getInstance('firstp2p', 'slave');
for ($i = $start; $i <= $end; $i += 1000) {
    $s = microtime(true);
    $endId = min($end, $i + 1000 -1);

    $users = $slave->getAll("SELECT id, user_purpose FROM firstp2p_user WHERE id BETWEEN {$i} AND {$endId}");

    // 如果为空，跳过
    if (empty($users)) {
        usleep(100);
        continue;
    }

    foreach ($users as $item) {
        $account = $slave->getOne("SELECT id FROM firstp2p_user_third_balance WHERE user_id = {$item['id']}");
        if (empty($account)) {
            Logger::info(sprintf('move user no account:%s,%s', $item['id'], $item['user_purpose']));
            continue;
        }
        $sql = "UPDATE firstp2p_user_third_balance SET account_type = {$item['user_purpose']} WHERE user_id = {$item['id']}";
        $master->query($sql);
        if ($master->affected_rows() == 0) {
            Logger::info(sprintf('move user no update:%s,%s', $item['id'], $item['user_purpose']));
        }
    }

    Logger::info(sprintf('move users done. cost:%ss, startId:%s, endId:%s, count:%s', round(microtime(true) - $s, 3), $i, $endId, count($users)));
}
