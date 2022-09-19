<?php
namespace NCFGroup\Task\Services;

use NCFGroup\Task\Events\AsyncEvent;
use NCFGroup\Task\Services\RedisTaskService;
use NCFGroup\Task\Services\DbTaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Task\Models\TaskFail;
use Phalcon\Logger\Adapter\File as FileAdapter;

class TaskService
{
    /**
     * doBackground
     * 后台执行任务
     *
     * @param AsyncEvent $event 事件对象
     * @param mixed $maxTry 尝试次数
     * @param mixed $pri 优先级
     * @param mixed $executeTime 执行时间
     * @param mixed $worker workerkey
     * @param mixed $paralleled 是否可并发
     * @return string taskid
     */
    public function doBackground(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME, $pri = Task::PRIORITY_NORMAL, XDateTime $executeTime = null,
                                 $worker = WxGearManWorker::DOTASK_BASE, $paralleled = true)
    {/*{{{*/
        try {
            $result = DbTaskService::push($event, $maxTry, $pri, $executeTime, $worker, $paralleled);

            if($result) return $result;
        } catch(\Exception $e) {
            \NCFGroup\Task\Instrument\Monitor::add("TASK_ADD_EVENT_TO_DB_FAILED");
            $hostName = gethostname();
            $title = "save event to db failed, " . $hostName . ' --- ' . XDateTime::now()->toString();
            $message = "File: " . $e->getFile() . ", Line: " . $e->getLine() . ", Message: " .  $e->getMessage();
            WechatService::sendMessage($title, $message);
        }
        return RedisTaskService::push($event, $maxTry, $pri, $executeTime, $worker, $paralleled);
    }/*}}}*/

    public static function registerTask(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME,
                                        $pri = Task::PRIORITY_NORMAL, XDateTime $executeTime = null, $paralleled = true, $status = null)
    {
        try{
            $result = DbTaskService::registerDbTask($event, $maxTry, $pri, $executeTime, $paralleled, $status);
            if($result) return $result;
        } catch(\Exception $e) {
            $hostName = gethostname();
            $title = "register event failed, " . $hostName . ' --- ' . XDateTime::now()->toString();
            $message = "File: " . $e->getFile() . ", Line: " . $e->getLine() . ", Message: " .  $e->getMessage();
            WechatService::sendMessage($title, $message);
        }
        return RedisTaskService::registerRedisTask($event, $maxTry, $pri, $executeTime, $paralleled);
    }

    public static function notifyTask($taskId, $worker = WxGearManWorker::DOTASK_BASE, XDateTime $executeTime = null)
    {
        try {
            $result = DbTaskService::notifyDbTask($taskId, $worker, $executeTime);
            if($result) return $result;
        } catch(\Exception $e) {
            $hostName = gethostname();
            $title = "notify event failed, " . $hostName . ' --- ' . XDateTime::now()->toString();
            $message = "File: " . $e->getFile() . ", Line: " . $e->getLine() . ", Message: " .  $e->getMessage();
            WechatService::sendMessage($title, $message);
        }
        return RedisTaskService::notifyRedisTask($taskId, $worker, $executeTime);
    }

    public static function cancelTask($taskId) {
        try {
            $result = DbTaskService::cancelDbTask($taskId);
            if($result) return $result;
        } catch(\Exception $e) {
            $hostName = gethostname();
            $title = "cancel event failed, " . $hostName . ' --- ' . XDateTime::now()->toString();
            $message = "File: " . $e->getFile() . ", Line: " . $e->getLine() . ", Message: " .  $e->getMessage();
            WechatService::sendMessage($title, $message);
        }
        return RedisTaskService::cancelRedisTask($taskId);
    }

//    public static function queryWaitingTasks() {
//        if(TASK_WORK_MODE == 'db') {
//            return DbTaskService::queryWaitingDbTasks();
//        } else if(TASK_WORK_MODE == 'redis') {
//            return RedisTaskService::queryWaitingRedisTasks();
//        }
//    }

    public static function begin() {
        return Task::begin();
    }

    public static function registerTransactionTask(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME, $pri = Task::PRIORITY_NORMAL, XDateTime $executeTime = null,
                                                   $worker = WxGearManWorker::DOTASK_BASE, $paralleled = true) {
        return Task::registerTransactionTask($event, $maxTry, $pri, $executeTime, $worker, $paralleled);
    }

    public static function commit() {
        return Task::commit();
    }

    public static function rollback() {
        return Task::rollback();
    }

    public function failTaskRun($failTaskId, $toGearman = true)
    {/*{{{*/
        $failTask = TaskFail::find($failTaskId)->getFirst();
        $failTask->setRunning();
        $failTask->save();

        if ($toGearman) {
            $failTask->toGearman();
        } else {
            $failTask = TaskFail::find($failTaskId)->getFirst();
            $failTask->run();
        }
    }/*}}}*/

    /**
     * getRunningInfoByTaskIds
     *
     * 查看指执行失败任务执行情况, 执行中有哪些, 执行失败有哪些, 执行成功有哪些
     *
     * @access public
     * @return array
     */
    public function getRunningInfoByTaskIds(array $taskIds)
    {/*{{{*/
        if (empty($taskIds)) {
            return array();
        }

        return array(
            'runningCnt' => count(TaskFail::getRunningTaskIds($taskIds)),
            'failCnt' => count(TaskFail::getRunFailTaskIds($taskIds)),
        );
    }/*}}}*/
}
