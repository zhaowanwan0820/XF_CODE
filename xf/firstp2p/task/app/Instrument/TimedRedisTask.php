<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/12/10
 * Time: 18:59
 */

namespace NCFGroup\Task\Instrument;

use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Gearman\GearMan;
use NCFGroup\Task\Services\RedisTaskService;
use NCFGroup\Task\Models\RedisTask;

class TimedRedisTask {

    private static $defaultInterval = 300;
    private static $specialQueue = array(
        "p2p_domq_cpu" => 186400
    );

    public static function execute() {
        //获取所有的key
        $queues = RedisTaskService::getAllQueue();
        getDI()->get('taskLogger')->info("All queues: " . json_encode($queues));
        $nowTime = time();
        foreach($queues as $queue) {
            //找出待执行的定时任务
            $timedTasks = RedisTaskService::findMissedMessages(RedisTaskService::TIMED_QUEUE_PREFIX . $queue, 0, $nowTime);
            getDI()->get('taskLogger')->info("timedRedisTasks: " . json_encode($timedTasks));
            foreach($timedTasks as $taskId) {
                $task = RedisTaskService::fetchMessage($taskId);
                if($task instanceof RedisTask) {
                    //更新消息信息
                    $task->setRunNowStatus();
                    RedisTaskService::saveMessage($task);
                    //进入运行队列
                    RedisTaskService::enqueue($task, Task::STATUS_RUN_NOW);
                    //从定时队列删除任务
                    RedisTaskService::dequeue($task, Task::STATUS_RUN_TIMED);
                }
            }
            //找出很久没有执行的任务
            if(isset(self::$specialQueue[$queue])) {
                $endTime = $nowTime - self::$specialQueue[$queue];
            } else {
                $endTime = $nowTime - self::$defaultInterval;
            }
            $waitingTasks = RedisTaskService::findMissedMessages(RedisTaskService::QUEUE_PREFIX . $queue, 0, $endTime);
            getDI()->get('taskLogger')->info("waitingRedisTasks: " . json_encode($waitingTasks));
            foreach($waitingTasks as $taskId) {
                $task = RedisTaskService::fetchMessage($taskId);
                if($task instanceof RedisTask) {
                    GearMan::getInstance()->doBackground($task->queueName, json_encode(array('id'=>$task->id, 'queue'=>$task->queueName)), $task->id);
                }
            }
        }
    }
}