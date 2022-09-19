<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包任务补发脚本，意外中断后手动执行该脚本
 *-----------------------------------------------------------------------.
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//ini_set('display_errors', 1);
//error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '512M');
require_once dirname(__FILE__).'/../../../app/init.php';

use NCFGroup\Task\Models\Task;
use core\event\Bonus\BonusTaskEvent;
use libs\lock\LockFactory;

$task_id = intval($argv[1]);

$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
$lock_key = 'bonus_task_bufa_script';
$lock->releaseLock($lock_key); //解锁
if (!$lock->getLock($lock_key, 3600)) {
    return false;
}

if ($task_id <= 0) { //获取当天的签到红包任务
    $task_sql = 'SELECT id FROM `firstp2p_bonus_task` where start_time between %s AND %s && status = 1 && is_effect = 1 && const_name = 1 LIMIT 1';
    $result = $GLOBALS['db']->getRow(sprintf($task_sql, (mktime(0, 0, 0) - 28800), (mktime(23, 59, 59) - 28800)));
    if ($result['id'] <= 0) {
        exit("获取红包任务失败!\n");
    }
    $task_id = intval($result['id']);
}

$task_sql = 'SELECT * FROM `firstp2p_bonus_task` where id = %s && start_time < %s && status = 1 && is_effect = 1';
$result = $GLOBALS['db']->getRow(sprintf($task_sql, $task_id, (get_gmtime() - 30 * 60)));
if ($result) {
    $event = new BonusTaskEvent($task_id, intval($argv[2]));
    try {
        $event->execute();
    } catch (Exception $e) {
        print_r($e);
    }
} else {
    exit("Task not exit.\n");
}

$lock->releaseLock($lock_key); //解锁
exit("Done.\n");
