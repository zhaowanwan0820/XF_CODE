<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：从16日0:00起至17日16:00期间过期的红包（本周三发送的
 * 连续两个月未投资老用户激活红包及本周四发送的连续三个月未投资老用户激
 * 活红包除外）进行等额补发，有效期24小时
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once dirname(__FILE__).'/../../app/init.php';

$num_success = 0;
$end_time = to_timespan(date('Y-m-d'));

$sql = "SELECT owner_uid, mobile, sum(money) AS total_money FROM `firstp2p_bonus` where id > 100000000 && expired_at between unix_timestamp('2015-07-16') and unix_timestamp('2015-07-17 16:00:00') && status = 1 && task_id not in (354,355,356,357,358,359,360,361,362,363,364,365,366,367,377,378,379,380,381,382,383,384,385,386,387,388,389) group by owner_uid, mobile order by owner_uid ASC";
$list = $GLOBALS['db']->get_slave()->getAll($sql);
$created_at = time();
$updated_at = $created_at + 86400;
foreach ($list as $user) {
    $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `mobile`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, "%s", %s, %s, %s, %s, %s)';
    $result = $GLOBALS['db']->query(sprintf($insert_sql, intval($user['owner_uid']), $user['mobile'], $user['total_money'], 1, 9, $created_at, $updated_at));
    if ($result) {
        echo $user['mobile'], "\n";
        $num_success++;
    }
}

echo '红包发送成功',$num_success,"个\n";
