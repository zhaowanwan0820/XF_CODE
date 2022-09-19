<?php
namespace NCFGroup\Task\Models;

use NCFGroup\Common\Extensions\Base\ModelBase;
use NCFGroup\Task\Events\AsyncEvent;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Task\Gearman\GearMan;
use NCFGroup\Task\Instrument\DistributionLock;
use NCFGroup\Task\Instrument\TaskException;
use NCFGroup\Task\Services\EmailService;
use NCFGroup\Task\Events\EventInterceptor;
use NCFGroup\Task\Services\WechatService;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Logger\Adapter\File as FileAdapter;

class Task extends ModelBase
{
    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $status;

    /**
     *
     * @var integer
     */
    public $nowtry;

    /**
     *
     * @var integer
     */
    public $maxtry;

    /**
     *
     * @var string
     */
    public $event;

    /**
     *
     * @var string
     */
    public $event_type;

    /**
     *
     * @var string
     */
    public $priority;

    /**
     *
     * @var date
     */
    public $execute_time;

    /**
     *
     * @var date
     */
    public $ctime;

    /**
     *
     * @var date
     */
    public $mtime;

    /**
     *
     * @var date
     */
    public $start_execute_time;

    /**
     *
     * @var date
     */
    public $end_execute_time;

    /**
     *
     * @var int
     */
    public $paralleled;

    /**
     *
     * @var string
     */
    public $app_name;

    //END PROPERTY

    const STATUS_RUN_NOW = "run_now";
    const STATUS_RUN_TIMED = 'run_timed';
    const STATUS_WAITING = 'run_waiting';
    const STATUS_INVALID = 'invalid';

    const PRIORITY_NORMAL = 'NORMAL';
    const PRIORITY_LOW = 'low';
    const PRIORITY_HIGH = 'high';

    const DEFAULT_MAX_RETRY_TIME = 1;

    static $_transactionCount = 0;
    static $_transactionMessages = array();
    //记录事务的开始时间
    static $_transactionStartTime = 0;

    public function initialize()
    {
        //BEGIN DEFAULT_VALUE
        $this->nowtry = '0';
        $this->executeTime = '0000-00-00 00:00:00';
        $this->startExecuteTime = '0000-00-00 00:00:00';
        $this->endExecuteTime = '0000-00-00 00:00:00';
        $this->ctime = '0000-00-00 00:00:00';
        $this->mtime = '0000-00-00 00:00:00';
        //END DEFAULT_VALUE
        parent::initialize();
        $this->setConnectionService('taskDb');
    }

    public function columnMap()
    {/*{{{*/
        return array(
            'id' => 'id',
            'status' => 'status',
            'nowtry' => 'nowtry',
            'maxtry' => 'maxtry',
            'app_name' => 'appName',
            'event' => 'event',
            'event_type' => 'eventType',
            'priority' => 'priority',
            'execute_time' => 'executeTime',
            'start_execute_time' => 'startExecuteTime',
            'end_execute_time' => 'endExecuteTime',
            'ctime' => 'ctime',
            'mtime' => 'mtime',
            'paralleled' => 'paralleled',
        );
    }/*}}}*/

    public function getSource()
    {
        return "task";
    }

    public function afterFetch()
    {
        parent::afterFetch();
//        $this->ctime = XDateTime::valueOf($this->ctime);
        $this->executeTime = XDateTime::valueOf($this->executeTime);
        $this->event = igbinary_unserialize($this->event);
    }

    public function beforeSave()
    {
        parent::beforeSave();
        //此判斷是為了防止save兩次時, 第二次已經做過轉化的問題
        if ($this->executeTime instanceof XDateTime) {
            $this->executeTime = $this->executeTime->toString();
        }
        if ($this->startExecuteTime instanceof XDateTime) {
            $this->startExecuteTime = $this->startExecuteTime->toString();
        }
        if ($this->endExecuteTime instanceof XDateTime) {
            $this->endExecuteTime = $this->endExecuteTime->toString();
        }
        $this->event = igbinary_serialize($this->event);

        return true;
    }

