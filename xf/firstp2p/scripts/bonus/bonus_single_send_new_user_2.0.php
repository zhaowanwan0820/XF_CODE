<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：4月4-6日注册用户发送5元红包
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';

//$deadline = app_conf('BONUS_2_0_SEND_DEADLINE');
$deadline = \core\dao\BonusConfModel::get('BONUS_2_0_SEND_DEADLINE');
if (empty($deadline) || date('Y-m-d', strtotime('-1 day')) > $deadline) {
    exit("无效的发送日期。\n");
}

$money = 5;
$created_at = time();
$updated_at = $created_at + 172800;
$pages = 50;
$num_success = 0;
$count = 1000;

$day_start = to_timespan(date('Y-m-d', strtotime('-1 day')));
$day_end = $day_start + 86400 - 1;

for($i = 0; $i < $pages; $i++) {

    $sql = "SELECT id FROM `%s` where create_time between %s and %s ORDER BY id ASC LIMIT %s, %s";
    $sql = sprintf($sql, 'firstp2p_user',  $day_start, $day_end, $i * $count, $count);
    $list = $GLOBALS['db']->get_slave()->getAll($sql);
    if (count($list) <= 0) {
        break;
    }

    foreach ($list as $user) {
        $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, %s, %s, %s, %s, %s)';
        $result = $GLOBALS['db']->query(sprintf($insert_sql, $user['id'], $money, 1, 9, $created_at, $updated_at));
        if ($result) {
            $num_success++;
        }
    }
    sleep(1);
}

echo '红包发送成功',$num_success,"个\n";
