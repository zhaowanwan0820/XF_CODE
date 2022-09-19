<?php

require dirname(__FILE__).'/../../app/init.php';

use libs\utils\Logger;
use libs\utils\Alarm;
use NCFGroup\Common\Library\GTM\GlobalTransactionWorker;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;

set_time_limit(0);
ini_set('memory_limit', '1024M');
\libs\utils\PhalconRPCInject::init();

$worker = new GlobalTransactionWorker();
$transactions = $worker->getAsyncTransactions(200);
if (empty($transactions)) {
    Logger::info('gtm_worker get empty');
    sleep(1);
}

foreach ($transactions as $item) {
    try {
        $gtm = new GlobalTransactionManager($item['tid']);
        $result = $gtm->executeBackgroud();
        Logger::info("gtm_worker execute. tid:{$item['tid']}, name:{$item['name']}, result:".var_export($result, true));
    } catch (\Exception $e) {
        Logger::error("gtm_worker exception. tid:{$item['tid']}, name:{$item['name']}, msg:".$e->getMessage());
        Alarm::push('gtm', 'worker_exception', "tid:{$item['tid']}, name:{$item['name']}, msg:".$e->getMessage());
    }
}
