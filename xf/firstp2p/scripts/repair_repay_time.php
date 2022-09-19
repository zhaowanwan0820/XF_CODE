<?php
require(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

error_reporting(E_ALL^E_NOTICE);
set_time_limit(0);

use core\dao\DealModel;
use core\dao\DealRepayModel;
use app\models\dao\Deal;

$deal_id = intval($argv[1]);
if (!$deal_id) {
    echo "param error";
    exit;
}

$deal_model = new DealModel();
$deal = $deal_model->find($deal_id);

$loantype = $deal->loantype;
$repay_start_time = $deal->repay_start_time;
$repay_cycle = $deal->getRepayCycle();

$deal_repay_model = new DealRepayModel();
$deal_repay_list = $deal_repay_model->findAll("`deal_id`='{$deal_id}' ORDER BY `repay_time` ASC");

$deal_dao = new Deal();

$i = 0;
foreach ($deal_repay_list as $deal_repay) {
    $i++;
    $repay_time = $deal_dao->getRepayDay($repay_start_time, $repay_cycle, $loantype, $i);
    $deal_repay->repay_time = $repay_time;
    $deal_repay->save();

    $GLOBALS['db']->query("UPDATE firstp2p_deal_loan_repay SET `time`='{$repay_time}' WHERE `deal_id`='{$deal_id}' AND `deal_repay_id`='{$deal_repay->id}'");
}

echo "success\n";
