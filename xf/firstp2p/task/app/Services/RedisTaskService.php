<?php
/**
 * Created by PhpStorm.
 * User: Dengyi
 * Date: 2015/12/8
 * Time: 20:31
 */

namespace NCFGroup\Task\Services;

use NCFGroup\Task\Events\AsyncEvent;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Task\Gearman\GearMan;
use NCFGroup\Task\Instrument\BigIntegerIdentityGenerator;
use NCFGroup\Task\Models\RedisTask;

class RedisTaskService {

    //所有的键值采用树形目录结构命名，第一级目录是数据结果如：hash/list/zset/set/string
    //第二级是名字，一般取有意义的名字
    //保存所有队列的名字
    const HASH_ALL_QUEUES = "/hash/queues";
    //保存所有的任务信息
    const HASH_ALL_TASK = "/hash/tasks";

    //为了兼容Gearman的worker工作方式
    const QUEUE_PREFIX = "/zset/queue/";
    //定时执行的任务
    const TIMED_QUEUE_PREFIX = "/zset/timed_queue/";
    //失败的任务
    const FAILED_QUEUE_PREFIX = "/zset/failed_queue/";
    //事务消息的队列
    const TRANSACTION_QUEUE = "/zset/transaction_queue";
    //保存统计的数据
    const STAT_TASKS = "/hash/stat/";

    const REDIS_ID_PREFIX = "Redis:";

    public static function saveQueue($queueName, $partition = 1) {
        return getDI()->get('taskRedis')->hset(self::HASH_ALL_QUEUES, $queueName, $partition);
    }

    public static function push(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME, $pri = Task::PRIORITY_NORMAL,
                                XDateTime $executeTime = null, $worker = WxGearManWorker::DOTASK_BASE, $paralleled = true, $status = null) {
        $idGenerator = new BigIntegerIdentityGenerator(getDI()->get('taskRedis'));
        $id = $idGenerator->generate(); //ID必须为整数，兼容

        if(empty($worker)) {
            $queueName = '';
        } else {
            $queueName = self::getQueueName($worker);
            self::saveQueue($queueName);
        }
        $task = new RedisTask($id, $event, $queueName, $executeTime, $maxTry, $pri, $paralleled, $status);
        $saveResult = self::saveMessage($task);
        if(!$saveResult) {
            return false;
        }
        $enqueueResult = self::enqueue($task, $task->status);
        if(!$enqueueResult) {
            return false;
        }
        \NCFGroup\Task\Instrument\Monitor::add("TASK_ADD_EVENT_TO_REDIS");
        return $id;
    }

    public static function getAllQueue() {
        return getDI()->get('taskRedis')->hkeys(self::HASH_ALL_QUEUES);
    }

    public static function saveMessage(RedisTask $task) {
        getDI()->get('taskLogger')->info("save task to redis, task_id = {$task->id}, task = " . serialize($task));
        return getDI()->get('taskRedis')->hset(self::HASH_ALL_TASK, $task->id, serialize($task));
    }

    public static function deleteMessage($taskId) {
        return getDI()->get('taskRedis')->hdel(self::HASH_ALL_TASK, $taskId);
    }

    public static function fetchMessage($taskId) {
        $task = getDI()->get('taskRedis')->hget(self::HASH_ALL_TASK, $taskId);
        return unserialize($task);
    }

