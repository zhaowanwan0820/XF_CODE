<?php
namespace core\service\deal;

use core\dao\BaseModel;
use core\dao\dealloan\DealLoanRepayCalendarModel;
use libs\utils\Logger;
use core\dao\jobs\JobsModel;
use core\service\BaseService;
use core\enum\JobsEnum;
use core\enum\DealLoanRepayCalendarEnum;


/**
 * loan_repay 汇总表
 * @author jinhaidong
 * @date 2016-2-26 11:40:44
 */
class DealLoanRepayCalendarService extends BaseService {


    /**
     * 汇总用户的loan_repay数据
     * @param int $uit 投资用户ID
     * @param int $repayTime 预计/实际 还款时间
     * @param Array $moneyInfo 汇总的各项金额
     * @param $prepayTime int 提前还款回款|强制提前 需要删除的回款记录时间
     */
    public static function collect($uid,$repayTime,$moneyInfo,$calcTime='') {
        // 用户数据未初始化完全的情况下不进行任何操作 用户初始化完成后 打开开关，收集所有用户行为
//        if(app_conf('USER_CALENDAR_FINISH') == '0' && !self::hasInitCalendarFinish($uid)) {
//            return true;
//        }
        $moneyTypes = array_keys($moneyInfo);
        $diff = array_diff($moneyTypes,DealLoanRepayCalendarEnum::$moneyTypes);
        if($diff) {
            throw new \Exception("user_loan_repay_calendar 非法的字段类型:".implode(",",$moneyTypes));
        }
        Logger::debug(implode(',',array(__CLASS__,__FUNCTION__,"uid:".$uid,"repayTime:".$repayTime,"moneyInfo:".json_encode($moneyInfo),"calcTime:".$calcTime)));

        $jobs_model = new JobsModel();
        $function = '\core\service\deal\DealLoanRepayCalendarService::collectJobs';
        $param = array(
            'uid' => $uid,
            'repayTime' => $repayTime,
            'moneyInfo' => $moneyInfo,
            'calcTime' => $calcTime,
        );
        $jobs_model->priority = JobsEnum::JOBS_PRIORITY_REPAY_CALENDAR_COLLECT;
        $r = $jobs_model->addJob($function, array('param' => $param), false, 90);
        if ($r === false) {
            throw new \Exception('add \core\service\DealLoanRepayCalendarService::collectJobs error');
        }
        return true;
    }

    /**
     * 为什么要把collect单独出一个jobs来执行 ??
     *   答: 因为线上频繁发生Deadlock 的报警，原因是线上calendar表设置的是uid、month,day 联合唯一索引 在放款、还款等批量更新操作过程中
     *       多个进程的频繁插入导致。所以改成jobs 用单一worker执行来避免
     * 风险：用户看到日历可能会有延迟
     */
    public function collectJobs($params) {
        $uid = $params['uid'];
        $repayTime = $params['repayTime'];
        $moneyInfo = $params['moneyInfo'];
        $calcTime  = $params['calcTime'];

        $res = DealLoanRepayCalendarModel::instance()->updateDealLoanRepayCalendar($uid,$repayTime,$moneyInfo);
        if(!$res) {
            throw new \Exception("updateDealLoanRepayCalendar fail");
        }
        if($calcTime) {
            return DealLoanRepayCalendarModel::instance()->delDealLoanRepayCalendar($uid,$calcTime);
        }
        return true;
    }

    /**
     * 是否用户的所有日历已经初始化完毕
     * @param $uid
     */
    public static function hasInitCalendarFinish($uid) {
        $redisKey = DealLoanRepayCalendarEnum::CALENDAR_INIT_FINISH;
        $redisKeyRegin = $uid;
        $val = \SiteApp::init()->dataCache->getRedisInstance()->hGet($redisKey, $redisKeyRegin);
        return ($val == 1) ? true :false;
    }

