<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/11/17
 * Time: 18:06
 */

//修复2015年11月06-2015年11月09 repay_start_time时间错误的数据。

require_once(dirname(__FILE__) . '/../app/init.php');
FP::import('libs.common.functions');
FP::import("app.deal");

//date_default_timezone_set("Asia/Shanghai"); // 设置时间为北京时间

function mylog($msg) {
    $msg .= "\n";
    file_put_contents("/tmp/fix_repay_start_time.log", $msg, FILE_APPEND | LOCK_EX);
}

mylog("start");
//选出repay_start_time 错误的标
//线上
$sql = "select * from firstp2p_deal where repay_start_time % 3600 !=0 and hour(from_unixtime(repay_start_time)) != 16 and repay_start_time >= unix_timestamp('2015-11-05 00:00:00') AND repay_start_time <= unix_timestamp('2015-11-10 00:00:00') order by id desc" ;
//测试
//$sql = "select * from firstp2p_deal where repay_start_time % 3600 !=0 and hour(from_unixtime(repay_start_time)) != 16  AND repay_start_time <= unix_timestamp('2015-11-10 00:00:00') order by id desc" ;
mylog($sql);
$badDeals = $GLOBALS['db']->getAll($sql);
$count = count($badDeals);
mylog("Fix repay_start_time: deal count={$count}" );
foreach($badDeals as $badDeal) {
    mylog("Fix repay_start_time: deal_info = " . json_encode($badDeal));
    $GLOBALS['db']->startTrans();
    try{
        //老的错误时间
        $oldRepayStartTime = $badDeal['repay_start_time'];
        $date = to_date($oldRepayStartTime, "Y-m-d");
        $fixedRepayStartTime = to_timespan($date);

        $where = " `id` = {$badDeal['id']} AND `repay_start_time` = {$oldRepayStartTime} ";
        $result = $GLOBALS['db']->update("firstp2p_deal", array("repay_start_time"   =>  $fixedRepayStartTime), $where);
        $affected_rows = $GLOBALS['db']->affected_rows();
        if(!$result || $GLOBALS['db']->affected_rows()<= 0) {
            throw new \Exception("Fix repay_start_time: deal_id={$badDeal['id']} update repay_start_time failed");
        } else {
            mylog("Fix repay_start_time: deal_id={$badDeal['id']} update firstp2p_deal.repay_start_time from old={$oldRepayStartTime} to new={$fixedRepayStartTime}");
        }
        // 选出repay_start_time 错误的firstp2p_deal_loan_repay。
        $sql = "SELECT * FROM firstp2p_deal_loan_repay where deal_id = {$badDeal['id']} order by `id` desc";
        mylog($sql);
        $dealLoanRepayList = $GLOBALS['db']->getAll($sql);
        foreach($dealLoanRepayList as $dealLoanRepay) {
            mylog("Fix repay_start_time: deal_loan_repay_info = " . json_encode($dealLoanRepay));
            // deal_loan_repay time
            $oldTime = $dealLoanRepay['time'];
            if($oldTime % 3600 == 0 && date("H", $oldTime) == 16) {//是整点，继续
                continue;
            }
            $date = to_date($oldTime, "Y-m-d");
            $fixedTime = to_timespan($date);

            $where = " `id` = {$dealLoanRepay['id']} AND `deal_id` = {$badDeal['id']} AND `time` = {$dealLoanRepay['time']} ";
            $result = $GLOBALS['db']->update("firstp2p_deal_loan_repay" , array("time"  => $fixedTime), $where);
            $affected_rows = $GLOBALS['db']->affected_rows();
            if(!$result || $GLOBALS['db']->affected_rows() <= 0) {
                throw new \Exception("Fix repay_start_time: deal_loan_repay={$dealLoanRepay['id']} update time failed");
            } else {
                mylog("Fix repay_start_time: deal_loan_repay={$dealLoanRepay['id']} update firstp2p_deal_loan_repay.time from old={$oldTime} to new={$fixedTime}");
            }
        }
        $GLOBALS['db']->commit();
    }catch (\Exception $e) {
        $GLOBALS['db']->rollback();
        mylog("Fix repay_start_time: " . $e->getMessage());
    }
    mylog("Fix repay_start_time: deal_id={$badDeal['id']} finished");
}

//选出标修正了，但是没有修正过的firstp2p_deal_loan_repay，create_time修正10月10号之前的记录
// id            create_time        create_time
//186263750 	1444603934 	    2015-10-12 06:52:14
//线上
$baseId = 186263750; //基数的id
$startId = $baseId;  //开始的id
$stopId = 246263750;  //结束的id
//测试数据
//$baseId = 0;
//$startId = $baseId;
//$stopId = 2222900;
$pageSize = 1000;  //每次读取的记录
for($i = 0; $startId < $stopId; $i++) {
    $startId = $baseId + $i * $pageSize;
    $endId = $baseId + ($i + 1) * $pageSize;
    //type = 6/8/9的才会依赖repay_start_time
    $sql = "select * from firstp2p_deal_loan_repay WHERE `id` >= {$startId} AND `id` <= {$endId} AND `type` in (6, 8, 9) ";
    mylog($sql);
    $badDealLoanRepayList = $GLOBALS['db']->getAll($sql);
    $count = count($badDealLoanRepayList);
    mylog("Fix repay_start_time: deal_loan_repay count = " . $count);
    foreach($badDealLoanRepayList as $dealLoanRepay) {
        //老的时间
        $oldTime = $dealLoanRepay['time'];
        if(($oldTime % 3600 == 0 && date("H", $oldTime) == 16) || $oldTime == 0 ) {//是整点，对的数据，不修了
            continue;
        }
        mylog("Fix repay_start_time: deal_loan_repay_with_fix_deal = " . json_encode($dealLoanRepay));
        $date = to_date($oldTime, "Y-m-d");
        //新的时间
        $fixedTime = to_timespan($date);

        $where = " `id` = {$dealLoanRepay['id']} AND `time` = {$dealLoanRepay['time']} ";
        $result = $GLOBALS['db']->update("firstp2p_deal_loan_repay" , array("time"  => $fixedTime), $where);
        if(!$result || $GLOBALS['db']->affected_rows() <= 0) {
            mylog("Fix repay_start_time: deal_loan_repay={$dealLoanRepay['id']} update time failed");
        } else {
            mylog("Fix repay_start_time: deal_loan_repay={$dealLoanRepay['id']} update firstp2p_deal_loan_repay.time from old={$oldTime} to new={$fixedTime}");
        }
    };
}

mylog("end");
