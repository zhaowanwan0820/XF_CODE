<?php
require(dirname(__FILE__) . '/../app/init.php');
use NCFGroup\Task\Services\TaskService AS GTaskService;
ini_set('error_reporting', E_ERROR);
// 获取参数
$userId = $argv[1];
if (!$userId) {
    die("Need UserId \n");
}

// 获取该用户的投标金额和使用的邀请码
$sql = "SELECT user_id, short_alias, money FROM firstp2p_deal_load WHERE user_id = {$userId} ORDER BY id ASC";
$dealInfo = $GLOBALS['db']->getRow($sql);
if (empty($dealInfo)) {
    die("No Deal \n");
}

$event = new \core\event\SendBonusEvent($dealInfo['user_id'], $dealInfo['money'], $dealInfo['short_alias']);
$task_obj = new GTaskService();
$task_id = $task_obj->doBackground($event, 20, \NCFGroup\Task\Models\Task::PRIORITY_NORMAL, NULL, \NCFGroup\Task\Gearman\WxGearManWorker::DOTASK_BASE, false);
if(!$task_id){
    die("Add To Task Fail \n");
}
