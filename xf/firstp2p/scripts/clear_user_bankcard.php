<?php
/**
 * 清理用户绑卡记录，保证只有一条
 * author: weiwei12@ucfgroup.com
 */

require(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use libs\db\Db;

set_time_limit(0);
ini_set('memory_limit', '2048M');
$db = Db::getInstance('firstp2p', 'master');

if (!isset($argv[1])) {
    $content = file_get_contents('http://static.firstp2p.com/attachment/201803/28/16/9f12c1baab461f5ae8efd6d6275dc3f9/94ef07ac046f66d93d3c08a9d01f265b.csv');
    $userIds = explode("\n", $content);
} else {
    $userIds = explode(',', $argv[1]);
}

$count = count($userIds);
Logger::info(sprintf('start clear_user_bankcard, count: %s, userIds: %s', $count, json_encode($userIds)));

foreach ($userIds as $userId) {
    $userId = intval($userId);
    if (empty($userId)) {
        continue;
    }
    $sql = sprintf('SELECT * FROM firstp2p_user_bankcard WHERE user_id = %d ORDER BY update_time desc, id desc', $userId);
    $bankcards = $db->getAll($sql);
    if (count($bankcards) <= 1) {
        Logger::info(sprintf('not need to clear up bankcard, userId: %d', $userId));
        continue;
    }

    $needDelIds = [];
    foreach ($bankcards as $key => $val) {
        //跳过第一个
        if ($key == 0) {
            continue;
        }
        $needDelIds[] = $val['id'];
    }
    $delSql = sprintf('DELETE FROM firstp2p_user_bankcard WHERE user_id = %d and id in (%s)', $userId, implode(',', $needDelIds));
    $result = $db->query($delSql);
    if (!$result) {
        Logger::error(sprintf('clear_user_bankcard error, userId: %d, id: %d', $userId, $val['id']));
    }
    Logger::info(sprintf('clear_user_bankcard success, userId: %d', $userId));
}

Logger::info(sprintf('end clear_user_bankcard, count: %s', $count));



