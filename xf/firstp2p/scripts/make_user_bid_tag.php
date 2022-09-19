<?php
/**
 * 给所有投资用户添加投资次数tag
 */
require_once dirname(__FILE__).'/../app/init.php';
use core\service\UserTagService;

set_time_limit(0);
ini_set('memory_limit', '512M');

$sql = "SELECT `user_id`,COUNT(`id`) AS bid_count FROM `firstp2p_deal_load` GROUP BY `user_id` ORDER BY `user_id` ASC";
$list = $GLOBALS['db']->get_slave()->getAll($sql);

$tag_service = new UserTagService();
foreach($list as $user){
    $bid_tag = $user['bid_count'] > 1 ? 'BID_MORE' : 'BID_ONE';
    $res = $tag_service->addUserTagsByConstName($user['user_id'], $bid_tag);
    echo sprintf("%d|%d|%s|%s \n", $user['user_id'], $user['bid_count'], $bid_tag, $res ? 'success' : 'fail');
}

