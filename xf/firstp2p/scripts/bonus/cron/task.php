<?php
/**
 *-----------------------------------------------------------------------
 * 1、每分钟执行一次扫描红包任务表，扫描后执行
 *-----------------------------------------------------------------------
 * gearman中间层
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//ini_set('display_errors', 1);
//error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '512M');
require_once dirname(__FILE__).'/../../../app/init.php';

use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Common\Library\Date\XDateTime;
use core\event\Bonus\BonusTaskEvent;
use libs\lock\LockFactory;

$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
$lock_key = 'bonus_task_script';
if (!$lock->getLock($lock_key, 3600)) {
    return false;
}

$task_sql = 'SELECT * FROM `firstp2p_bonus_task` where start_time < %s && status = 0 && is_effect = 1';
$tasks = $GLOBALS['db']->getAll(sprintf($task_sql, get_gmtime()));
foreach ($tasks as $task) {
    $result = $GLOBALS['db']->query("UPDATE `firstp2p_bonus_task` SET status=1 WHERE id=" . intval($task['id']));
    //使用gearman队列发送
    if ($result) {
        $event = new BonusTaskEvent($task['id']);
        try {
            $event->execute();
        } catch(Exception $e) {
            \libs\sms\SmsServer::sendAlertSms(array('13601013563','18500132164'),json_encode($e));
            print_r($e);
        }
        /*$obj = new GTaskService();
        for ($i = 0; $i < $task['continue_times']; $i++) { //一次定时执行多次
            $runtime = $task['start_time'] + 28800 + $i * 86400;
            $event_res = $obj->doBackground($event, 1, TASK::PRIORITY_NORMAL, XDateTime::valueOfTime($runtime));
        }*/
    }
    echo "date=", date('Y-m-d H:i:s'), "\tresult=$result\tevent_res=$event_res\ttask=", json_encode($task), "\n";
}

$lock->releaseLock($lock_key); //解锁
