<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/8/6
 * Time: 18:36
 */

namespace NCFGroup\Task\Services;

use NCFGroup\Task\Models\RedisTask;
use NCFGroup\Task\Services\RedisTaskService;
use NCFGroup\Task\Services\TaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Events\AsyncEvent;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Common\Library\Date\XDateTime;

abstract class BaseTaskClient {

    /**
     * @param AsyncEvent $event
     * @param int $maxTry
     * @param string $pri
     * @param bool $paralleled
     * @return int | bool taskId
     * 如果想保证消息不会丢失，应该放在事务中调用这个函数
     */
    public final function register(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME, $pri = Task::PRIORITY_NORMAL, $paralleled = true) {
        $taskId = TaskService::registerTask($event, $maxTry, $pri, null, $paralleled, Task::STATUS_WAITING);
        if(!$taskId) {
            return false;
        }
        if(!$this->createLocalTaskRecord($taskId)) {
            return false;
        }
        return $taskId;
    }

    /**
     * @param $taskId
     * @param XDateTime $executeTime
     * @param string $worker
     * @return bool
     * 不需要放在事务中，也不需要关心它的返回值。
     */
    public final function notify($taskId, $worker = WxGearManWorker::DOTASK_BASE, XDateTime $executeTime = null) {
        $result = TaskService::notifyTask($taskId, $worker, $executeTime);
        //发送到worker成功后，删除业务库的业务执行记录，防止业务库的这张表过大。
        if($result) {
            $this->deleteLocalTaskRecord($taskId);
        }
        return $result;
    }

    /**
     * @param $taskId
     * @return bool
     * 通知Gearman取消执行任务。
     */
    public final function cancel($taskId) {
        $this->deleteLocalTaskRecord($taskId);
        return TaskService::cancelTask($taskId);
    }

    /**
     * 业务起CronJob周期性执行这个函数，补偿丢失的消息。crontab每隔5min执行一次。
     */
    public final function compensate() {
        $tasks = DbTaskService::queryWaitingDbTasks();
        foreach($tasks as $task) {
            $successFul = $this->queryLocalTaskRecord($task->id);
            if ($successFul) {
                if ($task->executeTime->before(XDateTime::now())) {
                    $queueName = $this->getQueueName($task->event);
                    $result = DbTaskService::notifyDbTask($task->id, $queueName);
                } else {
                    $task->status = Task::STATUS_RUN_TIMED;
                    $result = $task->save();
                }
                //放入处理成功的队列后，删除业务库的业务执行记录，防止业务库的这张表过大。
                if($result) {
                    $this->deleteLocalTaskRecord($task->id);
                }
            } else { // 没有找到记录，删除任务。
                if ($task->ctime->addDay(1)->after(XDateTime::now())) {
                    DbTaskService::cancelDbTask($task->id);
//                    $task->status = Task::STATUS_INVALID;
//                    $task->save();
                }
            }
        }
        //TODO: redis mode
        try {
            $tasks = RedisTaskService::queryWaitingRedisTasks();
            foreach($tasks as $taskId) {
                $successFul = $this->queryLocalTaskRecord($taskId);
                if($successFul) {
                    $redisTask = RedisTaskService::fetchMessage($taskId);
                    if($redisTask instanceof RedisTask) { //找到task
                        if($redisTask->executeTime > time()) { //立即执行的任务
                            $result = RedisTaskService::enqueue($redisTask, Task::STATUS_RUN_NOW);//进入立即执行的队列
                            if($result) {
                                RedisTaskService::dequeue($redisTask, Task::STATUS_WAITING);
                                RedisTaskService::deleteMessage($taskId);
                                $this->deleteLocalTaskRecord($taskId);
                            }
                        } else { //定时执行的任务。
                            $result = RedisTaskService::enqueue($redisTask, Task::STATUS_RUN_TIMED);//进入定时队列
                            if($result) {
                                RedisTaskService::dequeue($redisTask, Task::STATUS_WAITING);
                                RedisTaskService::deleteMessage($taskId);
                                $this->deleteLocalTaskRecord($taskId);
                            }
                        }
                    } else {//没有找到
                        //TODO:
//                    $this->deleteLocalTaskRecord($taskId);
                    }
                } else {
                    $redisTask = RedisTaskService::fetchMessage($taskId);
                    RedisTaskService::deleteMessage($taskId);
                    if($redisTask instanceof RedisTask) {
                        RedisTaskService::dequeue($redisTask, Task::STATUS_WAITING);
                    }
                }
            }
        } catch(\Exception $e){
            getDI()->get('taskLogger')->error("compensate error, error=" . $e->getTraceAsString());
        }
    }

    /**
     * @param $taskId
     * @return bool true | false
     * 创建任务在业务库本地的记录
     */
    abstract public function createLocalTaskRecord($taskId);

    /**
     * @param $taskId
     * @return bool true | false
     * 查询任务在业务库执行的情况
     */
    abstract public function queryLocalTaskRecord($taskId);

    /**
     * @param $taskId
     * @return mixed
     * 删除任务在业务库的记录。
     */
    abstract public function deleteLocalTaskRecord($taskId);

    /**
     * @param $eventType string
     * @return string
     * 根据不同的eventType确定队列名字；默认使用domq_base队列；
     * 业务可以覆盖该函数，提供
     */
    public function getQueueName(AsyncEvent $event) {
        return "domq";
    }
}
