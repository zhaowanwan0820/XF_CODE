<?php
namespace core\dao;

use core\dao\DealLoanRepayModel;
use libs\db\MysqlDb;

class DealLoanRepayCalendarModel extends BaseModel {

    /**
     * 更新loan_repay 的汇总数据
     * @param $uid
     * @param $repayTime
     * @param $moneyInfo
     * @return bool|resource
     */
    public function updateDealLoanRepayCalendar($uid,$repayTime,$moneyInfo) {
        $time = time();
        $fields = array(
            'norepay_interest' => isset($moneyInfo['norepay_interest']) ? $moneyInfo['norepay_interest'] : 0, // 待还利息
            'repay_interest' => isset($moneyInfo['repay_interest']) ? $moneyInfo['repay_interest'] : 0,      // 已还利息
            'norepay_principal' => isset($moneyInfo['norepay_principal']) ? $moneyInfo['norepay_principal'] : 0, // 待还本金
            'repay_principal' => isset($moneyInfo['repay_principal']) ? $moneyInfo['repay_principal'] : 0,  // 已还本金
            'prepay_principal' => isset($moneyInfo['prepay_principal']) ? $moneyInfo['prepay_principal'] : 0, // 提前还款本金
            'prepay_interest' => isset($moneyInfo['prepay_interest']) ? $moneyInfo['prepay_interest'] : 0, // 提前还款利息
        );

        $month = date('n',$repayTime);
        $day = date('j',$repayTime);

        $table = $this->getTableNameByTime($repayTime);
        $sql = "INSERT INTO `{$table}` (user_id,repay_month,repay_day,norepay_interest,repay_interest,norepay_principal,repay_principal,prepay_principal,prepay_interest,create_time,update_time) VALUES ";
        $sql.=" ({$uid},{$month},{$day},".$fields['norepay_interest'].",".$fields['repay_interest'].",".$fields['norepay_principal'].",".$fields['repay_principal'].",".$fields['prepay_principal'].",".$fields['prepay_interest'].",{$time},{$time})";
        $sql.=" ON DUPLICATE KEY UPDATE norepay_interest=norepay_interest+".$fields['norepay_interest'];
        $sql.=" ,repay_interest=repay_interest+".$fields['repay_interest'];
        $sql.=" ,norepay_principal=norepay_principal+".$fields['norepay_principal'];
        $sql.=" ,repay_principal=repay_principal+".$fields['repay_principal'];
        $sql.=" ,prepay_principal=prepay_principal+".$fields['prepay_principal'];
        $sql.=" ,prepay_interest=prepay_interest+".$fields['prepay_interest'];
        $sql.=" ,update_time=".$time;
        return $this->execute($sql);
    }

    /**
     * 删除无效的回款日历
     * @param $uid
     * @param $prepayTime
     * @return bool|resource
     */
    public function delDealLoanRepayCalendar($uid,$prepayTime) {
        $month = date('n',$prepayTime);
        $day = date('j',$prepayTime);
        $table = $this->getTableNameByTime($prepayTime);
        $sql = "DELETE FROM `{$table}` WHERE user_id={$uid} AND repay_month={$month} AND repay_day={$day} AND norepay_interest=0 AND repay_interest = 0 AND norepay_principal = 0 AND repay_principal = 0 AND prepay_principal=0 AND prepay_interest = 0";
        return $this->execute($sql);
    }

