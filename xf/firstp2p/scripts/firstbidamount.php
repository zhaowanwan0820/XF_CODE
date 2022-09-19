<?php
require(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ERROR);
ini_set('display_errors', 1);
use libs\utils\PaymentApi;
use core\service\RemoteTagService;
use core\dao\DealLoadModel;
$timeStart = strtotime($argv[1]) - 28800;
$timeEnd = strtotime($argv[2]) - 28800;
$sql = "SELECT * FROM firstp2p_deal_load WHERE create_time >= $timeStart AND create_time < $timeEnd";
$result = $GLOBALS['db']->get_slave()->query($sql);
$remoteTagService = new RemoteTagService();
while($result && $data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    $firstDeal = DealLoadModel::instance()->getFirstDealByUser($data['user_id']);
    if ($firstDeal['id'] !== $data['id']) {
        continue;
    }
    $res =  $remoteTagService->addUserTag($data['user_id'], 'FirstBidAmount', $data['money']);
    if ($res) {
        PaymentApi::log('O2O补FirstBidAmount-'. $data['user_id'] . '-' . $data['money']);
    }

}
