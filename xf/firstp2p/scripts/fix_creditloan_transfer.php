<?php
require(dirname(__FILE__) . '/../app/init.php');
use NCFGroup\Protos\Ptp\Enum\PayQueueEnum;
use core\dao\FinanceQueueModel;
use libs\utils\Logger;
error_reporting(E_ERROR);
ini_set('display_errors', 1);

$fixData = [
    "852,100",
    "19186,50",
    "1108788,2940",
    "1439877,12838",
    "2192649,600",
    "2324582,13068",
    "2357555,1242",
    "2658866,15000",
    "3433213,822",
    "5325190,3300",
    "7270213,2700",
    "9526791,2820",
    "9629080,300"
];

$transferBizType = PayQueueEnum::BIZTYPE_CREDIT_LOAN_SERVICE_FEE;
$payerId = app_conf('SPEED_LOAN_SERVICE_FEE_UID');
foreach ($fixData as $userData) {
    list($userId, $money) = explode(',', $userData);
    // 同步到支付
    $data = array(
        'outOrderId' => '',
        'payerId' => $payerId,
        'receiverId' => $userId,
        'repaymentAmount' => bcmul($money, 100),
        'curType' => 'CNY',
        'bizType' => $transferBizType,
        'batchId' => '',
    );

    $res = FinanceQueueModel::instance()->push(array('orders' => array($data)), 'transfer');
    if (!$res) {
        Logger::info('FIX_CREDITLOAN_TRANSFER_FAIL:'. $userData);
    } else {
        Logger::info('FIX_CREDITLOAN_TRANSFER_SUCCESS:'. $userData);
    }
}