    /**
     * 按月初始化用户日历数据
     * @param $uid
     * @param $year
     * @param $month
     * @return mixed
     */
    public function initDealLoanRepayCalendarByMonth($uid,$year,$month,$isReset=false) {
        $beginTime = to_timespan($year."-".$month."-01"); // 本月第一天
        $endTime   = to_timespan(date('Y-m-d', strtotime(date('Y-m-01', strtotime($year."-".$month."-01")) . ' +1 month -1 day')));// 本月走后一天

        $LoanRepayModel = new DealLoanRepayModel();
        $result1 = $LoanRepayModel->getUserNoRepaySummaryByTime($uid,$beginTime,$endTime);
        $result2 = $LoanRepayModel->getUserRepaySummaryByTime($uid,$beginTime,$endTime);
        $data = array();
        foreach($result1 as $v) {
            $day = to_date($v['time'],'j');
            if(!isset($data[$day])) {
                $data[$day]['norepay_interest'] = 0;
                $data[$day]['repay_interest'] = 0;
                $data[$day]['norepay_principal'] = 0;
                $data[$day]['repay_principal'] = 0;
                $data[$day]['prepay_principal'] = 0;
                $data[$day]['prepay_interest'] = 0;
            }
            if (in_array($v['type'],array(2,9))) {
                $data[$day]['norepay_interest'] = bcadd($data[$day]['norepay_interest'], $v['m'], 2);
            }
            if (in_array($v['type'], array(1,8))) {
                $data[$day]['norepay_principal'] = bcadd($data[$day]['norepay_principal'], $v['m'], 2);
            }
        }

        foreach ($result2 as $v) {
            $day = to_date($v['real_time'],'j');
            if(!isset($data[$day])) {
                $data[$day]['norepay_interest'] = 0;
                $data[$day]['repay_interest'] = 0;
                $data[$day]['norepay_principal'] = 0;
                $data[$day]['repay_principal'] = 0;
                $data[$day]['prepay_principal'] = 0;
                $data[$day]['prepay_interest'] = 0;
            }
            if (in_array($v['type'], array(2,5,9))) {
                $data[$day]['repay_interest'] = bcadd($data[$day]['repay_interest'], $v['m'], 2);
            }
            if (in_array($v['type'], array(1,8))) {
                $data[$day]['repay_principal'] = bcadd($data[$day]['repay_principal'], $v['m'], 2);
            }
            if ($v['type'] == 3) {
                $data[$day]['prepay_principal'] = bcadd($data[$day]['prepay_principal'], $v['m'], 2);
            }
            if (in_array($v['type'],array(4,7))) {
                $data[$day]['prepay_interest'] = bcadd($data[$day]['prepay_interest'], $v['m'], 2);
            }
        }

        if($isReset) {
            $res = $this->resetDealLoanRepayCalendar($uid,$year,$month,$data);
        }else{
            $res = $this->saveDealLoanRepayCalendar($uid,$year,$month,$data);
        }

        if(!$res) {
            return false;
        }
        return $data;
    }