    public static function createTask(AsyncEvent $event, $priority = self::PRIORITY_NORMAL, $maxtry = self::DEFAULT_MAX_RETRY_TIME,
                                      XDateTime $executeTime = null, $paralleled = true, $status  = null)
    {
        $task = new Task();
        $task->initialize();
        $task->event = $event;
        $task->eventType = get_class($event);
        $task->nowtry = 0;
        $task->maxtry = $maxtry;
        $task->paralleled = $paralleled;
        $task->appName = getDI()->get('config')->taskGearman->appName;

        if ($executeTime) {
            $task->executeTime = $executeTime;
            $task->status = self::STATUS_RUN_TIMED;
        } else {
            $task->executeTime = XDateTime::now();
            $task->status = self::STATUS_RUN_NOW;
        }
        if($status) {
            $task->status = $status;
        }
        $task->ctime = XDateTime::now();
        $task->priority = $priority;

        return $task;
    }

    public function isNeedRetry()
    {
        return false == $this->hasReachedMaxTry();
    }

    public function hasReachedMaxTry()
    {
        return $this->nowtry >= $this->maxtry;
    }

    public function execute()
    {
        if ($this->paralleled) {
            return $this->eventExecute();
        }

        $isLocked = DistributionLock::getInstance()->getLockWait($this->getLockKey());
        if(!$isLocked) {
            return false;
        }
        $successFul = $this->eventExecute();
        DistributionLock::getInstance()->releaseLock($this->getLockKey());

        return $successFul;
    }

    private function eventExecute()
    {/*{{{*/
        if(!property_exists($this->event, 'task_event_id')) {
            $this->event->task_event_id = $this->id;
        }

        try {
            if ($this->event instanceof EventInterceptor) {
                $this->event->before();
            }

            try {
                if (empty($this->event) || !method_exists($this->event, "execute")) {
                    $hostName = gethostname();
                    getDI()->get('taskLogger')->error(__FUNCTION__." event empty hostname:{$hostName}, eventType:{$this->eventType}, taskid:{$this->id}");
                    $title = "event unserialize failed, " . $hostName . ' --- ' . XDateTime::now()->toString();
                    $message = "taskId = {$this->id}, eventType = {$this->eventType}";
                    WechatService::sendMessage($title, $message);
                    return false;
                }
                $successFul = $this->event->execute();
            } catch (\Exception $e) {
                if ($this->event instanceof EventInterceptor) {
                    $this->event->after();
                }
                throw $e;
            }
            if ($this->event instanceof EventInterceptor) {
                $this->event->after();
            }
        } catch (\Exception $e) {
            throw new \Exception('event_exception: '. "taskId=" . $this->id . "\n, event=" . serialize($this->event) ."\n, errorMsg=" . $e->getMessage() . "\n, errorFile=" . $e->getFile() . "\n, errorLine=" . $e->getLine() . "\n, errorTrace=" . $e->getTraceAsString());
        }

        return $successFul;
    }/*}}}*/

    private function getLockKey()
    {
        return "Task::".$this->event_type;
    }

    public function incrementTry()
    {
        ++$this->nowtry;
    }

