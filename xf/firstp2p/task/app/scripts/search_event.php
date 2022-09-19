<?php

use NCFGroup\Task\Models\TaskSuccess;
require '/home/dev/git/firstp2p/scripts/init.php';

$options = array(
    'eventType' => 'core\event\SendContractEvent',
    'startTime' => '2015-07-07 13:00',
    'endTime' => '2015-07-07 13:30',
);

$tasks = TaskSuccess::get4TaskWebSearch($options);
foreach($tasks as $task)
{
    if ($task->event->_op_log_id == '8394156') {
        echo 'ok';
    }
}
