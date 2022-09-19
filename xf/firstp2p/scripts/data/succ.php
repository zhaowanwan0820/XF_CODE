<?php

require_once dirname(__FILE__).'/../../app/init.php';

use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealLoadModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$file = $argv[1];
if (!$file) {
    echo "file empty";
    exit;
}

$fp = fopen($file, "w+"); 

$title = array("投资记录ID", "成交时间", "投资人ID", "投资人会员名称", "投资人姓名", "投资金额", "借款编号", "借款标题", "上标平台", "借款期限", "年化借款利率");

put_csv($fp, $title);

$time_start = to_timespan("2015-06-05");
$time_end = to_timespan("2015-06-07");
$deal_list = DealModel::instance()->findAll("`success_time`>='{$time_start}' AND `success_time`<'{$time_end}'");

foreach ($deal_list as $deal) {
    $arr_deal_load = DealLoadModel::instance()->findAll("`deal_id`='{$deal['id']}'");
    foreach ($arr_deal_load as $v) {
        $user = UserModel::instance()->find($v['user_id']);
        $arr = array(
            $v['id'],
            to_date($v['create_time']),
            $v['user_id'],
            $v['user_name'],
            $user['real_name'],
            $v['money'],
            $v['deal_id'],
            $deal['name'],
            get_deal_domain($v['deal_id'], true),
            $deal['repay_time'] . ($deal['loantype'] == 5 ? "天" : "个月"),
            $deal['rate'],
        );

        put_csv($fp, $arr);
    }
}

fclose($fp);
exit;

function put_csv($handle, $arr) {
    foreach ($arr as $k => $v) {
        $arr[$k] = iconv("utf-8", "gbk", $v);
    }
    fputcsv($handle, $arr);
}


