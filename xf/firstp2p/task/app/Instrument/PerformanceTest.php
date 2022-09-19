<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/11
 * Time: 12:02
 */

namespace NCFGroup\Task\Instrument;


class PerformanceTest {

    public $workerCount;
    public $runTimes;
    public $endTime;
    static private $_workersMap;
    //目前的子进程的数量
    static private $_count = 0;

    const PERFORMANCE_INFO_LOG = "/tmp/performance.info.log";
    const PERFORMANCE_STAT_LOG = "/tmp/performance.stat.log";
    const PERFORMANCE_ERROR_LOG = "/tmp/performance.error.log";

    /**
     * @param $workerCount 设定的子进程数量
     * @param $runTimes 设定的运行时间，单位分钟
     */
    public function __construct($workerCount, $runTimes) {
        if(intval($workerCount) <=0) {
            throw new \Exception("子进程的数量必须大于0");
        }
        if(floatval($runTimes) <= 0) {
            throw new \Exception("运行时间必须大于0");
        }
        $this->workerCount = intval($workerCount);
        $this->runTimes = $runTimes;
        $this->endTime = microtime(true) + $runTimes * 60;
        $this->_workerId = spl_object_hash($this);
        self::$_workersMap[$this->_workerId] = $this;
    }

    /**
     * @param $signo
     * 回收子进程的资源，并把当前的进程数减一。
     */
    static public function signalHandler($signo) {
        if($signo == SIGCHLD) {
            while(($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
                self::$_count--;
                self::log("info", "child process {$pid} terminated", true);
            }
        }
    }

    /**
     * 清除所有的日志，为统计做准备。
     */
    static public function clearAllLogs() {
        @unlink(self::PERFORMANCE_INFO_LOG);
        @unlink(self::PERFORMANCE_STAT_LOG);
        @unlink(self::PERFORMANCE_ERROR_LOG);
    }

    /**
     * 进行多进程的测试。
     */
    static public function test() {
        //清除所有的日志
        self::clearAllLogs();
        //注册子进程结束的处理函数，防止子进程变成僵尸进程
        pcntl_signal(SIGCHLD, array("\\NCFGROUP\\Task\\Instrument\\PerformanceTest", 'signalHandler'), false);
        foreach(self::$_workersMap as $worker) {
            while(self::$_count < $worker->workerCount) {
                //创建子进程
                self::forkOneWorker($worker);
            }
        }
        self::log("info", "create child processes finished.", true);
        //等待子进程结束。
        while(self::$_count) {
            pcntl_signal_dispatch();
        }
        self::log("info", "test execution end.", true);
    }

    /**
     * 统计结果。
     */
    static public function analyze() {
        $starTime = microtime(true);
        $workers = array_values(self::$_workersMap);
        $worker = $workers[0];
        $fd = fopen(self::PERFORMANCE_STAT_LOG, "r");
        $totalCostTimes = 0;
        $times = 0;
        $avgCostTime = 0;
        while($line = fgets($fd)) {
            if(preg_match("/costTime=(?<costtime>\d+)/i", $line, $matches)) {
                $times++;
                $totalCostTimes += $matches['costtime'];
            }
        }
        if($times > 0) {
            $avgCostTime = $totalCostTimes / $times;
        }
        self::log("info", "concurrency:         {$worker->workerCount}  ", true);
        self::log("info", "runtimes:            {$worker->runTimes} ", true);
        self::log("info", "total message:       {$times}    ", true);
        self::log("info", "total costTimes:     {$totalCostTimes}   ", true);
        self::log("info", "average costTime:    {$avgCostTime}  ", true);
        $endTime = microtime(true);
        $analyzingTime = round(($endTime - $starTime) * 1000);
        self::log("info","analyze execution end, costTime = {$analyzingTime}", true);
    }

    /**
     * @param $worker
     * 创建一个子进程
     */
    static public function forkOneWorker($worker) {
        $pid = pcntl_fork();
        if($pid > 0) {//parent process
            //当前子进程数量加一。
            self::$_count++;
            self::log("info", "create child process " . self::$_count . ", child process id = {$pid}", true);
        } elseif (0 === $pid) {//child process
            $worker->runChildProcess();
            exit("child process exit normally\n");
        } else {
            exit("create child process failed\n");
        }
    }

    /**
     * 子进程的运行函数，往消息队列中插任务。
     */
    public function runChildProcess() {
        self::log("info", "child process run", true);
        $startTime = microtime(true);
        self::log("info", "startTime = {$startTime}, endTime = {$this->endTime}", true);
        $event = new \NCFGroup\Task\Events\TestEvent();
        $taskService = new \NCFGroup\Task\Services\TaskService();
        try {
            while($startTime < $this->endTime) {
                //TODO: send message
                $result = $taskService->doBackground($event);
                if($result <= 0) {
                    self::log("warning", "send message failed");
                }
                $nowTime = microtime(true);
                $costTime = round(($nowTime - $startTime) * 1000);
                self::log("stat", "costTime={$costTime}");
                $startTime = $nowTime;
            }
        } catch(\Exception $e) {
            self::log("error", $e->getMessage());
        }
        self::log("info", "runChildProcess execution end.", true);
    }

    /**
     * @param $level
     * @param $message
     * 打log。
     */
    static public function log($level, $message, $debug = false) {
        $format = "[%s] [%s]\n";
        if(is_array($message)) {
            $message = json_encode($message);
        }
        $logContent = sprintf($format, date("Y-m-d H:i:s"), $message);
        if($level == "stat") {
            file_put_contents(self::PERFORMANCE_STAT_LOG, $logContent, FILE_APPEND | LOCK_EX);
        } else if($level == "info" || $level == "notice") {
            file_put_contents(self::PERFORMANCE_INFO_LOG, $logContent, FILE_APPEND | LOCK_EX);
        } else if($level == "warning" || $level == "error") {
            file_put_contents(self::PERFORMANCE_ERROR_LOG, $logContent, FILE_APPEND | LOCK_EX);
       }
        if($debug) {//调试，打印日志到屏幕。
            echo $logContent;
        }
    }
}