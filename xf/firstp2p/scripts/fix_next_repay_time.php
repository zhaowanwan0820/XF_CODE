<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/11/9
 * Time: 14:02
 */

require(dirname(__FILE__) . '/../app/init.php');
FP::import('libs.common.functions');
FP::import("app.deal");

$sql = "SELECT * FROM firstp2p_deal WHERE repay_start_time >= unix_timestamp('2015-11-06 00:00:00') - 8 * 3600
        AND repay_start_time <= unix_timestamp('2015-11-09 23:59:59') - 8 * 3600 AND deal_status = 4 AND next_repay_time = 0" ;

$badData = $GLOBALS['db']->getAll($sql);
$count = count($badData);
libs\utils\logger::info("Fix next_repay_time: count={$count}" );
foreach($badData as $bad) {
    libs\utils\logger::info("Fix next_repay_time: " . json_encode($bad));
    //老的错误时间
    $oldNextRepayTime = $bad['next_repay_time'];
    $delta_month_time = get_delta_month_time($bad['loantype'], $bad['repay_time']);
    //修复的时间
    // 按天一次到期
    if($bad['loantype'] == 5){
        $fixedNextRepayTime = next_replay_day_with_delta($bad['repay_start_time'], $delta_month_time);
    }else{
        $fixedNextRepayTime = next_replay_month_with_delta($bad['repay_start_time'], $delta_month_time);
    }
    $where = " `id` = {$bad['id']} AND `next_repay_time` = {$oldNextRepayTime} ";
    $result = $GLOBALS['db']->update("firstp2p_deal", array("next_repay_time"   =>  $fixedNextRepayTime), $where);
    if(!$result && !$GLOBALS['db']->affected_rows()) {
        libs\utils\logger::error("Fix next_repay_time : deal_id={$bad['id']} update next_repay_time failed");
    } else {
        libs\utils\logger::info("Fix next_repay_time: deal_id={$bad['id']} update next_repay_time from old={$oldNextRepayTime} to new={$fixedNextRepayTime}");
    }
}