    public static function enqueue(RedisTask $task, $status) {
        if($status == Task::STATUS_RUN_NOW) { //立即运行
            getDI()->get('taskLogger')->info("enqueue {$task->id} to " . self::QUEUE_PREFIX . $task->queueName);
            $result = getDI()->get('taskRedis')->zadd(self::QUEUE_PREFIX . $task->queueName, $task->executeTime, $task->id);
            //发送给GermanServer
            GearMan::getInstance()->doBackground($task->queueName, json_encode(array('id'=>$task->id, 'queue'=>$task->queueName)), $task->id);
            return $result;
        } else if($status == Task::STATUS_RUN_TIMED) { //定时执行
            getDI()->get('taskLogger')->info("enqueue {$task->id} to " . self::TIMED_QUEUE_PREFIX . $task->queueName);
            return getDI()->get('taskRedis')->zadd(self::TIMED_QUEUE_PREFIX . $task->queueName, $task->executeTime, $task->id);
        } else if($status == Task::STATUS_INVALID) { //无效
            getDI()->get('taskLogger')->info("enqueue {$task->id} to " . self::FAILED_QUEUE_PREFIX . $task->queueName);
            return getDI()->get('taskRedis')->zadd(self::FAILED_QUEUE_PREFIX . $task->queueName, $task->executeTime, $task->id);
        } else if($status == Task::STATUS_WAITING) { //Gearman+兼容，事务队列
            getDI()->get('taskLogger')->info("enqueue {$task->id} to " . self::TRANSACTION_QUEUE);
            return getDI()->get('taskRedis')->zadd(self::TRANSACTION_QUEUE, $task->executeTime, $task->id);
        }
        return 0;
    }

    public static function dequeue(RedisTask $task, $status) {
        $result = 0;
        if($status == Task::STATUS_RUN_NOW) {
            getDI()->get('taskLogger')->info("dequeue {$task->id} in " . self::QUEUE_PREFIX . $task->queueName);
            $result = getDI()->get('taskRedis')->zrem(self::QUEUE_PREFIX . $task->queueName, $task->id);
        } else if($status == Task::STATUS_RUN_TIMED) {
            getDI()->get('taskLogger')->info("dequeue {$task->id} in " . self::TIMED_QUEUE_PREFIX . $task->queueName);
            $result = getDI()->get('taskRedis')->zrem(self::TIMED_QUEUE_PREFIX . $task->queueName, $task->id);
        } else if($status == Task::STATUS_INVALID) {
            getDI()->get('taskLogger')->info("dequeue {$task->id} in " . self::FAILED_QUEUE_PREFIX . $task->queueName);
            $result = getDI()->get('taskRedis')->zrem(self::FAILED_QUEUE_PREFIX . $task->queueName, $task->id);
        } else if($status == Task::STATUS_WAITING) {//事务消息队列出列
            getDI()->get('taskLogger')->info("dequeue {$task->id} in " . self::TRANSACTION_QUEUE);
            $result = getDI()->get('taskRedis')->zrem(self::TRANSACTION_QUEUE, $task->id);
        }
        return $result;
    }

    public static function getQueueName($worker) {
        return getDI()->get('config')->taskGearman->appName . "_" . $worker;
    }

    public static function pop(RedisTask $task) {
        self::deleteMessage($task->id);
        return self::dequeue($task, TASK::STATUS_RUN_NOW);
    }

    public static function consume($id) {
        getDI()->get('taskLogger')->info("consume RedisTask:{$id}");
        $task = self::fetchMessage($id);
        if(!($task instanceof RedisTask)) {//找不到任务
            getDI()->get('taskLogger')->info("fetch {$id} message failed, it is not an instance of RedisTask");
            return;
        }
        getDI()->get('taskLogger')->info("RedisTask:{$id} is " . json_encode($task));
        //消费消息
        if($task->hasReachedMaxTry()) {
            getDI()->get('taskLogger')->info("consume {$task->maxTry} times, failed, id={$task->id}, eventType={$task->eventType}");
            self::enqueueFailQueue($task);
            return;
        }
        $task->incrementNowTryTimes();
        self::saveMessage($task);
        try{
            $isSuccess = $task->run();
            if($isSuccess || is_null($isSuccess)) {//消费成功删除记录
                getDI()->get('taskLogger')->info("consume successfully, id={$task->id}, eventType={$task->eventType}, event=" . serialize($task->event));
                $startTime = microtime(true);
                self::pop($task);
                $endTime = microtime(true);
                $costTime = round(($endTime - $startTime) * 1000);
                getDI()->get('taskLogger')->info("pop costTime={$costTime}ms");
//                getDI()->get('taskLogger')->info("pop result={$result}, id={$task->id}, eventType={$task->eventType}");
            } else {
                getDI()->get('taskLogger')->info("consume failed, id={$task->id}, eventType={$task->eventType}");
                self::dealWithFail($task);
            }
        } catch (\Exception $e) {
            getDI()->get('taskLogger')->info("consume Exception:" . $e->getMessage());
            $task->errorLog = $e->getMessage();
            self::dealWithFail($task);
        }
    }

