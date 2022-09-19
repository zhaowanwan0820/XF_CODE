<?php
/**
 * @desc 删除无效红包脚本
 * ----------------------------------------------------------------------------
 * 删除没有被领取并且已经过期的红包，这些红包不需要展示到页面，删除不会影响数据
 * ----------------------------------------------------------------------------
 * 1、获取无效的红包组，连表查询，数据通过从库查询，不会对主库造成影响
 * 2、删除按照无效的红包组进行删除
 * 3、脚本计划每天执行一次（确认无误后在放入定时任务）
 * ----------------------------------------------------------------------------
 * 定时脚本设置方式(0 0 1 * * /project path/scripts/bonus_delete.php)
 * 暂时先执行手动删除，确认没有问题之后在加入到定时任务中
 */
require_once dirname(__FILE__).'/../app/init.php';

set_time_limit(0);
ini_set('memory_limit','512M');

$num_success = $affect_row = 0;

$delete_limit = intval($argv[1]) > 0 ? intval($argv[1]) : 100000;

$delete_sql = 'DELETE FROM firstp2p_bonus WHERE status=0 AND group_id=%s LIMIT 5';
$sql = 'SELECT distinct(A.group_id) FROM firstp2p_bonus A INNER JOIN firstp2p_bonus_group B ON A.group_id = B.id WHERE B.expired_at < %s AND A.status=0 LIMIT %s';

$list = $GLOBALS['db']->get_slave()->getAll(sprintf($sql, time(), $delete_limit));
foreach ($list as $row) {
    $result = $GLOBALS['db']->query(sprintf($delete_sql, intval($row['group_id'])));
    if (!$result) {
        echo "ERROR:", sprintf($delete_sql, intval($row['group_id'])), "\n";
    }
    $affect_row += $GLOBALS['db']->affected_rows();
    $num_success++;
    if ($num_success % 10000 == 0) {
        usleep(10000);
    }
}

echo '共成功删除无效红包', $affect_row, "个\n";

