<?php
require(dirname(__FILE__) . '/../app/init.php');
require(APP_ROOT_PATH.'libs/utils/PhalconRPCInject.php');
use core\service\O2OService;
use libs\utils\Alarm;
use core\dao\OtoAcquireLogModel;
use core\service\BonusService;
use libs\utils\PaymentApi;
error_reporting(E_ERROR);
ini_set('display_errors', 1);
\libs\utils\PhalconRpcInject::init();

$o2oService = new O2OService();
$mode = $argv[1];
$timeStart = strtotime($argv[2]);
$timeEnd = strtotime($argv[3]);
$sql = "SELECT * FROM firstp2p_oto_acquire_log WHERE gift_id = 0";
$requestStatus = OtoAcquireLogModel::REQUEST_STATUS_INIT;
if ($mode == 'empty') {
    $requestStatus = OtoAcquireLogModel::REQUEST_STATUS_EMPTY;
} else if ($mode == 'init') {
    $requestStatus = OtoAcquireLogModel::REQUEST_STATUS_INIT;
}
$sql .= ' AND request_status = ' . $requestStatus;
$sql .= ' AND create_time >= '. $timeStart . ' AND create_time <=' . $timeEnd;

$result = $GLOBALS['db']->get_slave()->query($sql);
while($result && $data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    $logstr = $data['id'] .'-'. $data['user_id'] .'-'. $data['trigger_mode'] .'-'. $data['deal_load_id'];
    // 补发O2O券
    try {
        $res = $o2oService->getCouponGroupList($data['user_id'], $data['trigger_mode'], $data['deal_load_id']);
        if (!empty($res)) {
            PaymentApi::log('O2O补单-补单成功-' . $logstr);
        } else {
            PaymentApi::log('O2O补单-无须补单-'.$logstr);
        }
    } catch (\Exception $e) {
        PaymentApi::log('O2O补单-补单异常-' . $logstr);
    }
}
