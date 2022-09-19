<?php
/**
 * 定时任务处理脚本
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
use core\dao\JobsModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');
\libs\utils\PhalconRPCInject::init();

class JobsWorker {

    private $priorityLow;
    private $priorityHigh;

    public function __construct($low,$high){
        $this->priorityLow = $low;
        $this->priorityHigh = $high;
    }

    public function run() {
        $jobs_model = new JobsModel();
        do {
            $start = microtime(true);

            $job = $jobs_model->getOneJob($this->priorityLow,$this->priorityHigh);
            if ($job && $job->function) {
                \libs\utils\Monitor::add('JOBS_WORKER_PRIORITY_RUN');
                $GLOBALS['db']->startTrans();
                try {
                    $startJobs = $job->startJob();
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $job->id, "start")));
                    $result = $job->runJob();
                    $arr_log = array(__CLASS__, __FUNCTION__, $job->id, $job->function, $job->params);
                    if ($result) {
                        $arr_log[] = "succ";
                        Logger::info(implode(" | ", $arr_log));
                        $job->finishJob();
                    } else {
                        $arr_log[] = "fail";
                        Logger::info(implode(" | ", $arr_log));
                        throw new \Exception('runJobFailed');
                    }

                    $GLOBALS['db']->commit();
                } catch (\Exception $e) {
                    Logger::info('JobsWorkerException. message:'.$e->getMessage());
                    \libs\utils\Monitor::add('JOBS_WORKER_PRIORITY_RUN_FAILED');
                    $GLOBALS['db']->rollback();
                    $job->finishJob(false);
                }
            }

            $cost = round(microtime(true) - $start, 3);
            Logger::info("JobsWorkerDone. function:{$job->function}, cost:{$cost}");
        } while($job);

        Logger::info('JobsWorkerEmpty');
        sleep(3);
    }
}

$priorityLow = 0;
$priorityHigh = 0;
if ( count($argv)!=3 ){
    echo '`which php` jobs_worker_for_test.php ${low} ${high}'."\n";
    exit(0);
}else{
    if(intval($argv[1]) <0 || intval($argv[2])<=0){
        echo '${priority} must >= 0. the bigger number ,the higher priority'."\m";
        echo 'the worker only accept works which priority is between the given number low & high'."\n";
        exit(0);
    }else{
        $priorityLow = intval($argv[1]);
        $priorityHigh = intval($argv[2]);
    }
}

/*
$pid = posix_getpid();
$cmd = "ps aux | grep \"jobs_worker_with_priority.php {$priorityLow} {$priorityHigh}\" | grep -v {$pid} | grep -v grep | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo sprintf("task with priority [ %s - %s ] is exist ~! \n",$priorityLow,$priorityHigh);
    exit(0);
}
*/
$obj = new JobsWorker($priorityLow, $priorityHigh);
$obj->run();


