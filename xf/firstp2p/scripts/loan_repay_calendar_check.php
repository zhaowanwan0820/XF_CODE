<?php
/**
 * 对loan_repay 日历表进行校验
 * User: jinhaidong
 * Date: 2016-5-24 16:03:27
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\UserLoanRepayStatisticsService;
use core\dao\DealLoanRepayCalendarModel;

class LoanRepayCalendarCheck {
    public function run() {
        global $argv;
        $beginId = isset($argv[1]) ? $argv[1] : 0;
        $endId = isset($argv[2]) ? $argv[2] : 0;
        $isReset = isset($argv[3]) ? $argv[3] : 0;
        $this->checkCalendar($beginId,$endId,$isReset);
    }


    public function checkCalendar($beginId,$endId,$isReset) {
        while(true){
            $sql = "SELECT * FROM `firstp2p_user_loan_repay_statistics` where id >$beginId and id < $endId ORDER  BY id ASC limit 1000";
            $rows = $GLOBALS['db']->get_slave()->getAll($sql);
            if(!$rows) {
                echo "check finish beginId:".$beginId;
                exit;
            }
            foreach($rows as $row) {
                $tmpSql = "SELECT sum(money) as money FROM `firstp2p_deal_loan_repay` WHERE loan_user_id=".$row['user_id']." and status = 0 AND type=8 AND time=0";
                $norepayCompound = $GLOBALS['db']->get_slave()->getOne($tmpSql);

                $remain_principal = $row['norepay_principal']; // 待收本金
                if($norepayCompound && $norepayCompound > 0) { // 待收本金去除通知贷未赎回的
                    $remain_principal-=$norepayCompound;
                }

                $remain_interest  = $row['norepay_interest']; // 待收利息
                $total_interest = \libs\utils\Finance::addition(array($row['load_earnings'], $row['load_tq_impose'], $row['load_yq_impose']), 2); // 累计收益

                $calendarInfo = $this->getUserCalendar($row['user_id']);

                $beginId = $row['id'];
                if(bccomp($remain_principal,$calendarInfo['norepay_principal'],2) == 0
                    && bccomp($remain_interest,$calendarInfo['norepay_interest'],2) == 0
                    && bccomp($total_interest,$calendarInfo['total_interest'],2) == 0) {
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "calendar check success id:{$beginId} uid:".$row['user_id'])));
                    continue;
                }else{
                    $dataInfo = array(
                        'assetInfo' =>array('remain_principal'=>$remain_principal,'remain_interest'=>$remain_interest,'total_interest'=>$total_interest),
                        'calInfo' => $calendarInfo,
                    );
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "calendar check fail uid:".$row['user_id']." assetInfo:".json_encode($dataInfo))));
                    if($isReset) {
                        $this->resetCalendarByUserId($row['user_id']);
                    }
                    continue;
                }
            }
        }
    }

    public function getUserCalendar($uid) {
        $tables = array(
            'firstp2p_deal_loan_repay_calendar_2013',
            'firstp2p_deal_loan_repay_calendar_2014',
            'firstp2p_deal_loan_repay_calendar_2015',
            'firstp2p_deal_loan_repay_calendar_2016',
            'firstp2p_deal_loan_repay_calendar_2017',
            'firstp2p_deal_loan_repay_calendar_2018',
            'firstp2p_deal_loan_repay_calendar_2019',
        );
        $data = array(
            'norepay_principal' => 0, // 待还本金
            'norepay_interest' => 0, // 待还利息
            'total_interest' => 0, // 总收益(不含贴息)
        );
        foreach($tables as $table) {
            $sql = "SELECT sum(norepay_principal) as norepay_principal,
                sum(norepay_interest) as norepay_interest,
                sum(repay_interest) as repay_interest,sum(prepay_interest) as prepay_interest FROM {$table} WHERE  user_id={$uid}";
            $row = $GLOBALS['db']->get_slave()->getRow($sql);
            $data['norepay_principal']= bcadd($data['norepay_principal'],$row['norepay_principal'],2);
            $data['norepay_interest']=bcadd($data['norepay_interest'],$row['norepay_interest'],2);
            $data['total_interest']= \libs\utils\Finance::addition(array($data['total_interest'],$row['repay_interest'],$row['prepay_interest']), 2);
        }
        return $data;
    }

    public function resetCalendarByUserId($uid) {
        $years = array(2013,2014,2015,2016,2017,2018,2019);
        foreach($years as $year) {
            for($i=1;$i<=12;$i++) {
                $res = DealLoanRepayCalendarModel::instance()->initDealLoanRepayCalendarByMonth($uid,$year,$i,true);
                if($res === false) {
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "reset loan_repay calendar fail uid:{$uid},year:{$year},month:{$i}")));
                    break 2;
                }
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "reset loan_repay calendar success uid:{$uid},year:{$year},month:{$i}")));
            }
        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new LoanRepayCalendarCheck();
$obj->run();