<?php

use NCFGroup\Task\Services\TaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Events\TestEvent;

require '/home/dev/git/fundgate/backend/app/tasks/init4worker.php';
$i = 0;
while ($i < 100) {
    $mqSrc = new TaskService();
    $expectedTestEvent = new TestEvent(true, 3, 'jx');
    $expectedTaskId = $mqSrc->doBackground($expectedTestEvent, 10, Task::PRIORITY_HIGH);

    $i ++;
}