    /**
     * run 执行
     *
     * @access public
     * @return void
     */
    public function run()
    {
        if(APP_ENV == 'dev' || APP_ENV == 'test') {
            getDI()->get('taskLogger')->info("task {$this->id} is running now, nowTry={$this->nowtry}, event_type={$this->eventType}, event=" . serialize($this->event));
        } else {
            getDI()->get('taskLogger')->info("task {$this->id} is running now, nowTry={$this->nowtry}, event_type={$this->eventType}.");
        }
        try {
            $this->startExecuteTime = XDateTime::now();
            $successFul = $this->execute();

            getDI()->get('taskDb')->connect();
            $this->endExecuteTime = XDateTime::now();
            if ($successFul || $successFul === null) {
                //如果返回为null, 视为成功并记录日志 说明此task没有返回值, 为了防止没有返回值的task重复执行
                if ($successFul === null) {
                    $eventClass = get_class($this->event);
                    $errorContent = "\ntask执行异常:{$eventClass}返回值为NULL, 没有返回值?? time:".XDateTime::now()->toString()."\n";
                    trigger_error($errorContent, E_USER_WARNING);
                }

                $this->dealWithSucc();
            } else {
                $this->dealWithFail();
            }
        } catch (\Exception $exception) {
            $this->dealWithFail($exception->getTraceAsString());
        }

        $this->tryAlert4LongRunTime();
    }

    private function tryAlert4LongRunTime()
    {/*{{{*/
        if (XDateTime::secondDiff($this->getStartExecuteTime(), $this->getEndExecuteTime()) > 300) {
            $hostName = gethostname();
            $title = "[$hostName]异步任务[{$this->eventType}]执行超时";
            $content = "异步任务[$this->eventType], id[$this->id], 执行超过了5分钟, 可能会重复执行, 警告一下";

            try {
                $mailSvc = new EmailService();
                $mailSvc->sendSync($this->getAlertMails(), $title, $content);
            } catch (\Exception $e) {
                getDI()->get('taskLogger')->error(__FUNCTION__." 发邮件异常 hostname:{$hostName}, eventType:{$this->eventType}, taskid:{$this->id}");
            }
        }
    }/*}}}*/

    private function getStartExecuteTime()
    {/*{{{*/
        if ($this->startExecuteTime instanceof XDateTime) {
            return $this->startExecuteTime;
        }

        return XDateTime::valueOf($this->startExecuteTime);
    }/*}}}*/

    private function getEndExecuteTime()
    {/*{{{*/
        if ($this->endExecuteTime instanceof XDateTime) {
            return $this->endExecuteTime;
        }

        return XDateTime::valueOf($this->endExecuteTime);
    }/*}}}*/

    /**
     * dealWithSucc 处理成功的任务
     *
     * @access private
     * @return void
     */
    private function dealWithSucc()
    {/*{{{*/
        \NCFGroup\Task\Instrument\Monitor::add("TASK_CONSUME_SUCCESS");
        $hostName = gethostname();
        $errMsg = __FUNCTION__." taskid:{$this->id} hostnamne:{$hostName}, tasktype:{$this->eventType}";

        try {
            if (!$this->delete()) {
                getDI()->get('taskLogger')->error($errMsg." delete");
                throw new \Exception($errMsg." delete");
            }
        } catch (\Exception $e) {
            getDI()->get('taskLogger')->error($errMsg." commit");
            $mailSvc = new EmailService();
            $mailSvc->sendSync($this->getAlertMails(), 'dealWithSucc失败', $errMsg);
        }

        $eventStr = serialize($this->event);
        getDI()->get('taskLogger')->info(__FUNCTION__." hostname:{$hostName}, eventType:{$this->eventType}, taskid:{$this->id}, eventStr:{$eventStr}");
    }/*}}}*/

    /**
     * dealWithFail 处理失败的任务
     *
     * @access private
     * @return void
     */
    private function dealWithFail($errorLog = '')
    {
        if ($this->isNeedRetry()) {
            $this->setTimed();
            $this->setNextExecuteTime();
            if (!$this->save()) {
                $hostName = gethostname();
                $errMsg = __FUNCTION__." taskid:{$this->id} hostnamne:{$hostName}, tasktype:{$this->eventType}";
                getDI()->get('taskLogger')->error($errMsg);
            }
        } else {
            $this->toFailTask($errorLog);
        }
    }

    public function setTimed()
    {
        $this->status = self::STATUS_RUN_TIMED;
    }

