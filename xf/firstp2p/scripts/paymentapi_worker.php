<?php
/**
 * 支付接口调用队列消费者 (转账队列)
 * @author 全恒壮<quanhengzhuang@ucfgroup.com>
 */
ini_set('memory_limit', '512M');
set_time_limit(0);
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\FinanceQueueModel;
use libs\utils\PaymentApi;

class PaymentapiWorker
{

    public function run()
    {
        $pidList = \libs\utils\Process::getPidList('paymentapi_worker.sh');
        $pidCount = count($pidList) > 0 ? count($pidList) : 1;
        $pidOffset = array_search(posix_getppid(), $pidList);
        if ($pidOffset === false) {
            exit("进程启动方式错误，请用paymentapi_worker.sh启动\n");
        }

        for ($i = 0; $i < 1000; $i++)
        {
            $data = FinanceQueueModel::instance()->pop($pidCount, $pidOffset);

            //并发冲突
            if ($data === false)
            {
                continue;
            }

            //队列为空
            if (empty($data))
            {
                sleep($pidCount);
                break;
            }

            FinanceQueueModel::instance()->processRequest($data);
        }
    }

}

$paymentapiWorker = new PaymentapiWorker();
$paymentapiWorker->run();
