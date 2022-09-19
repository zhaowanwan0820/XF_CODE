<?php
/**
 * @desc 余额划转补偿脚本
 * User:  wangqunqiagn
 * Date: 2017-9-27
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\SupervisionFinanceService;
use core\service\SupervisionBaseService;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;
use libs\utils\Alarm;
use libs\utils\PaymentApi;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$db = \libs\db\Db::getInstance('firstp2p', 'master');

$maxId = $db->getOne("SELECT id FROM firstp2p_supervision_transfer ORDER BY id DESC LIMIT 1");

// 一天20万笔划转
$minId = $maxId - 200000;

// 10分钟之前的余额划转订单
$create30MinutesBefore = time() - 600;

$sql = "SELECT id,out_order_id,amount,direction FROM firstp2p_supervision_transfer WHERE id > '{$minId}' AND transfer_status = 0  AND create_time <= $create30MinutesBefore";

$data = $db->getAll($sql);
$supervisionFinanceService = new SupervisionFinanceService();

foreach ($data as $record) {
    try {
        $db->startTrans();
        // 查询支付结果
        $result = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->request('orderSearch', ['orderId' => $record['out_order_id']]);
        if (!isset($result['respCode'])) {
            throw new \Exception('存管查找订单失败。');
        }
        // 订单不存在, 当成失败订单处理
        if (!isset($result['status']) && !empty($result['respSubCode']) && $result['respSubCode'] == '200005') {
            $result['data']['status'] = 'F';
            $result['data']['amount'] = $record['amount'];
            $result['data']['outOrderId'] = $record['out_order_id'];
        } else {
            $result['data']['orderId'] = $result['orderId'];
            $result['data']['status'] = $result['status'];
            $result['data']['amount'] = $result['amount'];
        }
        // 订单存在
        $result = $supervisionFinanceService->superRechargeNotify($record['out_order_id'], $record['direction'], $result);
        if (isset($result['respCode']) && $result['status'] != SupervisionBaseService::RESPONSE_SUCCESS) {
            throw new \Exception('数据库记录更新失败。');
        }
        $db->commit();
    } catch(\Exception $e) {
        $db->rollback();
        Alarm::push('supervision', '余额划转补单失败 订单Id:'.$record['out_order_id'].' 原因:'.$e->getMessage());
    }

}




