<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：给截止4月12日24点，为参加新手礼包1.0，2.0，3.0活动的
 * 所有邀请人和新投资人发放8元投资红包，有效期24小时。
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

$money = 8;
$created_at = time();
$updated_at = $created_at + 86400;
$pages = 500;
$num_success = 0;
$count = 1000;

for($i = 0; $i < $pages; $i++) {

    $sql = "SELECT distinct(owner_uid) FROM `%s` WHERE owner_uid > 0 && `type` IN (1, 2, 7, 8) && created_at <= unix_timestamp('2015-04-13') ORDER BY owner_uid ASC LIMIT %s, %s";
    $sql = sprintf($sql, 'firstp2p_bonus', $i * $count, $count);
    $list = $GLOBALS['db']->get_slave()->getAll($sql);
    if (count($list) <= 0) {
        break;
    }
    foreach ($list as $user) {
        $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, %s, %s, %s, %s, %s)';
        $result = $GLOBALS['db']->query(sprintf($insert_sql, intval($user['owner_uid']), $money, 1, 9, $created_at, $updated_at));
        if ($result) {
            $num_success++;
        }
    }
    if ($num_success % 10000 == 0) {
        sleep(1);
    }
}

echo '红包发送成功',$num_success,"个\n";
