<?php
/**
 * 修复提前还款取消导致的用户回款日历变为负值的问题
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;


$year = isset($argv[1]) ? trim($argv[1]) : 0;
$month = isset($argv[2]) ? trim($argv[2]) : 0;
$day = isset($argv[3]) ? trim($argv[3]) : 0;
$userId = isset($argv[4]) ? trim($argv[4]) : 0;

if(!in_array($year,array(2017,2018,2019,2020,2021))){
    die('Params year error');
}

$sql = "SELECT * FROM firstp2p_deal_loan_repay_calendar_".$year." WHERE (norepay_principal <0 or norepay_interest <0) ";
if($month){
    $sql.="  AND repay_month = ".$month;
}
if($day){
    $sql.=" AND repay_day=".$day;
}
if($userId){
    $sql.=" AND user_id=".$userId;
}

$result = Db::getInstance('firstp2p')->getAll($sql);

foreach($result as $row){
    $uid = $row['user_id'];
    $date = $year . "-" . $row['repay_month'] . "-" . $row['repay_day'];
    $time = to_timespan($date);
    $tmpSql = "SELECT * FROM `firstp2p_deal_loan_repay` WHERE loan_user_id=".$uid." AND time=".$time." AND status = 2";
    $tmpResult = Db::getInstance('firstp2p')->getAll($tmpSql);


    $needRepayPrincipal = $row['norepay_principal'];
    $needRepayInterest = $row['norepay_interest'];

    $dealService = new \core\service\DealService();
    $updatePrincipal = $updateInterest = 0;
    foreach ($tmpResult as $tmpRow){
        if(!$dealService->isDealDT($tmpRow['deal_id'])){
            Logger::info("fix calendar_duotou is not dtb dealId:".$tmpRow['deal_id']);
            continue;
        }
        if($tmpRow['type'] == 1){
            $updatePrincipal=bcadd($updatePrincipal,$tmpRow['money'],2);
        }
        if($tmpRow['type'] == 2){
            $updateInterest = bcadd($updateInterest,$tmpRow['money'],2);
        }
    }

    $sumPrincipal = bcadd($needRepayPrincipal , $updatePrincipal,2);
    $sumInterest = bcadd($needRepayInterest,$updateInterest,2);

    if($sumPrincipal <0 || $sumInterest <0){
        Logger::error("fix calendar_duotou money not enough year:{$year}  calendar_id:".$row['id']);
        continue;
    }

    if($row['id'] && ($updatePrincipal >0 || $updateInterest >0)){
        $updateSql = "update firstp2p_deal_loan_repay_calendar_".$year." set norepay_principal = norepay_principal+ ".$updatePrincipal.",norepay_interest = norepay_interest+".$updateInterest." where id=".$row['id'];
        $updateRes = Db::getInstance('firstp2p')->query($updateSql);
        Logger::info("fix calendar_duotou sql:".$updateSql);
        if(Db::getInstance('firstp2p')->affected_rows() > 0){
            Logger::info("fix calendar_duotou succ result:".implode(",",$row));
        }else{
            Logger::info("fix calendar_duotou err result:".implode(",",$row));
        }
    }
}