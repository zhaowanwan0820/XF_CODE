<?php
/**
 * 定时任务处理脚本
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
use libs\utils\Process;
use core\dao\JobsModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');
// 引入phalcon RPC相关，使jobs异步处理RPC请求
\libs\utils\PhalconRPCInject::init();

class JobsWorker
{

    public static function run($priority, $pidCount, $pidOffset)
    {
        $jobsModel = new JobsModel();
        $jobs = $jobsModel->getJobs($priority, $pidCount, $pidOffset);
        if (empty($jobs)) {
            Logger::info("JobsWorkerEmpty. priority:{$priority}");
            return false;
        }

        foreach ($jobs as $job) {
            if (!$job->start()) {
                break;
            }

            \libs\utils\Monitor::add('JOBS_WORKER_RUN');
            try {
                $GLOBALS['db']->startTrans();

                if (!$job->runJob()) {
                    throw new \Exception($job->err_msg);
                }
                $job->success();

                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                Logger::info("JobsWorkerFailed. id:{$job->id}, function:{$job->function}, message:".$e->getMessage());
                $GLOBALS['db']->rollback();
                $job->failed();
            }
        }

        Logger::info("JobsWorkerDone. priority:{$priority}");
    }

}

if (!isset($argv[1])) {
    exit("请指定priority参数\n");
}

$priority = intval($argv[1]);

$pidList = Process::getPidList("jobs_worker.sh {$priority}$");
$pidCount = count($pidList) > 0 ? count($pidList) : 1;
$pidOffset = array_search(posix_getppid(), $pidList);
if ($pidOffset === false) {
    exit("进程启动方式错误，请用jobs_worker.sh启动\n");
}

//如果处理的任务为空，sleep一段时间
if (JobsWorker::run($priority, $pidCount, $pidOffset) === false) {
    sleep($pidCount);
}
