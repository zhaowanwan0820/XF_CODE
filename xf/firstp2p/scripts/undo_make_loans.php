<?php
require_once dirname(__FILE__).'/../app/init.php';
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\UserModel;
use core\dao\DealAgencyModel;
use libs\utils\Finance;
use core\dao\FinanceQueueModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$deal_id = intval($argv[1]);
if (!$deal_id) {
    echo "param error";
    exit;
}

$deal = DealModel::instance()->find($deal_id);
$deal_agency_dao = new DealAgencyModel();
$user_dao = new UserModel();
$syncRemoteData = array();

// 咨询机构
$advisory_info = $deal_agency_dao->getDealAgencyById($deal['advisory_id']); // 咨询机构
// 担保机构
$agency_info = $deal_agency_dao->getDealAgencyById($deal['agency_id']); // 担保机构

$consult_user_id = $advisory_info['user_id']; // 咨询机构账户
$guarantee_user_id = $agency_info['user_id'];

$GLOBALS['db']->startTrans();

try{

    $loan_list = DealLoadModel::instance()->findAll("`deal_id`='{$deal_id}' ORDER BY `id` ASC");
    foreach ($loan_list as $k => $v) {
        $user = $user_dao->find($v['user_id']);
        if (bccomp($v['money'], '0.00', 2) > 0) {
            $syncRemoteData[] = array(
                'outOrderId' => 'UNDO_DEAL_LOAN|' . $deal_id,
                'payerId' => $deal['user_id'],
                'receiverId' => $v['user_id'],
                'repaymentAmount' => bcmul($v['money'], 100), // 以分为单位
                'curType' => 'CNY',
                'bizType' => 1,
                'batchId' => $deal_id,
            );
        }

        $user->changeMoney(-$v['money'], '系统冻结余额修正', "编号{$deal_id} {$deal['name']}，单号{$v['id']}", 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);

    }

    $loan_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'], false);
    $loan_fee = floorfix($deal['borrow_amount'] * $loan_fee_rate / 100.0);

    if (bccomp($loan_fee, '0.00', 2) > 0) {
        $syncRemoteData[] = array(
            'outOrderId' => 'UNDO_LOAN_FEE|' . $deal_id,
            'payerId' => app_conf('DEAL_CONSULT_FEE_USER_ID'),
            'receiverId' => $deal['user_id'],
            'repaymentAmount' => bcmul($loan_fee, 100), // 以分为单位
            'curType' => 'CNY',
            'bizType' => 1,
            'batchId' => $deal_id,
        );
    }

    $consult_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'], false);
    $consult_fee = floorfix($deal['borrow_amount'] * $consult_fee_rate / 100.0);

    if (bccomp($consult_fee, '0.00', 2) > 0) {
        $syncRemoteData[] = array(
            'outOrderId' => 'UNDO_CONSULT_FEE' . $deal_id,
            'payerId' => $consult_user_id,
            'receiverId' => $deal['user_id'],
            'repaymentAmount' => bcmul($consult_fee, 100), // 以分为单位
            'curType' => 'CNY',
            'bizType' => 1,
            'batchId' => $deal_id,
        );
    }

    $guarantee_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'], false);
    $guarantee_fee = floorfix($deal['borrow_amount'] * $guarantee_fee_rate / 100.0);

    if (bccomp($guarantee_fee, '0.00',2) > 0) {
        $syncRemoteData[] = array(
            'outOrderId' => 'UNDO_GUARANTEE_FEE|' . $deal_id,
            'payerId' => $guarantee_user_id,
            'receiverId' => $deal['user_id'],
            'repaymentAmount' => bcmul($guarantee_fee, 100), // 以分为单位
            'curType' => 'CNY',
            'bizType' => 1,
            'batchId' => $deal_id,
        );
    }

    $note = "编号{$deal_id} {$deal['name']}";

    $money = $deal['borrow_amount'] - $loan_fee - $consult_fee - $guarantee_fee;
    $user = $user_dao->find($deal['user_id']);
    $user->changeMoney(-$money, '系统余额修正', $note, 0);

    //平台手续费
    $user = $user_dao->find(app_conf('DEAL_CONSULT_FEE_USER_ID'));
    $user->changeMoney(-$loan_fee, '系统余额修正', $note, 0);

    //咨询费
    $user = $user_dao->find($consult_user_id);
    $user->changeMoney(-$consult_fee, '系统余额修正', $note, 0);

    //担保费
    $user = $user_dao->find($guarantee_user_id);
    $user->changeMoney(-$guarantee_fee, '系统余额修正', $note, 0);

    if (!empty($syncRemoteData)) {
        FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
    }

    $GLOBALS['db']->commit();
    echo "succ\n";

} catch (\Exception $e) {

    $GLOBALS['db']->rollback();
    echo "fail\n";

}

function floorfix($value, $precision = 2) {
    $t = pow(10, $precision);
    if (!$t) {
        return 0;
    }
    $value = round($value*$t, 5);
    return (float)floor($value) / $t;
}