    public function initDealLoanRepayCalendarByTime($uid,$beginTime,$endTime) {
        $LoanRepayModel = new DealLoanRepayModel();
        $result1 = $LoanRepayModel->getUserNoRepaySummaryByTime($uid,$beginTime,$endTime);
        $result2 = $LoanRepayModel->getUserRepaySummaryByTime($uid,$beginTime,$endTime);

        $data = array();
        foreach($result1 as $v) {
            if(bccomp($v['m'],0,2) == 0) {
                continue;
            }
            $year = to_date($v['time'],'Y');
            $month = to_date($v['time'],'n');
            $day = to_date($v['time'],'j');

            if(!isset($data[$year][$month][$day])) {
                $data[$year][$month][$day]['norepay_interest'] = 0;
                $data[$year][$month][$day]['repay_interest'] = 0;
                $data[$year][$month][$day]['norepay_principal'] = 0;
                $data[$year][$month][$day]['repay_principal'] = 0;
                $data[$year][$month][$day]['prepay_principal'] = 0;
                $data[$year][$month][$day]['prepay_interest'] = 0;
            }
            if (in_array($v['type'],array(2,9))) {
                $data[$year][$month][$day]['norepay_interest'] = bcadd($data[$year][$month][$day]['norepay_interest'], $v['m'], 2);
            }
            if (in_array($v['type'], array(1,8))) {
                $data[$year][$month][$day]['norepay_principal'] = bcadd($data[$year][$month][$day]['norepay_principal'], $v['m'], 2);
            }
        }

        foreach ($result2 as $v) {
            if(bccomp($v['m'],0,2) == 0) {
                continue;
            }
            $year = to_date($v['real_time'],'Y');
            $month = to_date($v['real_time'],'n');
            $day = to_date($v['real_time'],'j');
            if(!isset($data[$year][$month][$day])) {
                $data[$year][$month][$day]['norepay_interest'] = 0;
                $data[$year][$month][$day]['repay_interest'] = 0;
                $data[$year][$month][$day]['norepay_principal'] = 0;
                $data[$year][$month][$day]['repay_principal'] = 0;
                $data[$year][$month][$day]['prepay_principal'] = 0;
                $data[$year][$month][$day]['prepay_interest'] = 0;
            }
            if (in_array($v['type'], array(2,5,9))) {
                $data[$year][$month][$day]['repay_interest'] = bcadd($data[$year][$month][$day]['repay_interest'], $v['m'], 2);
            }
            if (in_array($v['type'], array(1,8))) {
                $data[$year][$month][$day]['repay_principal'] = bcadd($data[$year][$month][$day]['repay_principal'], $v['m'], 2);
            }
            if ($v['type'] == 3) {
                $data[$year][$month][$day]['prepay_principal'] = bcadd($data[$year][$month][$day]['prepay_principal'], $v['m'], 2);
            }
            if (in_array($v['type'],array(4,7))) {
                $data[$year][$month][$day]['prepay_interest'] = bcadd($data[$year][$month][$day]['prepay_interest'], $v['m'], 2);
            }
        }

        $GLOBALS['db']->startTrans();
        foreach($data as $key=>$val) {
            foreach($val as $key2=>$val2) {
                $res = $this->saveDealLoanRepayCalendar($uid,$key,$key2,$val2);
                if(!$res) {
                    $GLOBALS['db']->rollback();
                    return false;
                }
            }
        }
        $GLOBALS['db']->commit();
        return true;
    }

