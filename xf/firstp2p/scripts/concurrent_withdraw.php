<?php
require(dirname(__FILE__) . '/../app/init.php');
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\PtpTaskClient AS PtpTaskClient;
use libs\utils\PaymentApi;
use libs\utils\Alarm;
use core\dao\UserCarryModel;

\libs\utils\Script::start();

ini_set('memory_limit', '2048M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);
$createBefore = 0;
if (app_conf('RUNTIME_WITHDRAW_DELAY_SECONDS')) {
    $createBefore = get_gmtime() - intval(app_conf('RUNTIME_WITHDRAW_DELAY_SECONDS'));
}
else {
    $createBefore = strtotime('-5 hours') - 28800;
}


$idLimit = $db->get_slave()->getOne('SELECT max(id) FROM firstp2p_user_carry');
if (!$idLimit)
{
    $idLimit = 5000000;
}
else
{
    $idLimit = $idLimit - 200000;
}
$sql = "SELECT id,deal_id,user_id,warning_stat,create_time FROM firstp2p_user_carry WHERE id > '{$idLimit}' AND withdraw_status = 0 AND create_time <= '{$createBefore}' AND status = 3";
$withdrawItems = $db->get_slave()->getAll($sql);
if (is_array($withdrawItems)) {
    PaymentApi::log('WithdrawEvent start. total:'. count($withdrawItems));
    $dcService = new \core\service\DealCompoundService();
    foreach ($withdrawItems as $id => $withdraw) {
        // 检查用户是否符合风控延迟提现规则-JIRA4937
        if ($withdraw['warning_stat'] == UserCarryModel::WITHDRAW_IS_DELAY) {
            // 当前日期是节假日，不处理
            $isCurrentHoliday = $dcService->checkIsHoliday(date('Y-m-d'));
            if ($isCurrentHoliday) {
                PaymentApi::log(sprintf('WithdrawEvent_Delay_Holiday, today_IsHoliday, withdrawId:%d, userId:%d, dealId:%d, Is_Withdraw_Delayed', $withdraw['id'], $withdraw['user_id'], $withdraw['deal_id']));
                continue;
            }

            // 获取当前时间戳
            $currentTimeStamp = time();
            // 提现日期  > 当前日期，不处理
            $createTimestamp = timestamp_to_conf_zone($withdraw['create_time']);
            if (($createTimestamp + (int)UserCarryModel::$withdrawDelayConfig['withdrawDelayTime']) > $currentTimeStamp) {
                PaymentApi::log(sprintf('WithdrawEvent_Delay_Holiday, withdrawDate_GreaterThan_CurrentDate, withdrawId:%d, userId:%d, dealId:%d, withdrawCreateTime:%s, Is_Withdraw_Delayed', $withdraw['id'], $withdraw['user_id'], $withdraw['deal_id'], date('Ymd_His', $createTimestamp)));
                continue;
            }

            $createDate = date('Y-m-d', $createTimestamp);
            // 获取提现申请日的零点
            $createDayZeroTimestamp = strtotime($createDate);
            // 获取提现日期之后的下一个工作日
            $nextWorkTimeStamp = $createDayZeroTimestamp + 86400;
            while ($dcService->checkIsHoliday(date('Y-m-d', $nextWorkTimeStamp))) {
                $nextWorkTimeStamp += 86400;
            }

            // 提现日期是节假日
            $isWithdrawHoliday = $dcService->checkIsHoliday($createDate);
            if ($isWithdrawHoliday) {
                $withdrawRequestTime = $nextWorkTimeStamp + (int)UserCarryModel::$withdrawDelayConfig['withdrawDelayTime'];
            }else{
                // 计算提现时间到提现零点的描述
                $createDaySecond = $createTimestamp - $createDayZeroTimestamp;
                $withdrawRequestTime = $nextWorkTimeStamp + $createDaySecond;
            }

            // 还没到该延迟提现的发起时间
            if ($currentTimeStamp - $withdrawRequestTime < 0) {
                PaymentApi::log(sprintf('WithdrawEvent_Delay, withdrawId:%d, userId:%d, dealId:%d, createTimestamp:%s, nextWorkTimeStamp:%s, withdrawRequestTime:%s, Is_Withdraw_Delayed', $withdraw['id'], $withdraw['user_id'], $withdraw['deal_id'], date('Ymd_His', $createTimestamp), date('Ymd_His', $nextWorkTimeStamp), date('Ymd_His', $withdrawRequestTime)));
                continue;
            }
            PaymentApi::log(sprintf('WithdrawEvent_Request, withdrawId:%d, userId:%d, dealId:%d, createTimestamp:%s, nextWorkTimeStamp:%s, withdrawRequestTime:%s, IS_PROCESSING', $withdraw['id'], $withdraw['user_id'], $withdraw['deal_id'], date('Ymd_His', $createTimestamp), date('Ymd_His', $nextWorkTimeStamp), date('Ymd_His', $withdrawRequestTime)));
        }

        $event = new \core\event\WithdrawEvent($withdraw['id']);
        $taskObj = new PtpTaskClient();
        $taskId = $taskObj->register($event);
        if(!$taskId){
            $eventData = get_object_vars($event);
            PaymentApi::log('WithdrawEvent['.($id+1).'/'.count($withdrawItems).'] add-task failed. execute event:'.json_encode($eventData));
        }
        // 企业放款的优先级高、个人用户的优先级相对低
        $workerName = $withdraw['deal_id'] > 0 ? 'domq_withdraw_pro' : 'domq_withdraw';
        $taskObj->notify($taskId, $workerName);
    }
}
\libs\utils\Script::end();
