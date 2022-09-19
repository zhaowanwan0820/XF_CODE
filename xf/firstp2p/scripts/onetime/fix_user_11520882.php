<?php

/*
 * 修复用户资金及资金记录问题
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';

use core\dao\UserLogModel;
use core\dao\UserModel;
use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '4096M');

try {
    $GLOBALS['db']->startTrans();
    //补用户网贷P2P资金记录
    $user = UserModel::instance()->find(7736629);
    $user->changeMoneyDealType = 4;
    if(empty($user)){
        throw new \Exception("查询不到当前用户 7736629");
    }

    $user->changeMoney(35609.95,"提前还款金额修正","提前还款金额修正-标的ID 1663338",0,0,UserModel::TYPE_DEDUCT_LOCK_MONEY);

    $GLOBALS['db']->commit();
} catch (\Exception $e) {
    $GLOBALS['db']->rollback();
    Logger::info("用户账户余额修正失败!".$e->getMessage());
}

echo "用户账户余额修正成功!";