    public function setNextExecuteTime() {
        if(is_string($this->ctime)) {
            $this->executeTime = XDateTime::valueOf($this->ctime)->addMinute(pow(2, $this->nowtry));
        } else {
            $this->executeTime = $this->ctime->addMinute(pow(2, $this->nowtry));
        }
    }

    public function setRunNow()
    {
        $this->status = self::STATUS_RUN_NOW;
    }

    public function markExecuteTime(XDateTime $executeTime)
    {
        $this->executeTime = $executeTime;
    }

    public function toFailTask($errorLog = '')
    {
        \NCFGroup\Task\Instrument\Monitor::add("TASK_CONSUME_FAIL");
        $this->alert($errorLog);

        $di = \Phalcon\DI::getDefault();
        $di->getTaskDb()->begin();

        $hostName = gethostname();
        $errMsg = __FUNCTION__." taskid:{$this->id} hostnamne:{$hostName}, tasktype:{$this->eventType}";

        try {
            $failTask = TaskFail::createTaskFail($this, $errorLog);
            if (!$failTask->save()) {
                getDI()->get('taskLogger')->error($errMsg." save failTask");
                throw new \Exception($errMsg." save failTask");
            }
            if (!$this->delete()) {
                getDI()->get('taskLogger')->error($errMsg. " delete fail");
                throw new \Exception($errMsg. " delete fail");
            }
            $di->getTaskDb()->commit();
        } catch (\Exception $e) {
            $di->getTaskDb()->rollback();
            getDI()->get('taskLogger')->error($errMsg." commit");
            $mailSvc = new EmailService();
            $mailSvc->sendSync($this->getAlertMails(), 'toFailTask失败', $errMsg);
        }
    }

    private function alert($errorLog = '')
    {
        $hostName = gethostname();
        $title = "失败任务:{$this->eventType}";
        $content = "失败任务：[{$this->eventType}][{$hostName}], 失败原因: [{$errorLog}]";

        try {
            //防止大量发邮件, 100秒内发一次
            $mailSvc = new EmailService();
            $mailSvc->sendSync($this->getAlertMails(), $title, $content);
        } catch (\Exception $e) {
            getDI()->get('taskLogger')->error(__FUNCTION__." 发邮件异常 hostname:{$hostName}, eventType:{$this->eventType}, taskid:{$this->id}");
        }
    }

    private function getAlertMails()
    {
        $devLocalMails = array();
        if (APP_ENV == 'dev') {
            preg_match('/(?<shorthostname>\w+)\./', gethostname(), $matches);
            $short_host_name = $matches['shorthostname'];
            $devLocalMails = array("{$short_host_name}@ucfgroup.com");
        }

        if (empty($this->event) || !method_exists($this->event, 'alertMails')) {
            return array();
        }

        return (array)$this->event->alertMails();
    }

    public function delete()
    {
        return parent::delete();
    }

    private function getDoMethod4Gearman()
    {
        if ($this->priority == self::PRIORITY_LOW) {
            return 'doLowBackground';
        }

        if ($this->priority == self::PRIORITY_NORMAL) {
            return 'doBackground';
        }

        if ($this->priority == self::PRIORITY_HIGH) {
            return 'doHighBackground';
        }

        return 'doBackground';
    }

    public function toGearMan($worker = WxGearManWorker::DOTASK_BASE)
    {
        $hostName = gethostname();
        getDI()->get('taskLogger')->info(__FUNCTION__." first hostname:{$hostName}, eventType:{$this->eventType}, taskid:{$this->id}");
        $doMethod = $this->getDoMethod4Gearman();

        $startTime = round(microtime(true), 1000);
        $job = GearMan::getInstance()->$doMethod($this->getPrefixedWoker($worker), $this->id, $this->id);
        $endTime = round(microtime(true), 1000);
        $costTime = round(($endTime - $startTime) * 1000);
        getDI()->get('taskLogger')->info("costTime>>>>doBackground:{$costTime}ms, taskid:{$this->id}, eventType:{$this->eventType}");

        $jobInfo = json_encode(Gearman::getInstance()->jobStatus($job));

        $returnCode = Gearman::getInstance()->returnCode();

        getDI()->get('taskLogger')->info(__FUNCTION__." second hostname:{$hostName}, eventType:{$this->eventType}, taskid:{$this->id}, jobinfo:{$jobInfo}, returnCode:{$returnCode}");

        if (Gearman::getInstance()->returnCode() != GEARMAN_SUCCESS) {
            return false;
        }
        return true;
    }

