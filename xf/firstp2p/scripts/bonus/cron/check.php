<?php
/**
 *-----------------------------------------------------------------------
 * 校验红包组表中数据与红包用户表数据是否一致
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//ini_set('display_errors', 1);
//error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once dirname(__FILE__).'/../../../app/init.php';

$count = 10000;
$count_sql = 'select count(*) from firstp2p_bonus_user';
$total_count = \core\dao\BonusModel::instance()->countBySql($count_sql, array(), true);

$pages = intval(ceil($total_count / $count));

for ($page = 0; $page < $pages; $page++) {
    $sql = sprintf('select user_id, by_get_count, by_used_count, till_send_bonus_id from firstp2p_bonus_user limit %s, %s', $page * $count, $count);
    $result = $GLOBALS['db']->get_slave()->getAll($sql);
    if (count($result) <= 0) {
       break;
    }
    foreach ($result as $row) {
        if ($row['till_send_bonus_id'] <= 0) {
            continue;
        }
        $max_group_id = $GLOBALS['db']->get_slave()->getOne('select max(group_id) as max_id from firstp2p_bonus where created_at < '.$row['till_send_bonus_id']);
        if ($max_group_id <= 0) {
            continue;
        }
        $send_info = $GLOBALS['db']->get_slave()->getRow('select sum(get_count) as get_count, sum(used_count) as used_count from firstp2p_bonus_group where user_id = '.$row['user_id'].' and id <= '.$max_group_id);
        if ($send_info['get_count'] != $row['by_get_count'] || $send_info['used_count'] != $row['by_used_count']) {
            echo sprintf("res=fail\tuid=%s\tug=%s\tgg=%s\tus=%s\tgs=%s\n", $row['user_id'], $row['by_get_count'], $send_info['get_count'], $row['by_used_count'], $send_info['used_count']);
        }
    }
}
exit("done\n");
