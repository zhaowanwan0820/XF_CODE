<?php
namespace NCFGroup\Task\Gearman;

class JobServerFunction
{
    public $functionName;
    public $waitingJobCnt;
    public $runningJobCnt;
    public $workerCnt;

    const LINE_PREG = "#(?<functionname>\w+)\s+(?<wait>\d+)\s+(?<run>\d+)\s+(?<worker>\d+)#";

    public function __construct($functionName, $waitingJobCnt, $runningJobCnt, $workerCnt)
    {
        $this->functionName = $functionName;
        $this->waitingJobCnt = $waitingJobCnt;
        $this->runningJobCnt = $runningJobCnt;
        $this->workerCnt = $workerCnt;
    }

    public static function createByLineStr($lineStr)
    {
        if (preg_match(self::LINE_PREG, $lineStr, $matches)) {
            return new self($matches['functionname'], $matches['wait'], $matches['run'], $matches['worker']);
        }

        return false;
    }
}
