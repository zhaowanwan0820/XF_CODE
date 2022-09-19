<?php
/**
 * 首山掌众资产监控邮件
 * @author wangjiantong  2015-08-07
 */

require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../libs/common/app.php';
require_once dirname(__FILE__) . '/../libs/common/functions.php';
require_once dirname(__FILE__) . '/../system/libs/msgcenter.php';

use core\dao\DealModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$dealModel = new DealModel();

$startTime = mktime(0,0,0,date('m'),date('d'),date('Y'));-28800;
$endTime = mktime(0,0,0,date('m',strtotime("+1 day")),date('d',strtotime("+1 day")),date('Y',strtotime("+1 day")))-28800;

//获取首山当日上标资产
$ssSql = "SELECT count(d.id) as deal_count ,round(sum(d.borrow_amount),2) as money_total FROM `firstp2p_deal` as d WHERE d.advisory_id = 153 AND d.start_time >= ".$startTime." AND d.start_time <= ".$endTime.";";
$ss = $dealModel->findBySql($ssSql);

//获取首山当日放款资产
$ssLoanSql = "SELECT count(d.id) as deal_count ,round(sum(d.borrow_amount),2) as money_total FROM `firstp2p_deal` as d WHERE d.advisory_id = 153 AND d.id in (SELECT deal_id FROM firstp2p_loan_oplog as o WHERE o.op_time >= ".$startTime." AND o.op_time <= ".$endTime.");";
$ssLoan = $dealModel->findBySql($ssLoanSql);

//获取掌众当日上标资产
$zzSql = "SELECT count(d.id) as deal_count ,round(sum(d.borrow_amount),2) as money_total FROM `firstp2p_deal` as d WHERE d.type_id = 34 AND d.start_time >= ".$startTime." AND d.start_time <= ".$endTime.";";
$zz = $dealModel->findBySql($zzSql);

//获取掌众当日放款资产
$zzLoanSql = "SELECT count(d.id),round(sum(d.borrow_amount),2) FROM `firstp2p_deal` as d WHERE d.type_id = 34 AND d.id in (SELECT deal_id FROM firstp2p_loan_oplog as o WHERE o.op_time >= ".$startTime." AND o.op_time <= ".$endTime.");";
$zzLoan = $dealModel->findBySql($zzSql);

$mailContent = "首山当日上标项目: ".$ss['deal_count']."<br />";
$mailContent .= "首山当日上标金额: ".$ss['money_total']."<br />";
$mailContent .= "首山当日放款项目: ".$ssLoan['deal_count']."<br />";
$mailContent .= "首山当日放款金额: ".$ssLoan['money_total']."<br />";
$mailContent .= "掌众当日上标项目: ".$zz['deal_count']."<br />";
$mailContent .= "掌众当日上标金额: ".$zz['money_total']."<br />";
$mailContent .= "掌众当日放款项目: ".$zzLoan['deal_count']."<br />";
$mailContent .= "掌众当日放款金额: ".$zzLoan['money_total']."<br />";

//发送邮件
FP::import("libs.common.dict");
$email_arr = dict::get("OVERSOLD_MONITOR_EMAIL");

if ($email_arr) {
    $title = sprintf("网信理财 首山,掌众项目监控数据", date("Y年m月d日", time()));
    $msgcenter = new msgcenter();
    foreach ($email_arr as $email) {
        $msg_count = $msgcenter->setMsg($email, 0, $mailContent, false, $title);
    }
    $msg_save = $msgcenter->save();
    echo 'success';
}


