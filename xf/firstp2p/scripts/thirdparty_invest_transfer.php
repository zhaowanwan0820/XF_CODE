<?php
require(dirname(__FILE__) . '/../app/init.php');
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\PtpTaskClient AS PtpTaskClient;
use libs\utils\PaymentApi;
use libs\utils\Alarm;
use core\dao\TradeLogModel;
ini_set('memory_limit', '2048M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);



$tradeLogModel = new TradeLogModel();

$newTrades = $tradeLogModel->findNewTrades(500);
$gs_Message = '';
$id = 0;
if (is_array($newTrades)) {
    PaymentApi::log('TradeLogSync start. total:'. count($newTrades));
    foreach ($newTrades as $trade) {
        $event = new \core\event\TradeLogSyncEvent($trade['id']);
        $taskObj = new PtpTaskClient();
        $taskId = $taskObj->register($event, 20);
        if(!$taskId){
            $eventData = get_object_vars($event);
            PaymentApi::log('TradeLogSyncEvent['.($id+1).'/'.count($newTrades).'] add-task failed. execute event:'.json_encode($eventData));
        }
        $id ++;
        $taskObj->notify($taskId, 'domq_invest_sync');
    }
}
