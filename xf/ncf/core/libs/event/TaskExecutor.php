<?php
namespace libs\event;

use libs\lock\LockFactory;
use libs\rpc\Rpc;
use core\service\TaskService;

class TaskExecutor 
{
    private static $lockKey = 'task_executor_082805';

    public static function launch()
    {

        self::getLock();

        $GLOBALS['db']->startTrans();
        try {
            $tasks = self::getTasks();
            foreach ($tasks as $task) {
                $task->setExecuting();
                $task->save();
            }
            $GLOBALS['db']->commit();
        } catch (Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::wLog('TaskExecutor 11 脚本失败'.print_r($e, true));
            return false;
        }

        self::releaseLock();

        foreach ($tasks as $task) {
            $task->run();
        }
    }

    private static function getLock()
    {
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock(self::$lockKey, 100, 10)) {
            die('获得锁失败了....');
        }
    }

    private static function releaseLock() 
    {
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        $lock->releaseLock(self::$lockKey);
    }

    private static function getTasks()
    {
        $taskService = new TaskService();
        return $taskService->getNowExecuteTasks();
    }
}
