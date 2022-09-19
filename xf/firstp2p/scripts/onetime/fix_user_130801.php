<?php

/*
 * 修复用户130801资金及资金记录问题
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';

use core\dao\UserLogModel;
use core\dao\UserModel;
use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '4096M');

$userId = 130801;


$GLOBALS['db']->startTrans();

try {
    //补用户网贷P2P资金记录
    $user = UserModel::instance()->find($userId);

    $userLog = new UserLogModel();

    $userLog->log_info = "网贷P2P账户余额修正";
    $userLog->note = "网贷P2P账户余额修正";
    $userLog->log_time = get_gmtime();
    $userLog->log_admin_id = 0;
    $userLog->log_user_id = $userId;
    $userLog->user_id = $userId;
    $userLog->deal_type = 4;
    $userLog->money = -500;
    $userLog->remaining_money = 100.30;
    $userLog->remaining_total_money = 100.30;

    if(!$userLog->insert()){
        throw new \Exception("ChangeMoney增加资金记录失败. userId:{$this->id}");
    }else{
        Logger::info("用户:130801,网贷P2P账户余额修正".$user_log->money."成功!");
    }

    $user = UserModel::instance()->find($userId);
    $user->changeMoneyAsyn = true;
    $user->changeMoneyDealType = 3;
    if(empty($user)){
        throw new \Exception("查询不到当前用户".$userId);
    }
    $result = $user->changeMoney(500,"网信理财账户余额修正","网信理财账户余额修正",0,0,UserModel::TYPE_MONEY);
    $resultlock = $user->changeMoney(500,"网信理财账户余额修正冻结","网信理财账户余额修正冻结",0,0,UserModel::TYPE_LOCK_MONEY);

    if((!$result)||(!$resultlock)){
        throw new \Exception("冻结资金失败!");
    }else{
        Logger::info("用户:130801,网信理财P账户余额修正成功!");
    }

    $GLOBALS['db']->commit();
} catch (\Exception $e) {
    $GLOBALS['db']->rollback();
    Logger::info("用户:130801,账户余额修正失败!".$e->getMessage());
}

echo "用户:130801,账户余额修正成功!";
