<?php

require(dirname(__FILE__) . '/../app/init.php');
ini_set('error_reporting', E_ERROR);
use core\dao\DealLoadModel;
use core\service\RemoteTagService;
use libs\utils\PaymentApi;

$remoteTagService = new RemoteTagService();
// 获取该用户的投标金额和使用的邀请码
$dateTimeStart = $argv[1];
if (empty($dateTimeStart)) {
    die('时间点不能为空'. PHP_EOL);
}
$condition = " create_time >= UNIX_TIMESTAMP('$dateTimeStart') - 28800";
$dateTimeEnd = $argv[2];
if (!empty($dateTimeEnd)) {
    $condition .= " AND create_time <= UNIX_TIMESTAMP('$dateTimeEnd') - 28800";
}

$sql = "SELECT id, user_id, money FROM firstp2p_deal_load WHERE $condition";
$result = $GLOBALS['db']->get_slave()->query($sql);
while($result && $data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    $firstDeal = DealLoadModel::instance()->getFirstDealByUser($data['user_id']);
    if ($firstDeal['id'] == $data['id']) {
        $res =  $remoteTagService->addUserTag($data['user_id'], 'FirstBidAmount', $data['money']);
        $logInfo = implode('|', $data);
        echo "ADD_FIRSTAMOUNT_TAG|$logInfo" . PHP_EOL;
    }
}
