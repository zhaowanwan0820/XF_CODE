<?php
require(dirname(__FILE__) . '/../app/init.php');
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\PtpTaskClient AS PtpTaskClient;
use libs\utils\PaymentApi;
ini_set('memory_limit', '2048M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

\libs\utils\Script::start();

$userId = intval(app_conf('AGENCY_ID_JF_REPAY'));
$createBefore = strtotime('-1 hours') - 28800;

$sql = "SELECT id FROM firstp2p_user_carry WHERE user_id='{$userId}' AND withdraw_status = 0 AND create_time <= '{$createBefore}' AND status = 3";

$withdrawItems = $db->get_slave()->getAll($sql);
if (is_array($withdrawItems)) {
    PaymentApi::log('WithdrawEventForJifu start. total:'. count($withdrawItems));
    foreach ($withdrawItems as $id =>  $withdraw) {
        $event = new \core\event\WithdrawEvent($withdraw['id']);
        $taskObj = new PtpTaskClient();
        $taskId = $taskObj->register($event);
        if(!$taskId){
            $eventData = get_object_vars($event);
            PaymentApi::log('WithdrawEvent['.($id+1).'/'.count($withdrawItems).'] add-task failed. execute event:'.json_encode($eventData));
        }
        $taskObj->notify($taskId, 'domq_withdraw');
    }
}

\libs\utils\Script::end();
