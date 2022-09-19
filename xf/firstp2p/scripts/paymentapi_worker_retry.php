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
        $result = FinanceQueueModel::instance()->popRetry();

        foreach ($result as $data)
        {
            FinanceQueueModel::instance()->processRequest($data);
        }
    }

}

$paymentapiWorker = new PaymentapiWorker();
$paymentapiWorker->run();
