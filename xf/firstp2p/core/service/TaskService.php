<?php
namespace core\service;
use libs\event\AsyncEvent;
use core\dao\TaskModel;
use libs\utils\XDateTime;

class TaskService extends BaseService
{
    public function regEvent(AsyncEvent $event, $maxTry = TaskModel::DEFAULT_MAX_RETRY_TIME, XDateTime $executeTime = null)
    {
        $task = TaskModel::create($event, $maxTry, $executeTime);
        $task->save();

        return $task;
    }

    /**
     * getNowExecuteTasks
     * 获得现在要执行的任务. 也就是执行状态是未执行,
     * 执行时间小于当前时间的任务
     *
     * @access public
     * @return array 符合条件的task
     */
    public function getNowExecuteTasks()
    {
        return TaskModel::instance()->getTaskBeforeExecuteTimeAndByExecuting(TaskModel::EXECUTING_NO, XDateTime::now());
    }
}
