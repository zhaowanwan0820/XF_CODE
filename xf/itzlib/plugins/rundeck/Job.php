<?php

/**
 * Job instance
 */
class Job
{
    /**
     * Name
     * @var [type]
     */
    var $name;

    /**
     * Shell command lines to exec
     * @var array
     */
    var $exec = [];

    /**
     * Cron expression to schedule job execution
     * @var [type]
     */
    var $schedule = null;

    /**
     * Description of job
     * @var string
     */
    var $description = "";

    /**
     * emails to send when successfully executed
     */
    var $notifySuccess = [];

    /**
     * emails to send when failure to execute
     */
    var $notifyFailure = [];

    /**
     * emails to send when job started
     */
    var $notifyStart = [];


    function __construct($name)
    {
        $this->name = $name;
    }

    public function addYiicExec($console, $action = '', $params = [])
    {
        $this->exec[] = $this->getYiicExec($console, $action, $params);
    }

    public function getYiicExec($console, $action = '', $params = [])
    {
        $rundeck = Yii::app()->rundeck;
        $paramsStr = "";
        foreach ($params as $key => $value) {
            $paramsStr .= "--{$key}={$value} ";
        }
        return "{$rundeck->phpPath} {$rundeck->yiicPath} $console $action $paramsStr";
    }

}
