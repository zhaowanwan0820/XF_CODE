<?php
/**
 * 异步资金队列消费者
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');

use core\dao\UserModel;

function pop($count, $offset)
{
    $startTime = microtime(true);
    $resultArray = $GLOBALS['db']->getAll("SELECT * FROM firstp2p_money_queue WHERE status=0 AND user_id%{$count}={$offset} LIMIT 500");
    if (empty($resultArray)) {
        \libs\utils\Logger::info("MoneyQueueEmpty. count:{$count}, offset:{$offset}");
        return false;
    }

    //可以扣负
    $negative = 1;
    foreach ($resultArray as $result) {
        try {
            $GLOBALS['db']->startTrans();
            $user = UserModel::instance()->find($result['user_id']);
            if (empty($user)) {
                throw new \Exception('用户不存在');
            }

            $user->changeMoneyDealType = $result['deal_type'];
            $bizToken = empty($result['biz_token']) ? [] : json_decode($result['biz_token'], true);
            $user->changeMoney($result['money'], $result['message'], $result['note'], 0, 0, $result['money_type'], $negative, $bizToken);

            $ret = $GLOBALS['db']->update('firstp2p_money_queue', array('status' => 1, 'update_time' => time()), "id='{$result['id']}' AND status=0");
            if (!$ret || $GLOBALS['db']->affected_rows() < 1) {
                throw new \Exception('已处理');
            }

            $cost = round(microtime(true) - $startTime, 3);
            \libs\utils\Logger::info("MoneyQueueSuccess. count:{$count}, offset:{$offset}, cost:{$cost}, data:".json_encode($result, JSON_UNESCAPED_UNICODE));
            \libs\utils\Monitor::add('MONEY_QUEUE_RUN_SUCCESS');
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            \libs\utils\Logger::error("MoneyQueueFailed. count:{$count}, offset:{$offset}, message:".$e->getMessage().', data:'.json_encode($result, JSON_UNESCAPED_UNICODE));
            \libs\utils\Monitor::add('MONEY_QUEUE_RUN_FAILED');
            \libs\utils\Alarm::push('MoneyQueue', '余额修改失败', 'message:'.$e->getMessage().', data:'.json_encode($result, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    return true;
}

$pidList = \libs\utils\Process::getPidList('money_queue_worker.sh');
$count = count($pidList) > 0 ? count($pidList) : 1;
$offset = array_search(posix_getppid(), $pidList);
if ($offset === false) {
    exit("进程启动方式错误，请用money_queue_worker.sh启动\n");
}

if (pop($count, $offset) === false) {
    sleep($count);
}
