<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/1/7
 * Time: 10:23
 */

namespace NCFGroup\Task\Services;

use NCFGroup\Task\Events\AsyncEvent;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Common\Library\Date\XDateTime;
use Phalcon\Logger\Adapter\File as FileAdapter;

class DbTaskService {

    public static function push(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME, $pri = Task::PRIORITY_NORMAL,
                         XDateTime $executeTime = null, $worker = WxGearManWorker::DOTASK_BASE, $paralleled = true) {
        $startTime = round(microtime(true), 4);
        $task = self::trySave($event, $pri, $maxTry, $executeTime, $paralleled);
        $endTime = round(microtime(true), 4);
        $costTime =  round(($endTime - $startTime) * 1000);
        if($costTime > 500) {//插入DB时间大于500Ms，报警
            \NCFGroup\Task\Instrument\Monitor::add('TASK_TO_DB_SLOW');
        }
        getDI()->get('taskLogger')->info("costTime>>>>>toDb:{$costTime}ms, taskid:{$task->id}, eventType:{$task->eventType}");
        if (!$task) {
            return false;
        }
        $startTime = round(microtime(true), 4);
        if ($task && $task->isRunNow()) {
            if(!$task->toGearMan($worker)) {
                $task = Task::findFirst($task->id);
                $task->setTimed();
                $task->save();
                getDI()->get('taskLogger')->error("togearman 失败, taskid:{$task->id}, eventType:{$task->eventType}");
            }
        }
        $endTime = round(microtime(true), 4);
        $costTime =  round(($endTime - $startTime) * 1000);
        if ($costTime > 200) {
            \NCFGroup\Task\Instrument\Monitor::add('TASK_TO_GEARMAN_SLOW');
        }
        getDI()->get('taskLogger')->info("costTime>>>>toGearman:{$costTime}ms, taskid:{$task->id}, eventType:{$task->eventType}");
        \NCFGroup\Task\Instrument\Monitor::add("TASK_ADD_EVENT_TO_DB");
        return $task->id;
    }

    private static function trySave($event, $pri, $maxTry, $executeTime, $paralleled, $status = null)
    {/*{{{*/
        $saveSuccessFul = false;
        $saveTime = 0;

        $task = Task::createTask($event, $pri, $maxTry, $executeTime, $paralleled, $status);
        while (!$saveSuccessFul && $saveTime < 2) {
            try {
                $saveSuccessFul = $task->save();
                if (!$saveSuccessFul) {
                    getDI()->get('taskLogger')->error("添加任务save失败: eventType:".get_class($event));
                } else {
                    if(APP_ENV == 'dev' || APP_ENV == 'test') {
                        getDI()->get('taskLogger')->info("save successfully, taskId:{$task->id}, event_type={$task->eventType}, event=" . serialize($event));
                    }
                }
            } catch (\Exception $e) {
                $saveSuccessFul = false;
                getDI()->get('taskLogger')->error("添加任务出现异常: eventType:".get_class($event) . " errMsg: " . $e->getMessage());
                getDI()->get('taskDb')->connect();
            }
            if($saveSuccessFul) {
                return $task;
            }
            $saveTime ++;
            usleep(50000);
        }

        return false;
    }/*}}}*/

    public static function registerDbTask(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME,
                                          $pri = Task::PRIORITY_NORMAL, XDateTime $executeTime = null, $paralleled = true, $status = null) {
        $startTime = round(microtime(true), 1000);
        $task = self::trySave($event, $pri, $maxTry, $executeTime, $paralleled, $status);
        $endTime = round(microtime(true), 1000);
        $costTime =  round(($endTime - $startTime) * 1000);
        if($costTime > 50) {//插入DB时间大于50Ms，报警
            \NCFGroup\Task\Instrument\Monitor::add("TASK_TO_DB_COST_TIME_MORE_THAN_50MS");
        }
        getDI()->get('taskLogger')->info("costTime>>>>>toDb:{$costTime}ms, taskid:{$task->id}, eventType:{$task->eventType}");
        if (!$task) {
            return false;
        }
        \NCFGroup\Task\Instrument\Monitor::add("TASK_ADD_EVENT_TO_DB");
        return $task->id;
    }

    public static function notifyDbTask($taskId,$worker = WxGearManWorker::DOTASK_BASE, XDateTime $executeTime = null) {
        $task = Task::getWaitingTaskById($taskId);
        if(!($task instanceof Task)) {
            getDI()->get('taskLogger')->error("无法找到taskId:{$taskId}的任务");
            return false;
        }
        if($executeTime) {
            $task->updateStatus(Task::STATUS_WAITING, Task::STATUS_RUN_TIMED, $executeTime->toString());
            $task->status = Task::STATUS_RUN_TIMED;
        } else {
            $task->updateStatus(Task::STATUS_WAITING, Task::STATUS_RUN_NOW, XDateTime::now()->toString());
            $task->status = Task::STATUS_RUN_NOW;
        }
        if($task->isRunNow()) {
            getDI()->get('taskLogger')->info("toGearman in notify, taskId:{$taskId}, eventType:{$task->eventType}");
            $result = $task->toGearMan($worker);
            if (!$result) {
                getDI()->get('taskLogger')->error("send job to Gearman failed, taskId:{$taskId}, eventType:{$task->eventType}");
                return false;
            }
        }
        return true;
    }

    public static function cancelDbTask($taskId) {
        $task = Task::getWaitingTaskById($taskId);
        if(!empty($task)) {
            return $task->delete();
        }
        return false;
    }

    public static function queryWaitingDbTasks() {
        $appName = getDI()->get('config')->taskGearman->appName;
        return Task::getWaitingTasks($appName);
    }
}
