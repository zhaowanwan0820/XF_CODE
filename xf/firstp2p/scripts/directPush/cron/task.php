<?php
/**
 *-----------------------------------------------------------------------
 * 1、每分钟执行一次扫描直推任务表，扫描后执行
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
use libs\lock\LockFactory;
use core\service\DirectPushTaskService;

$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
$lock_key = 'direct_push_task';
if (!$lock->getLock($lock_key, 3600)) {
    exit();
}

$service = new DirectPushTaskService();
$service->addTasks();
$service->runTasks();

$lock->releaseLock($lock_key); //解锁
echo "done.\n";
exit();
