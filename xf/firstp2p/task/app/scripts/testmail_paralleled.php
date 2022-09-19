<?php

use NCFGroup\Task\Services\EmailService;
use NCFGroup\Task\Services\TaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Gearman\WxGearManWorker;

//p2p product
//require  '/apps/product/nginx/htdocs/firstp2p/firstp2p/scripts/init.php';
//p2p dev
//require  '/home/dev/git/firstp2p/scripts/init.php';
//fund dev
require '/home/dev/git/fundgate/backend/app/tasks/init4worker.php';
//fund product
//require '/apps/product/nginx/htdocs/fundgate/backend/app/tasks/init4worker.php';

$taskSvc = new TaskService();
$i = 0;
while ($i < 10) {
    $mailEvent = new NCFGroup\Task\Events\MailEvent4Test('jingxu@ucfgroup.com', 'jx', "jx{$i}傻逼");
    $taskSvc->doBackground($mailEvent, 20, Task::PRIORITY_NORMAL, null, WxGearManWorker::DOTASK_BASE, false);
    $i ++;
}
