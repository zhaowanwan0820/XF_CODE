<?php
/**
 * 一次性脚本，修改用户的is_dflh 为0
 */
require_once dirname(__FILE__).'/../app/init.php';

error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', '2048M');

$begin = 0;
$end = 1000;
$effect_num = 0;
$max_user_id = 7595333;
while (true) {
    $sql = "UPDATE `firstp2p_user` SET is_dflh = 1 where id > {$begin} AND id <= {$end} ";
    $GLOBALS['db']->query($sql);
    $affected_rows = $GLOBALS['db']->affected_rows();
    if($begin > $max_user_id) {
        break;
    } else {
        $effect_num += $affected_rows;
        $begin += 1000;
        $end += 1000;
        usleep(100);
    }
}

echo 'exec success effect num : '.$effect_num;