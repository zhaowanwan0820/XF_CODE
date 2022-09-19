<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：自平台成立以来至4月13日24点投资红包，为所有投资用户
 * 发送3元投资红包，红包有效期24小时红包发放实践下午3点以前.
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';
//require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

$money = 2;
$pages = 1000;
$num_success = 0;
//$count = intval($argv[1]);
//if ($count <= 0) {
$count = 5000;
//}
$end_time = to_timespan(date('Y-m-d'));
for($i = 0; $i < $pages; $i++) {

    $sql = "SELECT distinct(user_id) FROM `%s` WHERE user_id > %s && create_time <= %s ORDER BY user_id ASC LIMIT %s, %s";
    $sql = sprintf($sql, 'firstp2p_deal_load', intval($argv[1]), $end_time, $i * $count, $count);
    $list = $GLOBALS['db']->get_slave()->getAll($sql);
    if (count($list) <= 0) {
        break;
    }
    $created_at = time();
    $updated_at = $created_at + 86400;
    foreach ($list as $user) {
        $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, %s, %s, %s, %s, %s)';
        $result = $GLOBALS['db']->query(sprintf($insert_sql, intval($user['user_id']), $money, 1, 9, $created_at, $updated_at));
        if ($result) {
            $num_success++;
        }
    }
    usleep(100000);
}

echo '红包发送成功',$num_success,"个\n";