    private function getPrefixedWoker($worker)
    {
        return $this->appName.'_'.$worker;
    }

    public function isRunTimed()
    {
        return $this->status == self::STATUS_RUN_TIMED;
    }

    public function isRunNow()
    {
        return $this->status == self::STATUS_RUN_NOW;
    }

    public function isRunWaiting() {
        return $this->status == self::STATUS_WAITING;
    }

    public static function getTimedNeedRunTaskList()
    {
        return self::find(array(
            "status = :timed: and executeTime <= :now:",
            'limit' => 5000,
            "bind" => array(
                'timed' => self::STATUS_RUN_TIMED,
                'now' => XDateTime::now()->toString(),
            ),
        ));
    }

    public static function getMissTaskList()
    {
        $notInEventTypeStr = "'".implode("','",array(
                'core\\\\event\\\\ContractSignEvent',
            ))."'";

        return self::find(array(
            "status = :now: and executeTime <= :time: and eventType not in ({$notInEventTypeStr})",
            'limit' => 5000,
            "bind" => array(
                'now' => self::STATUS_RUN_NOW,
                'time' => XDateTime::now()->addMinute(-3)->toString(),
            ),
        ));
    }

    public static function getRunNowTaskCnt(XDateTime $executeTime)
    {
        $notInEventTypeStr = "'".implode("','",array(
                'core\\\\event\\\\ContractSignEvent',
            ))."'";

        $sql = "SELECT\n".
            "   count(*)\n".
            "FROM\n".
            "   task t\n".
            "WHERE\n".
            "   t.`status` = :now\n".
            "AND t.execute_time < :executeTime and event_type not in ({$notInEventTypeStr})";

        $binds = array(
            'now' => self::STATUS_RUN_NOW,
            'executeTime' => $executeTime->toString(),
        );

        return getDI()->getTaskDb()->query($sql, $binds)->fetchAll()[0][0];
    }

    public static function get4TaskWebSearch(array $options = array())
    {
        $bind = array();
        $where = '1 = 1';
        if (isset($options['eventType'])) {
            $where .= ' and eventType = :eventType:';
            $bind['eventType'] = $options['eventType'];
        }
        $limit = 100;
        if (isset($options['limit'])) {
            $limit = $options['limit'];
        }
        return self::find(array(
            $where,
            'bind' => $bind,
            'order' => 'id desc',
            'limit' => $limit,
        ));
    }

    public function getRunDuration()
    {
        $diffInfo = XDateTime::getDiffInfo($this->ctime, XDateTime::now());
        return $diffInfo['hour'].'时'.$diffInfo['min'].'分'.$diffInfo['sec'].'秒';
    }

    /**
     * @param $appName
     * @return mixed
     */
    public static function getWaitingTasks($appName) {
        return Task::find(
            array(
                "conditions"    =>  "appName = :appName: AND status = :waiting:",
                "bind"  =>  array(
                    'appName'   =>  $appName,
                    'waiting'   =>  Task::STATUS_WAITING,
                ),
                "order"  => "id asc",
                "limit" =>  5000,
            )
        );
    }

    public static function getWaitingTaskById($taskId) {
        return Task::findFirst(
            array(
                "id = :taskId: and status = :waiting:",
                "bind" => array(
                    'taskId'    =>  $taskId,
                    'waiting'   =>  Task::STATUS_WAITING,
                ),
            )
        );
    }