    /**
     * 保存日历数据
     * @param $uid
     * @param $year
     * @param $month
     * @param $moneyInfo
     * @return bool
     */
    public function saveDealLoanRepayCalendar($uid,$year,$month,$moneyInfo){
        if(empty($moneyInfo)) {
            return true;
        }

        $time = time();
        $table = $this->getTableNameByYear($year);
        try{
            $GLOBALS['db']->startTrans();
            foreach($moneyInfo as $day=>$row) {
                $sql = "INSERT INTO {$table} (user_id,repay_month,repay_day,norepay_interest,repay_interest,norepay_principal,repay_principal,prepay_principal,prepay_interest,create_time,update_time)";
                $sql.=" VALUES ({$uid},{$month},{$day},".$row['norepay_interest'].",".$row['repay_interest'].",".$row['norepay_principal'].",".$row['repay_principal'].",".$row['prepay_principal'].",".$row['prepay_interest'].",".$time.",".$time.")";
                $res = $GLOBALS['db']->query($sql);
                if(!$res) {
                    throw new \Exception("calendar save error");
                }
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, 'calendar save fail errMsg:'.$ex->getMessage())));
            return false;
        }
        return true;
    }

    public function resetDealLoanRepayCalendar($uid,$year,$month,$moneyInfo) {
        if(empty($moneyInfo)) {
            return true;
        }

        $time = time();
        $table = $this->getTableNameByYear($year);
        try{
            $GLOBALS['db']->startTrans();
            foreach($moneyInfo as $day=>$row) {
                $sql = "INSERT INTO {$table} (user_id,repay_month,repay_day,norepay_interest,repay_interest,norepay_principal,repay_principal,prepay_principal,prepay_interest,create_time,update_time)";
                $sql.=" VALUES ({$uid},{$month},{$day},".$row['norepay_interest'].",".$row['repay_interest'].",".$row['norepay_principal'].",".$row['repay_principal'].",".$row['prepay_principal'].",".$row['prepay_interest'].",".$time.",".$time.")";

                $sql.=" ON DUPLICATE KEY UPDATE norepay_interest=".$row['norepay_interest'];
                $sql.=" ,repay_interest=".$row['repay_interest'];
                $sql.=" ,norepay_principal=".$row['norepay_principal'];
                $sql.=" ,repay_principal=".$row['repay_principal'];
                $sql.=" ,prepay_principal=".$row['prepay_principal'];
                $sql.=" ,prepay_interest=".$row['prepay_interest'];
                $sql.=" ,update_time=".$time;
                $res = $GLOBALS['db']->query($sql);
                if(!$res) {
                    throw new \Exception("calendar save error");
                }
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, 'calendar reset fail errMsg:'.$ex->getMessage())));
            return false;
        }
        return true;
    }

    /**
     * 获取用loan_repay日历
     * @param $uid
     * @return mixed
     */
    public function getDealLoanRepayCalendar($uid,$year,$month) {
        $table = $this->getTableNameByYear($year);
        $sql = "select * from {$table} where user_id={$uid} AND repay_month={$month} ORDER  BY repay_day DESC ";
        return $this->findAllBySql($sql,true, array(),true);
    }

    /**
     * 判断是否已经生成某月份的用户日历
     * @param $uid
     * @return mixed
     */
    public function isExistsCalendarByMonth($uid,$year,$month) {
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT COUNT(*) AS cnt FROM {$table} WHERE user_id=".$uid." AND repay_month= {$month}";
        return $GLOBALS['db']->getOne($sql);
    }

    public function isExistsCalendarByDay($uid,$year,$month,$day) {
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT COUNT(*) AS cnt FROM {$table} WHERE user_id=".$uid." AND repay_month= {$month} AND repay_day={$day}";
        return $GLOBALS['db']->getOne($sql);
    }

    public function getDealLoanRepayCalendarListByYear($uid,$year) {
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT DISTINCT repay_month FROM `{$table}` where user_id={$uid} ORDER BY repay_month ASC";
        return $this->findAllBySql($sql,true, array(),true);
    }

    /**
     * 用户待回款的汇总
     * @param $uid
     * @param $year
     * @return array
     */
    public function getSumNoRepayCalendar($uid,$year,$monthDay){
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT SUM(norepay_principal) as norepay_principal ,SUM(norepay_interest) as  norepay_interest ,count(*) as total FROM {$table} ";
        $sql.=" WHERE user_id={$uid} AND  (norepay_principal > 0 or norepay_interest >0) AND (repay_month * 100 + repay_day) <= $monthDay";
        return $this->findBySql($sql,array(),true);
    }

    /**
     * 获取最近一笔待回款日历
     * @param $uid
     * @param $year
     * @return \libs\db\Model
     */
    public function getLatestNoRepayCalenar($uid,$year){
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT * FROM {$table} WHERE user_id={$uid} AND (norepay_principal > 0 or norepay_interest >0) ORDER BY repay_month asc,repay_day ASC ";
        return $this->findAllBySql($sql,false, array(),true);
    }

    public function getSumByYearMonth($uid,$year){
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT repay_month ,sum(norepay_interest) as norepay_interest, sum(repay_interest) as repay_interest, sum(norepay_principal) as norepay_principal, 
                sum(repay_principal) as repay_principal,sum(prepay_principal) as prepay_principal,sum(prepay_interest) as prepay_interest FROM  {$table}  where user_id={$uid} group by repay_month";
        return $this->findAllBySql($sql,true);
    }

    /**
     * 按年分表
     * @param $repayTime
     */
    public function getTableNameByTime($repayTime) {
        //$year = to_date($repayTime,'Y');
        $year = date('Y',$repayTime);
        return 'firstp2p_deal_loan_repay_calendar_'.$year;
    }
    public function getTableNameByYear($year) {
        return 'firstp2p_deal_loan_repay_calendar_'.$year;
    }
}