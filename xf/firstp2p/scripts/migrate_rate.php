<?php
/**
 * 将借款手续费、借款担保费、顾问利率从区间利率调整为年化利率 
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

require(dirname(__FILE__).'/../app/init.php');

SiteApp::init();

use app\models\dao\Deal;

$deals = Deal::instance()->findAll();
foreach ($deals as $deal) {
    $deal->loan_fee_rate = convertRate($deal->loantype, $deal->loan_fee_rate, $deal->repay_time);//期间借款手续费
    $deal->guarantee_fee_rate = convertRate($deal->loantype, $deal->guarantee_fee_rate, $deal->repay_time);//期间借款担保费
    $deal->advisor_fee_rate = convertRate($deal->loantype, $deal->advisor_fee_rate, $deal->repay_time);//期间顾问利率
    $deal->save();
}

function convertRate($repay_mode, $rate, $period) {
    $period = $period >= 0 ? $period : 0;
    if($repay_mode == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']){
        return $rate / $period * 365;
    } else {
        return $rate / $period * 12;
    }
}
