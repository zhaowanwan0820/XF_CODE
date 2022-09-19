<?php
namespace core\dao\dealloan;

use libs\db\MysqlDb;
use core\dao\BaseModel;

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
     * 取得用户回款日历月份列表
     * @param $uid
     * @return array
     */
    public function getDealLoanRepayCalendarListByYear($uid,$year) {
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT DISTINCT repay_month FROM `{$table}` where user_id={$uid} ORDER BY repay_month ASC";
        return $this->findAllBySql($sql,true, array(),true);
    }
    /**
     * 获取最近一笔待回款日历
     * @param $uid
     * @param $year
     * @return \libs\db\Model
     */
    public function getLatestNoRepayCalenar($uid,$year){
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT * FROM {$table} WHERE user_id={$uid} AND (norepay_principal <> 0 OR norepay_interest <> 0) ORDER BY repay_month asc,repay_day ASC ";
        return $this->findAllBySql($sql,false, array(),true);
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
        $sql.=" WHERE user_id={$uid} AND  (norepay_principal <> 0 OR norepay_interest <> 0) AND (repay_month * 100 + repay_day) <= $monthDay";
        return $this->findBySql($sql,array(),true);
    }

    public function getSumByYearMonth($uid,$year){
        $table = $this->getTableNameByYear($year);
        $sql = "SELECT repay_month ,sum(norepay_interest) as norepay_interest, sum(repay_interest) as repay_interest, sum(norepay_principal) as norepay_principal, 
                sum(repay_principal) as repay_principal,sum(prepay_principal) as prepay_principal,sum(prepay_interest) as prepay_interest FROM {$table}  where user_id={$uid} group by repay_month";
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