<?php
/**
 * 更改借款期限 
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

require(dirname(__FILE__).'/../app/init.php');

SiteApp::init();

use app\models\dao\Deal;
use app\models\dao\DealRepay;
use app\models\dao\DealLoanRepay;

//145
$deals = Deal::instance()->findAll("id in (673,711,712,713,714,715,716)");
foreach ($deals as $deal) {
    $deal->repay_time = 145;
    $deal->save();
    $repay_time = $deal->repay_start_time; //中间变量，保存各期还款时间
    $repay_cycle = $deal->getRepayCycle();
    $repay_times = $deal->getRepayTimes();
    $repay_time = $deal->nextRepayDay($repay_time, $repay_cycle, $deal->loantype);

    $repays = DealRepay::instance()->findAll("deal_id = $deal->id");
    foreach ($repays as $repay) {
        $repay->repay_time = $repay_time;
        $repay->save();
    }

    $loan_repays = DealLoanRepay::instance()->findAll("deal_id = $deal->id"); 
    foreach ($loan_repays as $loan_repay) {
        $loan_repay->time = $repay_time;
        $loan_repay->save();
    }
}

//149
$deals = Deal::instance()->findAll("id in (672,689)");
foreach ($deals as $deal) {
    $deal->repay_time = 149;
    $deal->save();
    $repay_time = $deal->repay_start_time; //中间变量，保存各期还款时间
    $repay_cycle = $deal->getRepayCycle();
    $repay_times = $deal->getRepayTimes();
    $repay_time = $deal->nextRepayDay($repay_time, $repay_cycle, $deal->loantype);

    $repays = DealRepay::instance()->findAll("deal_id = $deal->id");
    foreach ($repays as $repay) {
        $repay->repay_time = $repay_time;
        $repay->save();
    }

    $loan_repays = DealLoanRepay::instance()->findAll("deal_id = $deal->id"); 
    foreach ($loan_repays as $loan_repay) {
        $loan_repay->time = $repay_time;
        $loan_repay->save();
    }
}
//153
$deals = Deal::instance()->findAll("id in (674)");
foreach ($deals as $deal) {
    $deal->repay_time = 153;
    $deal->save();
    $repay_time = $deal->repay_start_time; //中间变量，保存各期还款时间
    $repay_cycle = $deal->getRepayCycle();
    $repay_times = $deal->getRepayTimes();
    $repay_time = $deal->nextRepayDay($repay_time, $repay_cycle, $deal->loantype);

    $repays = DealRepay::instance()->findAll("deal_id = $deal->id");
    foreach ($repays as $repay) {
        $repay->repay_time = $repay_time;
        $repay->save();
    }

    $loan_repays = DealLoanRepay::instance()->findAll("deal_id = $deal->id"); 
    foreach ($loan_repays as $loan_repay) {
        $loan_repay->time = $repay_time;
        $loan_repay->save();
    }
}
