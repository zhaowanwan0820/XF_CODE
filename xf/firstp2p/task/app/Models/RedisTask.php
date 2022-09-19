<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/12/8
 * Time: 21:01
 */

namespace NCFGroup\Task\Models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Task\Events\AsyncEvent;
use NCFGroup\Task\Events\EventInterceptor;
use NCFGroup\Task\Services\EmailService;

class RedisTask {
    public $id;
    public $status;
    public $nowTry;
    public $maxTry;
    public $event;
    public $eventType;
    public $priority;
    public $paralleled;
    public $executeTime;
    public $queueName;
    public $errorLog;

    public function __construct($id, AsyncEvent $event, $queueName, XDateTime $executeTime = null, $maxTry = Task::DEFAULT_MAX_RETRY_TIME, $priority = "normal", $paralleled = true, $status = null) {
        $this->id = $id;
        $this->event = $event;
        $this->eventType = get_class($event);
        $this->queueName = $queueName;
        $this->executeTime = is_null($executeTime) ? time() : $executeTime->getTime();
        $this->status = is_null($status) ? (is_null($executeTime) ? Task::STATUS_RUN_NOW : Task::STATUS_RUN_TIMED) : $status;
        $this->nowTry = 0;
        $this->maxTry = $maxTry;
        $this->priority = $priority;
        $this->paralleled = $paralleled;
        $this->errorLog = "";
    }

    public function hasReachedMaxTry()
    {
        return $this->nowTry >= $this->maxTry;
    }

    public function incrementNowTryTimes() {
        ++$this->nowTry;
    }

    public function setTimedStatus() {
        $this->status = Task::STATUS_RUN_TIMED;
    }

    public function setInvalidStatus() {
        $this->status = Task::STATUS_INVALID;
    }

    public function setRunNowStatus() {
        $this->status = Task::STATUS_RUN_NOW;
    }

    public function setNextExecuteTime() {
        $this->executeTime += 60 * pow(2, $this->nowTry);
    }

    public function run() {
        if(!property_exists($this->event, "task_event_id")) {
            $this->event->task_event_id = $this->id;
        }
        try {
            if ($this->event instanceof EventInterceptor) {
                $this->event->before();
            }

            try {
                $successFul = $this->event->execute();
            } catch (\Exception $e) {
                if ($this->event instanceof EventInterceptor) {
                    $this->event->after();
                }
                getDI()->get('taskLogger')->error(__FUNCTION__." eventType:{$this->eventType}, taskid:{$this->id}, executeException:" . $e->getMessage());
                throw $e;
            }
            if ($this->event instanceof EventInterceptor) {
                $this->event->after();
            }
        } catch (\Exception $e) {
            throw new \Exception('event_exception, errorMessage='.$e->getMessage() . 'errorTrace=' . $e->getTraceAsString());
        }

        return $successFul;
    }

    public function alert() {
        $hostName = gethostname();
        $title = "失败任务:{$this->eventType}";
        $content = "失败任务：[{$this->eventType}][{$hostName}], 失败原因: [{$this->errorLog}]";
        $mailSvc = new EmailService();
        $mailSvc->sendSync($this->event->alertMails(), $title, $content);
    }
}