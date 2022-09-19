<?php
namespace NCFGroup\Task\Gearman;

use NCFGroup\Task\Instrument\DistributionLock;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Models\TaskFail;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Task\Services\WechatService;
use Phalcon\Logger\Adapter\File as FileAdapter;
use NCFGroup\Task\Services\RedisTaskService;

class WxGearManWorker
{
    /**
     *  基础worker
     */
    const DOTASK_BASE = 'domq';

    /**
     *  重试worker
     */
    const DOTASK_RETRY = 'domq_retry';

    /**
     *  再重试失败任务
     */
    const DOTASK_FAIL = 'domq_fail';

    /**
     *  替补worker 平常不用
     */
    const DOTASK_SUB = 'domq_sub';

    public static function domqBase($job)
    {
        $di = \Phalcon\DI::getDefault();
        if (empty($di)) {
            throw new \Exception('必须传入di!');
        }

        getDI()->get('taskLogger')->info("domqBase. worker receive workload:{$job->workload()}");

        $isLocked = DistributionLock::getInstance()->getLockWait($job->workload());
        if(!$isLocked) {
            getDI()->get('taskLogger')->info("domqBase. can not get lock :{$job->workload()}");
            WechatService::sendMessage("lock error", "domqBase. can not get lock :{$job->workload()}");
            return;
        }
        if(intval($job->workload()) > 0) { //db mode
            $startTime = microtime(true);
            Task::consume(intval($job->workload()));
            $endTime = microtime(true);
            $costTime = round(($endTime - $startTime) * 1000, 4);
            getDI()->get('taskLogger')->info("domqBase. DbTask id = {$job->workload()}, consume costTime={$costTime}ms");
        } else { //redis mode
            $startTime = microtime(true);
            $jobInfo = json_decode($job->workload(), true);
            RedisTaskService::consume($jobInfo['id']);
            $endTime = microtime(true);
            $costTime = round(($endTime - $startTime) * 1000, 4);
            getDI()->get('taskLogger')->info("domqBase. RedisTask id = {$jobInfo['id']}, consume costTime={$costTime}ms");
        }
        DistributionLock::getInstance()->releaseLock($job->workload());
    }

    public static function domq4Fail($job)
    {
        $di = \Phalcon\DI::getDefault();

        if (empty($di)) {
            throw new \Exception('必须传入di!');
        }
        getDI()->get('taskLogger')->info("domq4Fail. worker receive workload:{$job->workload()}");
        $isLocked = DistributionLock::getInstance()->getLockWait($job->workload());
        if(!$isLocked) {
            getDI()->get('taskLogger')->info("domq4Fail. can not get lock :{$job->workload()}");
        }
        $idInWorker = intval($job->workload());
        $failTask = TaskFail::findFirst($idInWorker);
        getDI()->get('taskLogger')->info("domq4Fail. failTask:".json_encode($failTask));
        if (!$failTask instanceof TaskFail) {
            $mailSvc = new \NCFGroup\Task\Services\EmailService();
            $hostName = gethostname();
            $mailSvc->sendSync(getDI()->get('config')->taskGearman->alertMails->toArray(), "worker未能find出failtask, failtaskid:{$idInWorker}, hostname:{$hostName}", $idInWorker);
            DistributionLock::getInstance()->releaseLock($job->workload());
            return true;
        }

        $failTask->run();
        DistributionLock::getInstance()->releaseLock($job->workload());
    }

    public static function monitorShutDown()
    {
        $di = getDI();
        //进程非正常结束报警
        register_shutdown_function(function () use ($di) {
            $error = error_get_last();
            if (isset($error["type"])) {
                $error['friend_type'] = self::friendlyErrorType($error['type']);
                $todayStr = XDateTime::now()->toString();

                $content = $todayStr.print_r($error, true)."\n";
                $shutDownLog = new FileAdapter(getDI()->get('config')->taskLogger->file->shutDownPath);
                $shutDownLog->error("{$todayStr} msg:".json_encode($error));

                $send = ($error['type'] != E_STRICT &&
                    $error["type"] != E_WARNING &&
                    $error["type"] != E_NOTICE &&
                    $error["type"] != E_DEPRECATED &&
                    $error["type"] != E_USER_WARNING &&
                    //防止进程频繁重启, 而不断的发邮件, 所以设定成在300秒内只能发一封邮件
                    $di->get('frequencyHandler')->canDo('mq_shutdown_mail', 300));

                if ($send) {
                    $env = APP_ENV;

                    if ($env == 'dev') {
                        preg_match('/(?<shorthostname>\w+)\./', gethostname(), $matches);
                        $short_host_name = $matches['shorthostname'];
                        $alertMails = array_unique(array_merge(
                            array("{$short_host_name}@ucfgroup.com"),
                            $di->get('config')->taskGearman->alertMails->toArray()
                        ));
                    } else {
                        $alertMails = $di->get('config')->taskGearman->alertMails->toArray();
                    }

                    $hostName = gethostname();
                    $mailSvc = new \NCFGroup\Task\Services\EmailService();
                    $mailSvc->sendSync($alertMails, "[{$env}] mq进程非正常结束 {$hostName}", nl2br($content));
                    WechatService::sendMessage("[{$env}] mq进程非正常结束 {$hostName}", nl2br($content));
                }
            }
        });
    }

    private static function friendlyErrorType($type)
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }

        return "";
    }
}
