<?php
namespace libs\payment\supervision;

use libs\common\WXException;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\PtpTaskClient AS PtpTaskClient;

use core\event\SupervisionCheckEvent;

class SupervisionChecker
{


    public static function registerCheck($userId)
    {
        try {
            $checkEvent = new SupervisionCheckEvent($userId);
            $task = new PtpTaskClient();
            $tid = $task->register($checkEvent,10);
            $task->notify($tid, 'domq');
        } catch (\Exception $e) {
            \libs\utils\Logger::error("registerCheckFail. userId:{$userId}. message:".$e->getMessage());
        }
    }
}