    public static function dealWithFail(RedisTask $task) {
        if($task->hasReachedMaxTry()) {//消费失败
            getDI()->get('taskLogger')->info("consume {$task->maxTry} times, failed, id={$task->id}, eventType={$task->eventType}");
            self::enqueueFailQueue($task);
            return;
        }
        //进入重试队列
        self::enqueue($task, Task::STATUS_RUN_TIMED);
        //设置重试状态
        $task->setTimedStatus();
        //设置下次运行时间
        $task->setNextExecuteTime();
        //更新消息
        self::saveMessage($task);
        //从运行队列出列
        self::dequeue($task, Task::STATUS_RUN_NOW);
        return;
    }

    public static function findMissedMessages($queueName, $startTime, $endTime, $limit = 5000) {
        return getDI()->get('taskRedis')->zrangebyscore($queueName, $startTime, $endTime, array('limit' => array(0, $limit)));
    }

    public static function enqueueFailQueue(RedisTask $task) {
        //发送报警邮件
        $task->alert();
        //从运行队列出列
        self::dequeue($task, TASK::STATUS_RUN_NOW);
        //为了防止消耗内存过多，失败的消息直接删除，写入日志
        self::deleteMessage($task->id);
        getDI()->get('taskLogger')->info("RedisTask failed, id={$task->id}, eventType={$task->eventType}, event=" .serialize($task->event));
        //修改状态为无效
//        $task->setInvalidStatus();
        //更新消息
//        self::saveMessage($task);
        //进入失败的队列
//        return self::enqueue($task, TASK::STATUS_INVALID);
    }

    public static function registerRedisTask(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME,
                                             $pri = Task::PRIORITY_NORMAL, XDateTime $executeTime = null, $paralleled = true) {
        return RedisTaskService::push($event, $maxTry, $pri, $executeTime, '', $paralleled, Task::STATUS_WAITING);
    }

    public static function notifyRedisTask($taskId, $worker = WxGearManWorker::DOTASK_BASE, XDateTime $executeTime = null) {
        $task = RedisTaskService::fetchMessage($taskId);
        if(!($task instanceof RedisTask)) {
            getDI()->get('taskLogger')->info("notifyRedisTask: can not find {$taskId}");
            return false;
        }
        $task->queueName = self::getQueueName($worker);
        if($executeTime) {
            $task->status = Task::STATUS_RUN_TIMED;
            $task->executeTime = $executeTime->getTime();
        } else {
            $task->status = Task::STATUS_RUN_NOW;
            $task->executeTime = time();
        }
        self::saveMessage($task);
        //进运行或者定时队列
        $result = self::enqueue($task, $task->status);
        if(!$result) {
            return false;
        }
        //出事务队列
        self::dequeue($task, Task::STATUS_WAITING);
        return true;
    }

    public static function cancelRedisTask($taskId) {
        $task = RedisTaskService::fetchMessage($taskId);
        self::deleteMessage($taskId);
        self::dequeue($task, Task::STATUS_WAITING);
        return true;
    }

    public static function queryWaitingRedisTasks() {
        return self::findMissedMessages(self::TRANSACTION_QUEUE, 0, time());
    }
}