    /**
     * 获取用户资产数据
     * @param $uid
     * @return mixed
     */
    public static function getDealLoanRepayCalendar($uid,$year,$month) {
        $endYear = date('Y') + 3;
        if($year < DealLoanRepayCalendarEnum::BEGIN_YEAR || $year > $endYear) {
            return array();
        }
        return DealLoanRepayCalendarModel::instance()->getDealLoanRepayCalendar($uid,$year,$month);
    }


    /**
     * 取得用户回款日历月份列表
     * @param $uid
     * @return array
     */
    public static function getDealLoanRepayCalendarList($uid) {
        $beginYear = DealLoanRepayCalendarEnum::BEGIN_YEAR;
        $endYear = date('Y') + 3;// TODO 改为自动监测
        $data = array(
            'default_year' => '',
            'default_month' => '',
            'list' => array(),
        );
        $currentYear = date('Y');
        $nearMonthData = array();
        for($year=$beginYear;$year<=$endYear;$year++) {
            try{
                $res = DealLoanRepayCalendarModel::instance()->getDealLoanRepayCalendarListByYear($uid,$year);
                if(empty($res)) {
                    continue;
                }
            }catch (\Exception $ex) {
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, " errMsg:".$ex->getMessage())));
                continue;
            }

            $tmpMonth = array();
            foreach($res as $month) {
                $tmpMonth[]= $month['repay_month'];
            }
            $nearMonthData[$year] = $tmpMonth;
            $data['list'][] = array(
                'year' => $year,
                'month' => $tmpMonth,
            );
        }
        $defaultYM = self::getDefaultYearAndMonth($nearMonthData);
        $data['default_year'] = $defaultYM['defaultYear'];
        $data['default_month'] = $defaultYM['defaultMonth'];
        return $data;
    }

    private static function getDefaultYearAndMonth($yearMonthData) {
        $currentMonth = date('n');
        $years = array_keys($yearMonthData);
        $years_key = array_flip($years);
        $defaultYear = self::getDefaultYear($years);
        $tmpYmData = $yearMonthData[$defaultYear];
        $defaultYearMaxMonth = array_pop($tmpYmData);
        if($currentMonth > $defaultYearMaxMonth) {
            if(isset($years[$years_key[$defaultYear]+1])) {
                $defaultYear = $years[$years_key[$defaultYear]+1];
            }
        }

        $defautMonth = self::getDefaultMonth($defaultYear,$yearMonthData[$defaultYear]);
        return array(
            'defaultYear' => $defaultYear,
            'defaultMonth' => $defautMonth,
        );
    }

    private static function getDefaultYear($years) {
        $currentYear = date('Y');
        if(in_array($currentYear,$years)) {
            $year = $currentYear;
        }else{
            $leftYears = array();
            $rightYears = array();

            foreach($years as $_year) {
                if($_year < $currentYear) {
                    $leftYears[]=$_year;
                }else{
                    $rightYears[]=$_year;
                }
            }
            $year = !empty($rightYears) ? $rightYears[0] : array_pop($leftYears);
        }
        return $year;
    }

    private static function getDefaultMonth($year,$nearMonthData) {
        $currentYear = date('Y');
        $currentMonth = date('n');
        if($currentYear > $year) {
            $month = array_pop($nearMonthData);
        }elseif($currentYear < $year) {
            $month =  $nearMonthData[0];
        }else{
            if(in_array($currentMonth,$nearMonthData)) {
                $month = $currentMonth;
            }else{
                $leftMonth = array();
                $rightMonth = array();

                foreach($nearMonthData as $_month) {
                    if($_month < $currentMonth) {
                        $leftMonth[]=$_month;
                    }else{
                        $rightMonth[]=$_month;
                    }
                }
                $month = !empty($rightMonth) ? $rightMonth[0] : array_pop($leftMonth);
            }
        }
        return $month;
    }

    /**
     * 获取用户最近一笔回款
     * @param $uid
     * @param $beginYear
     * @param $beginMonth
     * @param $beginDay
     * @param $days 未来$day天内  false 代表所有
     */
    public function getUserRecentCalendar($uid,$beginYear,$beginMonth,$beginDay,$days=false){
        $endYear = $beginYear + 3;
        if($days > 0){
            $endTime = strtotime($beginYear."-".$beginMonth."-".$beginDay) + $days * 86400;
            $endYear = date('Y',$endTime);
        }
        $data = array();
        for($year=$beginYear;$year<=$endYear;$year++) {
            $results = DealLoanRepayCalendarModel::instance()->getLatestNoRepayCalenar($uid, $year);
            if(!$results){
                continue;
            }
            foreach ($results as $res) {
                $key = sprintf('%d%02d%02d',$year,$res->repay_month,$res->repay_day);
                $data[$key] = array(
                    'year'=>$year,
                    'month'=>$res->repay_month,
                    'day'=> $res->repay_day,
                    'norepay_principal' => $res->norepay_principal,
                    'norepay_interest' => $res->norepay_interest
                );
            }
        }
        return $data;
    }

    /**
     * 获取用户所有待回款的日历汇总
     * @param $uid
     * @param $beginYear
     * @param $beginMonth
     * @param $beginDay
     * @param $days 未来$day天内  false 代表所有
     */
    public function getUserNoRepayCalendar($uid,$beginYear,$beginMonth,$beginDay,$days=false){
        if($days > 0){
            $endTime = strtotime($beginYear."-".$beginMonth."-".$beginDay) + $days * 86400;
        }else{
            // 没有传days 最大取3年
            $threeYear = $beginYear + 3;
            $endTime = strtotime($threeYear."-12-31");
        }
        $endYear = date('Y',$endTime);

        $data = array('norepay_principal' => 0,'norepay_interest' => 0,'totalNum'=>0);

        // 当设置天数跨年的时候 在最后一年的截止时间要根据实际月份天数计算
        for($year = $beginYear;$year <= $endYear;$year++){
            if($year < $endYear) {
                $endMonth = 12;
                $endDay = 31;
            }else{
                $endMonth = date('n',$endTime);
                $endDay = date('j',$endTime);
            }
            $endMonthDay = ($endMonth * 100) + $endDay;
            $res = DealLoanRepayCalendarModel::instance()->getSumNoRepayCalendar($uid, $year,$endMonthDay);
            $data['norepay_principal'] = $res->norepay_principal ? bcadd($res->norepay_principal,$data['norepay_principal'],2) : $data['norepay_principal'];
            $data['norepay_interest'] = $res->norepay_principal ? bcadd($res->norepay_interest,$data['norepay_interest'],2) : $data['norepay_interest'];
            $data['totalNum'] = $res->total > 0 ? ($data['totalNum']+$res->total) : $data['totalNum'];
        }
        return $data;
    }

    public function getSumByYearMonth($uid,$year){
        $res = DealLoanRepayCalendarModel::instance()->getSumByYearMonth($uid, $year);
        $data = array();
        foreach($res as $k=>$v){
            $data[$v['repay_month']]['norepay_interest'] = $v['norepay_interest'] ? $v['norepay_interest'] : 0;
            $data[$v['repay_month']]['repay_interest']  = $v['repay_interest'] ? $v['repay_interest'] : 0;
            $data[$v['repay_month']]['norepay_principal'] = $v['norepay_principal'] ? $v['norepay_principal'] : 0;
            $data[$v['repay_month']]['repay_principal'] = $v['repay_principal'] ? $v['repay_principal'] : 0;
            $data[$v['repay_month']]['prepay_principal'] = $v['prepay_principal'] ? $v['prepay_principal'] : 0;
            $data[$v['repay_month']]['prepay_interest'] = $v['prepay_interest'] ? $v['prepay_interest'] : 0;
        }
        return $data;
    }
}