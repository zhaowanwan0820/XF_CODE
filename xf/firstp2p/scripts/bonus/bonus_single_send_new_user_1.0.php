<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：截止4月1日24点，为参加过新手礼包1.0计划的所有
 * 新投资用户发送投资红包，红包金额为10元，有效期48小时。红包发送时间：今天尽早发送～
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';

$money = 8;
$created_at = time();
$updated_at = $created_at + 172800;
$pages = 10;
$num_success = 0;
$count = 10000;

for($i = 0; $i < $pages; $i++) {

    $sql = "SELECT distinct(owner_uid) FROM `%s`  where type=1 and created_at between unix_timestamp('2015-03-05 22:00:00') and unix_timestamp('2015-04-04 00:00:00') ORDER BY owner_uid ASC LIMIT %s, %s";
    $sql = sprintf($sql, 'firstp2p_bonus',  $i * $count, $count);
    $list = $GLOBALS['db']->get_slave()->getAll($sql);
    if (count($list) <= 0) {
        break;
    }

    foreach ($list as $user) {
        $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, %s, %s, %s, %s, %s)';
        $result = $GLOBALS['db']->query(sprintf($insert_sql, $user['owner_uid'], $money, 1, 9, $created_at, $updated_at));
        if ($result) {
            $num_success++;
        }
    }
    sleep(1);
}

echo '红包发送成功',$num_success,"个\n";
