<?php
/**
 * 用户回款记录
 * @author wangjiantong  2017-02-16
 */

require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../libs/common/app.php';
require_once dirname(__FILE__) . '/../libs/common/functions.php';
require_once dirname(__FILE__) . '/../system/libs/msgcenter.php';

use core\dao\DealLoanRepayModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$arg = intval($argv[1]);
$arg2 = intval($argv[2]);

if(isset($argv[1]) && $argv[1] > 0){
    $userId = $argv[1];
}else{
    exit("请指定用户ID\n");
}

if(isset($argv[2])){
    $time = $argv[2];
    $timeArr = explode('-',$argv[2]);
    if(count($timeArr) != 3){
        exit("时间参数错误!格式:2017-01-01 \n");
    }
    $endTime = time(date('Y-m-01', strtotime($argv[2])));
}else{
    $endTime = time(date('Y-m-01', strtotime(date("Y-m-d"))));
}

$title = "投资时间,投资期限,单笔投资金额(元),投资ID,投资产品标题, 还款/付息日期,还款金额,还款类型,项目状态";

$querySql = "SELECT dlr.money as dlr_money,dlr.real_time,dlr.type,d.name,d.loantype,d.repay_time,d.deal_status,dl.money as dl_money,dl.id as dl_id,dl.create_time FROM firstp2p_deal_loan_repay AS dlr LEFT JOIN firstp2p_deal as d ON dlr.deal_id = d.id LEFT JOIN firstp2p_deal_load AS dl ON dlr.deal_loan_id = dl.id WHERE dlr.loan_user_id = ".intval($userId)." AND dlr.`status` = 1 AND dlr.real_time < '".$endTime."';";

$recordContent = "";
$data = DealLoanRepayModel::instance()->findAllBySql($querySql);
foreach($data as $d){
    $dlTime = date('Y-m-d',$d['create_time']);
    if ($d['loantype'] == 5) {
        $repayTime = $d['repay_time'] . "天";
    } else {
        $repayTime = $d['repay_time'] . "个月";
    }
    $loanMoney = $d['dl_money'];
    $loadId = $d['dl_id'];
    $dealName = $d['name'];
    $realRepayTime = date('Y-m-d',$d['real_time']);
    $repayMoney = $d['dlr_money'];
    switch($d['type']){
        case DealLoanRepayModel::MONEY_PRINCIPAL:
            $repayType = "本金";
            break;
        case DealLoanRepayModel::MONEY_INTREST:
            $repayType = "利息";
            break;
        case DealLoanRepayModel::MONEY_PREPAY:
            $repayType = "提前还款本金";
            break;
        case DealLoanRepayModel::MONEY_COMPENSATION:
            $repayType = "提前还款补偿金";
            break;
        case DealLoanRepayModel::MONEY_PREPAY_INTREST:
            $repayType = "提前还款利息";
            break;
        case DealLoanRepayModel::MONEY_COMPOUND_PRINCIPAL:
            $repayType = "利滚利赎回本金";
            break;
        case DealLoanRepayModel::MONEY_COMPOUND_PRINCIPAL:
            $repayType = "利滚利赎回利息";
            break;
        default: $repayType = $d['type'];
    }
    switch($d['deal_status']){
        case 4:
            $dealStatus = "还款中";
            break;
        case 5:
            $dealStatus = "已还清";
            break;
        default: $dealStatus = '';
    }
    $recordLine = "{$dlTime},{$repayTime},{$loanMoney},{$loadId},{$dealName},{$realRepayTime},{$repayMoney},{$repayType},{$dealStatus}";
    $recordContent .= $recordLine."<br />";
}

//发送邮件
FP::import("libs.common.dict");
$email_arr = dict::get("USER_REPAY_RECORD_EMAIL");

if ($email_arr) {
    $title = sprintf("用户%s回款记录", $userId);
    $msgcenter = new msgcenter();
    foreach ($email_arr as $email) {
        $msg_count = $msgcenter->setMsg($email, 0, $record_content, false, $title);
    }
    $msg_save = $msgcenter->save();
    echo 'success';
}
