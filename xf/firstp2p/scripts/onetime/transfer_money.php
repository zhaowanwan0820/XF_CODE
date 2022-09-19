<?php
/*
 * 网贷转账脚本
 因投资人汪敏去世现需将其存管账户资金转账至其家属汪晶晶存管账户，请配合调账：

 调账信息如下：
 调增账户：用户ID：11497796
 用户姓名：汪晶晶
 调增金额：3元

 调减账户：用户ID：7244452
 用户姓名：汪敏
 调减金额：3元
 */

ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use libs\utils\Logger;
use core\dao\UserModel;
use core\dao\DealModel;

$receiverId = 11497796;
$payerId = 7244452;
$money = 3;
$userLogType = '调账';
$userLogMemo = '授权转账';

/*
 * 通用化，以后可做成工具
$receiverId = !empty($argv[1]) ? (int) $argv[1] : 0;
$payerId = !empty($argv[2]) ? (int) $argv[2] : 0;
$money = !empty($argv[3]) ? (float) $argv[3] : 0;
$userLogType = !empty($argv[4]) ? $argv[4] : '';
$userLogMemo = !empty($argv[5]) ? $argv[5] : '';
if (empty($receiverId) || empty($payerId) || empty($money) || empty($userLogType) || empty($userLogMemo)) {
    echo 'exp: php transfer_money.php receiverId payerId money userLogType userLogMemo';
    exit;
}
 */

Logger::info(sprintf('transfer_money, begin, payerId:%d,receiverId:%d,money:%s', $payerId, $receiverId, $money));

$userModel = UserModel::instance();
$payerDao = $userModel->find($payerId);
if (empty($payerDao)) {
    Logger::error('transfer_money, 付款人不存在, ' . $payerId);
    exit;
}

$receiverDao = $userModel->find($receiverId);
if (empty($receiverDao)) {
    Logger::error('transfer_money, 收款人不存在, ' . $receiverId);
    exit;
}

$payerDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
$receiverDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;

try {
    $userModel->db->startTrans();
    $changeMoneyResult = $payerDao->changeMoney(-$money, $userLogType, $userLogMemo, 0, 0, UserModel::TYPE_MONEY);
    $changeMoneyResult = $receiverDao->changeMoney($money, $userLogType, $userLogMemo, 0, 0, UserModel::TYPE_MONEY);
    $userModel->db->commit();
    Logger::info(sprintf('transfer_money, 成功, payerId:%d,receiverId:%d,money:%s', $payerId, $receiverId, $money));

} catch(\Exception $e) {
    $user->db->rollback();
    Logger::error(sprintf('transfer_money, 失败, payerId:%d,receiverId:%d,money:%s', $payerId, $receiverId, $money));
}

Logger::info(sprintf('transfer_money, end, payerId:%d,receiverId:%d,money:%s', $payerId, $receiverId, $money));

