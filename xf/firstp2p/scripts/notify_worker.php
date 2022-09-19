<?php
/**
 * 定时任务处理脚本
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
use libs\utils\Process;
use core\service\OrderNotifyService;
use core\dao\OrderNotifyModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');
// 引入phalcon RPC相关，使jobs异步处理RPC请求
\libs\utils\PhalconRPCInject::init();

class NotifyWorker
{

    public static function run($priority, $pidCount, $pidOffset)
    {
        $notifyModel = new OrderNotifyModel();
        $notifys = $notifyModel->getNotifys($pidCount, $pidOffset);
        if (empty($notifys)) {
            Logger::info("NotifyWorkerEmpty. pidCount:{$pidCount},pidOffset:{$priority}");
            return false;
        }

        foreach ($notifys as $notify) {
            $res = OrderNotifyService::notify($notify->id);
            if(!$res){
                continue;
            }
        }

        Logger::info("NotifyWorkerDone. pidCount:{$pidCount},pidOffset:{$priority}");
    }

}



$pidList = Process::getPidList("notify_worker.sh$");
$pidCount = count($pidList) > 0 ? count($pidList) : 1;
$pidOffset = array_search(posix_getppid(), $pidList);
if ($pidOffset === false) {
    exit("进程启动方式错误，请用notify_worker.sh启动\n");
}

//如果处理的任务为空，sleep一段时间
if (NotifyWorker::run($priority, $pidCount, $pidOffset) === false) {
    sleep($pidCount);
}
