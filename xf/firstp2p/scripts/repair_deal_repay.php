<?php
/**
 * 生成合同
 */
require_once dirname(__FILE__) . '/../app/init.php';
use core\dao\DealLoanRepayModel;
use core\dao\DealRepayModel;
use core\dao\DealModel;
use core\dao\DealExtModel;
use libs\utils\Logger;

ini_set('memory_limit', '1024M');
set_time_limit(0);

$deal_id = isset($argv[1]) ? intval($argv[1]) : 0;
if($deal_id <= 0){
    exit('id错误');
}

$GLOBALS['db']->query("DELETE FROM firstp2p_deal_repay WHERE `deal_id`='{$deal_id}'");

$dlr_model = new DealLoanRepayModel();
$deal = DealModel::instance()->find($deal_id);

$deal_ext = DealExtModel::instance()->getDealExtByDealId($deal_id);
if ($deal_ext['loan_fee_ext']) {
    $loan_fee_arr = json_decode($deal_ext['loan_fee_ext'], true);
    array_shift($loan_fee_arr);
}
if ($deal_ext['consult_fee_ext']) {
    $consult_fee_arr = json_decode($deal_ext['consult_fee_ext'], true);
    array_shift($consult_fee_arr);
}
if ($deal_ext['guarantee_fee_ext']) {
    $guarantee_fee_arr = json_decode($deal_ext['guarantee_fee_ext'], true);
    array_shift($guarantee_fee_arr);
}

$arr_dlr = $dlr_model->findAll("`deal_id`='{$deal_id}' ORDER BY `deal_repay_id`", true);
$result = array();

foreach ($arr_dlr as $k => $v) {
    $repay_id = $v['deal_repay_id'];
    if ($v['type'] == 1) {
        $result[$repay_id]['principal'] += $v['money'];
        $result[$repay_id]['repay_money'] += $v['money'];
    } elseif($v['type'] == 2) {
        $result[$repay_id]['interest'] += $v['money'];
        $result[$repay_id]['repay_money'] += $v['money'];
    } elseif($v['type'] == 6) {
        $result[$repay_id]['repay_money'] += $v['money'];
    }
    $result[$repay_id]['repay_time'] = $v['time'];
}

ksort($result);

$GLOBALS['db']->startTrans();

try {
    foreach ($result as $k => $v) {
        $loan_fee = array_shift($loan_fee_arr); 
        $consult_fee = array_shift($consult_fee_arr);
        $guarantee_fee = array_shift($guarantee_fee_arr);

        $service_fee = $loan_fee+$consult_fee+$guarantee_fee;       

        $deal_repay = new DealRepayModel();
        $deal_repay->id = $k;
        $deal_repay->deal_id = $deal_id;
        $deal_repay->user_id = $deal['user_id'];
        $deal_repay->repay_money = bcadd($v['repay_money'], $service_fee, 2);
        $deal_repay->principal = $v['principal'];
        $deal_repay->interest = $v['interest'];
        $deal_repay->repay_time = $v['repay_time'];
        $deal_repay->loan_fee = $loan_fee;
        $deal_repay->consult_fee = $consult_fee;
        $deal_repay->guarantee_fee = $guarantee_fee;
        $deal_repay->create_time = get_gmtime();
        $deal_repay->update_time = get_gmtime();
 
        $r = $deal_repay->save();
        if ($r === false) {
            throw new \Exception('insert fail');
        }
    }

    $GLOBALS['db']->commit();
} catch (\Exception $e) {
    $GLOBALS['db']->rollback();
    echo $e->getMessage() . '\n';
}

