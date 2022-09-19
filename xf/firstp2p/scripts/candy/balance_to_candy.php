<?php
/**
 * 给指定余额的用户增加信宝
 * 参数1,标识
 */
require_once dirname(__FILE__).'/../../app/init.php';

use libs\utils\Logger;
use core\service\candy\CandyService;
use core\service\ncfph\AccountService;

$times = intval(date('H')/2);
if(isset($argv[1]) && $argv[1] >= 0 && $argv[1] < 12){
    $times = intval($argv[1]);
}

//指定日期
if(!empty($argv[2])) {
    $times = $argv[2] . '_' . $times;
} else {
    $times = date("Ymd_") . $times;
}

//去普惠余额一次取多少范围的userid
$step = 100000;
if(!empty($argv[3])) {
    $step = intval($argv[3]);
}

set_time_limit(0);
ini_set('memory_limit', '4096M');

try{
    $selectSql = "select id as user_id,money from firstp2p_user where money>=".CandyService::BALANCE_MIN_AMOUNT." and user_purpose = 1;";
    $res =  $GLOBALS['db']->getInstance('firstp2p','slave')->getAll($selectSql);
    addCandyByBalance($res, 'ncfwx', $times);

    //普惠余额加信宝
    $maxUserId = $GLOBALS['db']->getInstance('firstp2p', 'slave')->getOne("select max(id) from firstp2p_user;");
    $accountService = new AccountService();
    for ($i=1; $i<=$maxUserId; $i+= $step) {
        $users = $accountService->getBalanceByRange($i, $i + $step - 1, CandyService::BALANCE_MIN_AMOUNT);
        addCandyByBalance($users, 'ncfph', $times);
    }

}catch (\Exception $e){
    Logger::info("balance to candy execute script exception. msg:". $e->getMessage());
}

function addCandyByBalance($users, $platform, $times)
{
    if (empty($users)) {
        Logger::info("balance to candy. users empty, platform:{$platform}");
        return false;
    }

    foreach($users as $user){
        //用户id
        $userId = $user['user_id'];
        //用户总余额
        $totalBalance = $user['money'];
        //唯一id
        $token = $platform . '_balance_' . $userId . '_' . $times;

        try {
            $res = CandyService::changeAmountByType($userId, $token, CandyService::SOURCE_TYPE_BALANCE, $totalBalance, "余额{$totalBalance}");
            Logger::info("balance to candy. token:{$token}, userId:{$userId}, balance:{$totalBalance}, res:".json_encode($res));
        } catch (\Exception $e) {
            Logger::info("balance to candy exception. token:{$token}, msg:". $e->getMessage());
        }
    }
    Logger::info("balance to candy execute script success. platform:{$platform}");

}
