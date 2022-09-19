<?php
require(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

use core\dao\DealModel;
use core\dao\DealRepayModel;

$deal_id = intval($argv[1]);
$day = intval($argv[2]);
$day = $day ? $day : 1;

$sec = 3600*24*$day;

$deal = DealModel::instance()->find($deal_id);
if (!$deal) {
    echo "deal does not exist\n";
}

// 修改deal_repay表还款时间
$res = $GLOBALS['db']->query("UPDATE ".DB_PREFIX."deal_repay SET `repay_time`=`repay_time`+'{$sec}' WHERE `deal_id`='{$deal_id}'");
if ($res === false) {
    echo "修改还款时间失败\n";
    exit;
} else {
    echo "修改还款时间成功\n";
}

// 修改deal_loan_repay表回款时间
$res = $GLOBALS['db']->query("UPDATE ".DB_PREFIX."deal_loan_repay SET `time`=`time`+'{$sec}' WHERE `deal_id`='{$deal_id}'");
if ($res === false) {
    echo "修改回款时间失败\n";
    exit;
} else {
    echo "修改回款时间成功\n";
}

// deal表还款时间
$deal->repay_start_time = $deal->repay_start_time + $sec;
$deal->last_repay_time = $deal->last_repay_time + $sec;
$deal->next_repay_time = $deal->next_repay_time + $sec;
$res = $deal->save();
if ($res === false) {
    echo "修改deal表开始还款时间、下次还款时间、最后还款时间失败\n";
} else {
    echo "修改deal表还款时间成功\n";
}
