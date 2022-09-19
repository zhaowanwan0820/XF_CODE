<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：从18日21:37起至19日10:00期间过期的红包（由于系统升级
 * 造成的红包过期，将按照等额红包进行补发
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

//$sql = "SELECT owner_uid, mobile, sum(money) AS total_money FROM `firstp2p_bonus` where id > 100000000 && expired_at between unix_timestamp('2015-07-16') and unix_timestamp('2015-07-17 16:00:00') && status = 1 && task_id not in (354,355,356,357,358,359,360,361,362,363,364,365,366,367,377,378,379,380,381,382,383,384,385,386,387,388,389) group by owner_uid, mobile order by owner_uid ASC";
//周五晚上21:37-周六早上10：00期间
$sql = "SELECT owner_uid, mobile, sum(money) AS total_money FROM `firstp2p_bonus` where id > 160000000 && owner_uid > 0 && status = 1 && expired_at between unix_timestamp('2015-09-18 21:37:00') and unix_timestamp('2015-09-19 10:00:00') group by owner_uid order by owner_uid ASC";

$list = $GLOBALS['db']->get_slave()->getAll($sql);
$created_at = time();
$updated_at = $created_at + 86400;
foreach ($list as $user) {
    $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `mobile`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, "%s", %s, %s, %s, %s, %s)';
    $result = $GLOBALS['db']->query(sprintf($insert_sql, intval($user['owner_uid']), $user['mobile'], $user['total_money'], 1, 9, $created_at, $updated_at));
    if ($result) {
        echo $user['owner_uid'], "\t", $user['mobile'], "\n";
        $num_success++;
    }
}

echo '红包发送成功',$num_success,"个\n";
