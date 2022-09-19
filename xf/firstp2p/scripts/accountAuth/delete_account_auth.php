<?php
/**
 * 删除账户授权
 * author: weiwei12@ucfgroup.com
 */

require(dirname(__FILE__) . '/../../app/init.php');
use libs\utils\Logger;
use libs\db\Db;
use core\dao\AccountAuthorizationModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');
$db = Db::getInstance('firstp2p_payment');
$dbP2P = Db::getInstance('firstp2p');

if (!isset($argv[1])) {
    $content = file_get_contents('http://static.firstp2p.com/attachment/201801/31/18/4938fe2f4ac67bcffcea3793a634384b/11489de5cb2584deb50a821b892f9157.csv');
    $userIds = explode("\n", $content);
} else {
    $userIds = explode(',', $argv[1]);
}

$count = count($userIds);
Logger::info(sprintf('start delete_account_auth, count: %s', $count));

$length = 1000;
$groupIds = [];
foreach ($userIds as $index => $userId) {
    $userId = intval($userId);
    if (empty($userId)) {
        continue;
    }
    //分组删除
    $groupIds[] = $userId;
    $index += 1;
    if ($index == $count || $index % $length == 0) {
        $sql = sprintf('DELETE FROM firstp2p_account_authorization WHERE user_id in (%s)', implode(',', $groupIds));
        $result = $db->query($sql);
        if (!$result) {
            Logger::error(sprintf('delete_account_auth error, userIds: %s', json_encode($groupIds)));
        }

        /*
        //删除未激活标签
        $sqlTag = sprintf('DELETE FROM `firstp2p_user_tag_relation` WHERE `uid` in (%s) AND `tag_id` = 1304', implode(',', $groupIds));
        $result2 = $dbP2P->query($sqlTag);
        if (!$result2) {
            Logger::error(sprintf('delete_account_auth tag error, userIds: %s', json_encode($groupIds)));
        }
        */

        $groupIds = [];
        sleep(0.1);
    }
}

Logger::info(sprintf('end delete_account_auth, count: %s', $count));



