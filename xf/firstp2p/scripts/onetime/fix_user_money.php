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
use core\service\UserThirdBalanceService;

set_time_limit(0);
ini_set('memory_limit', '4096M');

try {
        $syncRemoteData[] = array(
            'outOrderId' => 'PREPAYINTEREST|' . '110255',
            'payerId' => 7251619,
            'receiverId' => 7736629,
            'repaymentAmount' => 3560995,
            'curType' => 'CNY',
            'bizType' => 1,
            'batchId' => 1663338,
        );

        if (!\core\dao\FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', \core\dao\FinanceQueueModel::PRIORITY_HIGH)) {
            echo "FinanceQueueModel push error";
        }
       echo "FinanceQueueModel push succ";
} catch (\Exception $e) {
    echo "FinanceQueueModel push fail err:".$e->getMessage();
}