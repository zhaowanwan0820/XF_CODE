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

$userId = 7307872;


$GLOBALS['db']->startTrans();

try {
    //补用户网贷P2P资金记录
    $user = UserModel::instance()->find($userId);
    $user->changeMoneyDealType = 4;
    if(empty($user)){
        throw new \Exception("查询不到当前用户".$userId);
    }
    $user->changeMoney(9.75,"业务信息服务费","银信通业务信息服务费",0,0,UserModel::TYPE_MONEY);
    $user->changeMoney(1.57,"业务信息服务费","银信通业务信息服务费",0,0,UserModel::TYPE_MONEY);
    $GLOBALS['db']->commit();
} catch (\Exception $e) {
    $GLOBALS['db']->rollback();
    Logger::info("用户:7307872,账户余额修正失败!".$e->getMessage());
}

echo "用户:7307872,账户余额修正成功!";
