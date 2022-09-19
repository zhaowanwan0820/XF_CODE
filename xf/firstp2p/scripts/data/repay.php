<?php

require_once dirname(__FILE__).'/../../app/init.php';

use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\UserModel;
use core\dao\DealLoanRepayModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$file = $argv[1];
if (!$file) {
    echo "file empty";
    exit;
}

$fp = fopen($file, "w+"); 

$title = array("投资记录ID", "成交时间", "投资人ID", "投资人会员名称", "投资人姓名", "投资金额", "借款编号", "借款标题", "上标平台", "借款期限", "年化借款利率", "到期利息", "还款时间");

put_csv($fp, $title);

$time_start = to_timespan("2015-06-04");
$time_end = to_timespan("2015-06-04 09:30:00");
$list = DealLoanRepayModel::instance()->findAll("`type`='2' AND `status`='1' AND `real_time`>='{$time_start}' AND `real_time`<'{$time_end}'");

foreach ($list as $v) {
    $deal_load = DealLoadModel::instance()->find($v['deal_loan_id']);
    $user = UserModel::instance()->find($deal_load['user_id']);
    $deal = DealModel::instance()->find($deal_load['deal_id']);

    $arr = array(
        $v['deal_loan_id'],
        to_date($deal_load['create_time']),
        $deal_load['user_id'],
        $deal_load['user_name'],
        $user['real_name'],
        $deal_load['money'],
        $v['deal_id'],
        $deal['name'],
        get_deal_domain($v['deal_id'], true),
        $deal['repay_time'] . ($deal['loantype'] == 5 ? "天" : "个月"),
        $deal['rate'],
        $v['money'],
        to_date($v['real_time']),
    );

    put_csv($fp, $arr);
}

fclose($fp);
exit;

function put_csv($handle, $arr) {
    foreach ($arr as $k => $v) {
        $arr[$k] = iconv("utf-8", "gbk", $v);
    }
    fputcsv($handle, $arr);
}


