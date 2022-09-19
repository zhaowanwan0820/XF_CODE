<?php

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Task\Models\TaskFail;
use NCFGroup\Task\Services\TaskService;

/**
 * 失败任务处理
 *
 */
//p2p product
//require  '/apps/product/nginx/htdocs/firstp2p/firstp2p/scripts/init.php';
//p2p dev
//require  '/home/dev/git/firstp2p/scripts/init.php';
//fund dev
//require '/home/dev/git/fundgate/backend/app/tasks/init4worker.php';
//fund product
require '/apps/product/nginx/htdocs/fundgate/backend/app/tasks/init4worker.php';

$eventType ='core\event\ContractSignEvent';
$dateTime = XDateTime::now()->addDay(-1);

$failTasks = TaskFail::getFailTaskByTypeAndDateTime($eventType, $dateTime);

if (empty($failTasks)) {
    echo "没有找出失败的任务\n";
}

$taskService = new TaskService();
foreach ($failTasks as $failTask) {
    $taskService->failTaskRun($failTask->id);
}