    public function updateStatus($oldStatus, $newStatus, $executeTime) {
        return  getDI()->getTaskDb()->update(
            $this->getSource(),
            array(
                'status',
                'execute_time',
            ),
            array(
                $newStatus,
                $executeTime,
            ),
            array(
                'conditions'    =>  "id = ? and status = ?",
                'bind'  =>  array($this->id, $oldStatus),
            )
        );
    }

    public static function consume($taskId) {
        $task = Task::findFirst($taskId);
        if (!($task instanceof Task)) {
            getDI()->get('taskLogger')->info("domqBase consume. worker not find taskId:{$taskId}");
            return;
        }
        getDI()->get('taskLogger')->info("domqBase consume. worker receive taskId:{$taskId}, retryNum:{$task->nowtry}, type:{$task->eventType}");

        if ($task->hasReachedMaxTry()) {
            getDI()->get('taskLogger')->info("domqBase consume. worker is sent to fail task");
            $task->toFailTask();
        } else {
            $task->incrementTry();
            $task->save();

            $task = Task::findFirst($taskId);
            if($task instanceof Task) {
                $task->run();
            } else {
                getDI()->get('taskLogger')->info("domqBase consume. TypeError, task is not a type of NCFGroup\\Task\\Models\\Task. " . var_export($task, true));
            }
        }
    }

    public static function begin() {
        if(self::$_transactionCount++) {
            return true;
        }
        $di = \Phalcon\DI::getDefault();
        $di->getTaskDb()->begin();
        self::$_transactionStartTime = microtime(true);
        return true;
    }

    public static function registerTransactionTask(AsyncEvent $event, $maxTry = Task::DEFAULT_MAX_RETRY_TIME, $pri = Task::PRIORITY_NORMAL, XDateTime $executeTime = null,
                                                   $worker = WxGearManWorker::DOTASK_BASE, $paralleled = true) {
        //没有事务，直接返回。
        if(!self::$_transactionCount) {
            return false;
        }

        $task = Task::createTask($event, $pri, $maxTry, $executeTime, $paralleled);
        $saveResult = $task->save();
        if($saveResult == false) {
            throw new TaskException("save task to db failed", TaskException::SAVE_FAILURE);
        }
        if($task->status == Task::STATUS_RUN_NOW) {// 立即执行
            self::$_transactionMessages[$worker][] = $task;
        }
        \NCFGroup\Task\Instrument\Monitor::add("TASK_ADD_EVENT_TO_DB");
        return $task->id;
    }

    public static function commit() {
        //没有事务，直接返回
        if(!self::$_transactionCount) {
            //清空待提交消息列表
            self::$_transactionMessages = array();
            return false;
        }
        self::$_transactionCount--;
        //最后一个提交
        if(!self::$_transactionCount) {
            $di = \Phalcon\DI::getDefault();
            $commitResult = $di->getTaskDb()->commit();
            if(!$commitResult) {
                throw new TaskException("commit failure", TaskException::COMMIT_FAILURE);
            }
            self::sendTransactionTasksToGearman();
            $costTime = round((microtime(true) - self::$_transactionStartTime) * 1000);

            //记录花费时间
            getDI()->get('taskLogger')->info("costTime>>>>commit transaction message:{$costTime}ms");
        }
        return true;
    }

    public static function sendTransactionTasksToGearman() {
        foreach(self::$_transactionMessages as $worker => $taskArray) {
            foreach($taskArray as $task) {
                $task->toGearman($worker);
            }
        }
    }

    public static function rollback() {
        //前面有数据写入，rollback
        if(!self::$_transactionCount) {
            $di = \Phalcon\DI::getDefault();
            $di->getTaskDb()->rollback();
        }
        self::$_transactionCount = 0;
        self::$_transactionMessages = array();
        //记录rollback花费时间
        $costTime = round((microtime(true) - self::$_transactionStartTime) * 1000);
        getDI()->get('taskLogger')->info("costTime>>>>rollback transaction message:{$costTime}ms");
        return true;
    }
}
