<?php
namespace NCFGroup\Task\Models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBase;
use NCFGroup\Task\Services\EmailService;
use NCFGroup\Task\Events\EventInterceptor;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Task\Gearman\GearMan;

class TaskFail extends ModelBase
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
    public $event;

    /**
     *
     * @var string
     */
    public $event_type;

    /**
     *
     * @var integer
     */
    public $trycnt;

    /**
     *
     * @var integer
     */
    public $task_id;

    /**
     *
     * @var string
     */
    public $exception_log;

    /**
     *
     * @var string
     */
    public $app_name;

    /**
     *
     * @var date
     */
    public $execute_time;

    /**
     *
     * @var string
     */
    public $running;

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

//END PROPERTY

    CONST RUNNING_YES = 'yes';
    CONST RUNNING_NO = 'no';

    public function initialize()
    {
        //BEGIN DEFAULT_VALUE
        $this->trycnt = '0';
        $this->executeTime = '0000-00-00 00:00:00';
        $this->ctime = '0000-00-00 00:00:00';
        $this->mtime = '0000-00-00 00:00:00';
        //END DEFAULT_VALUE
        parent::initialize();
        $this->setConnectionService('taskDb');
    }

    public function columnMap()
    {
        return array(
            'id' => 'id',
            'task_id' => 'taskId',
            'app_name' => 'appName',
            'event' => 'event',
            'event_type' => 'eventType',
            'trycnt' => 'trycnt',
            'exception_log' => 'exceptionLog',
            'execute_time' => 'executeTime',
            'running' => 'running',
            'ctime' => 'ctime',
            'mtime' => 'mtime',
        );
    }

    public function getSource()
    {
        return "task_fail";
    }

    public function afterFetch()
    {
        parent::afterFetch();
        $this->executeTime = XDateTime::valueOf($this->executeTime);
        $this->event = igbinary_unserialize($this->event);
    }

    public function beforeSave()
    {
        parent::beforeSave();
        $this->executeTime = $this->executeTime->toString();
        $this->event = igbinary_serialize($this->event);

        return true;
    }

    public static function createTaskFail(Task $task, $exceptionLog = '')
    {
        $item = new self();
        $item->initialize();
        $item->event = $task->event;
        $item->eventType  = $task->eventType;
        $item->trycnt = $task->nowtry;
        $item->executeTime = $task->executeTime;
        $item->ctime = XDateTime::now();
        $item->taskId = $task->id;
        $item->appName = $task->appName;
        $item->running = self::RUNNING_NO;
        $item->exceptionLog = $exceptionLog;

        return $item;
    }

    public function run()
    {
        if(!property_exists($this->event, "task_event_id")) {
            $this->event->task_event_id = $this->taskId;
        }
        try {
            $startExecuteTime = XDateTime::now();

            getDI()->get('taskLogger')->info("domq4Fail. event:".json_encode($this->event));
            if ($this->event instanceof EventInterceptor) {
                $this->event->before();
            }
            $successFul = $this->event->execute();
            $endExecuteTime = XDateTime::now();
            if ($successFul) {
                $this->dealWithSucc($startExecuteTime, $endExecuteTime);
            } else {
                $this->dealWithFail();
            }
        } catch (\Exception $exception) {
            $this->logException($exception);
            $this->dealWithFail(true);
        }

        if ($this->event instanceof EventInterceptor) {
            $this->event->after();
        }
    }

    public function dealWithSucc(XDateTime $startExecuteTime, XDateTime $endExecuteTime)
    {

        try {
            $this->delete();
        } catch (\Exception $e) {
            $mailSvc = new EmailService();
            $hostName = gethostname();
            $mailSvc->sendSync($this->getAlertMails(), "失败任务{$this->taskId}手动重试dealwithsucc失败", "taskid:{$this->taskId} <br/> hostnamne:{$hostName}");
        }
    }

    private function getAlertMails()
    {
        return array_merge($this->event->alertMails(), getDI()->get('config')->taskGearman->alertMails->toArray());
    }

    private function dealWithFail($hasException = false)
    {
        $this->alert4Fail($hasException);

        $this->trycnt ++;
        $this->running = self::RUNNING_NO;
        $this->save();
    }

    public function setRunning()
    {
        $this->running = self::RUNNING_YES;
    }

    public function setStopped()
    {
        $this->running = self::RUNNING_NO;
    }

    public function toGearman($worker = WxGearManWorker::DOTASK_FAIL)
    {
        GearMan::getInstance()->doBackground($this->getPrefixedWoker($worker), $this->id, $this->id);
    }

    private function getPrefixedWoker($worker)
    {
        return $this->appName.'_'.$worker;
    }

    public function alert4Fail($hasException)
    {
        $hostName = gethostname();
        $title = "失败任务手动重试再失败:{$this->eventType}";
        $content = "失败任务：[{$this->eventType}][{$hostName}]";
        if ($hasException) {
            $content .= "<br> 异常信息:".$this->exceptionLog;
        }

        $mailSvc = new EmailService();
        $mailSvc->sendSync($this->getAlertMails(), $title, $content);
    }

    private function logException(\Exception $exception)
    {
        $info = array('taskid' => $this->taskId, 'nowtry' => $this->trycnt, 'time' => XDateTime::now()->toString(),'eventType' => $this->eventType, 'exception' => $exception->getMessage());
        $this->exceptionLog = var_export($info, true);
    }

    private function getExcptionLogPath()
    {
        $todayStr = XDateTime::now()->toStringByFormat('Ymd');

        return "/tmp/failtask_exception.{$todayStr}.{$this->taskId}.txt";
    }

    public function isRunning()
    {
        return $this->running == self::RUNNING_YES;
    }

    public static function getFailTaskByTypeAndDateTime($eventType, XDateTime $dateTime)
    {
        return self::find(array(
            "eventType = :type: and ctime >= :dateTime:",
            "bind" => array(
                'type' => $eventType,
                'dateTime' => $dateTime->toString(),
            ),
            'limit' => 5000,
        ));
    }

    public static function get4TaskWebSearch(array $options = array())
    {
        $bind = array();
        $where = '1 = 1';
        if (isset($options['eventType'])) {
            $where .= ' and eventType = :eventType:';
            $bind['eventType'] = $options['eventType'];
        }
        if (isset($options['startTime']) && isset($options['endTime'])) {
            $where .= ' and ctime between :startTime: and :endTime:';
            $bind['startTime'] = $options['startTime'];
            $bind['endTime'] = $options['endTime'];
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

    public static function getFailTaskByTaskId($taskId)
    {
        return self::find(array(
            'taskId = :taskId:',
            'bind' => array(
                'taskId' => $taskId,
            ),
        ))->getFirst();
    }

    public static function getRunningTaskIds(array $taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }

        return self::query()
            ->columns('taskId')
            ->where('running = :yes:', array('yes' => self::RUNNING_YES))
            ->inWhere('taskId', $taskIds)
            ->execute()->toArray();
    }

    public static function getRunFailTaskIds(array $taskIds)
    {
        if (empty($taskIds)) {
            return array();
        }

        return self::query()
            ->columns('taskId')
            ->where('running = :no:', array('no' => self::RUNNING_NO))
            ->inWhere('taskId', $taskIds)
            ->execute()->toArray();
    }
}
