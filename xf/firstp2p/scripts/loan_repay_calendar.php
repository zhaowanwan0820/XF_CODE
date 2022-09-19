<?php
/**
 * 汇总用户的loan_repay 未回款日历准备数据
 * User: jinhaidong
 * Date: 2016-3-28 18:12:50
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\DealLoanRepayCalendarModel;

class LoanRepayCalendar {

    public function run() {
        global $argv;

        $method = $argv[1];

        if(!in_array($method,array('hotUser','normalUserByYear','normalUserByMonth','allFromOneDay','resetMonths','resetCalendarByUserIds','delRedisKey'))) {
            echo "Please input right method:hotUser|normalUserByYear|normalUserByMonth|allFromOneDay|resetMonths|resetCalendarByUserIds|delRedisKey\n";
            exit;
        }
        if($method == 'delRedisKey') {
            if(!isset($argv[2])) {
                echo "Please input right redis key\n";
                exit;
            }
            $this->delRedisKey($argv[2]);
            exit;
        }
        if($method == 'allFromOneDay') {
            if(!isset($argv[2])) {
                echo "Please input right begin date eg:2016-03-01\n";
                exit;
            }
            if(!isset($argv[3])) {
                echo "Please input right end date eg:2018-09-01\n";
                exit;
            }
            $this->initAllUserCalendarFromOneDay($argv[2],$argv[3]);
            exit;
        }
        if($method == 'resetCalendarByUserIds') {
            if(!isset($argv[2])) {
                echo "Please input right uids eg:4,5,6\n";
                exit;
            }
            $uids = explode(",",$argv[2]);
            if(!isset($argv[3])) {
                echo "Please input right year eg:2015\n";
                exit;
            }
            $year = $argv[3];
            $month = isset($argv[4]) ? $argv[4] : false;
            $this->resetCalendarByUserIds($uids,$year,$month);
            exit;
        }
        if(!isset($argv[2])) {
            echo "Please input right year eg:2016\n";
            exit;
        }
        if(!isset($argv[3])) {
            echo "Please input right month eg:1\n";
            exit;
        }
        $year = $argv[2];
        $month = $argv[3];

        if($method == 'hotUser') {
            $users = \libs\utils\Block::getAllSpecialUsers();
            if(empty($users)) {
                echo "热点用户数据为空\n";
                exit;
            }
            try{
                foreach($users as $uid) {
                    $cnt = DealLoanRepayCalendarModel::instance()->isExistsCalendar($uid,$year,$month);
                    if($cnt > 0) {
                        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, "user calendar has been done uid:".$uid)));
                        continue;
                    }

                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, "begin to init loan_repay calendar uid:".$uid)));
                    $res = DealLoanRepayCalendarModel::instance()->initDealLoanRepayCalendar($uid,$year,$month);
                    if(!$res) {
                        throw new \Exception("init fail uid:".$uid);
                    }
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, "init loan_repay calendar success uid:".$uid)));
                }
            }catch (\Exception $ex) {
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "init loan_repay calendar fail errMsg:".$ex->getMessage())));
            }
        }elseif($method == 'normalUserByMonth'){
            $this->initUserLoanRepayCalendarByMonth($year,$month);
        }elseif($method == 'resetMonths'){
            $this->resetMonths($year,$month);
        }else{
            $this->initUserLoanRepayCalendarByYear($year);
        }
    }

    public function delRedisKey($key) {
        try{
            $res = \SiteApp::init()->dataCache->getRedisInstance()->del($key);
            if(!$res) {
                throw new \Exception("redis key del fail key:{$key}");
            }
        }catch (\Exception $ex) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, " redis key del fail ".$ex->getMessage())));
            exit;
        }
    }

    /**
     * 按月份去初始化用户回款日历
     * @param $year
     * @param $month
     */
    public function initUserLoanRepayCalendarByMonth($year,$month) {
        $redisKey = 'CALENDAR_LOAN_REPAY_USER_MONTH_NEW';
        $redisKeyRegin = $year."_".$month;
        $idOffset = \SiteApp::init()->dataCache->getRedisInstance()->hGet($redisKey,$redisKeyRegin);
        if(!$idOffset) {
            $idOffset = 0;
        }
        $startTime = to_timespan($year."-".$month."-01") + 2678400;
  
        while(true) {
            $sql = "SELECT id FROM `firstp2p_user` where id > {$idOffset} AND  create_time <=$startTime ORDER  BY id ASC limit 1000";
            $rows = $GLOBALS['db']->get_slave()->getAll($sql);

            if(empty($rows)) {
                echo "所有的用户回款日历生成完毕\n";
                break;
            }

            foreach($rows as $row) {
                $uid = $row['id'];
                $cnt = DealLoanRepayCalendarModel::instance()->isExistsCalendarByMonth($uid,$year,$month);
                if($cnt > 0) {
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, " loan_repay calendar has been done uid:{$uid}")));
                    continue;
                }
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "begin init loan_repay calendar uid:{$uid}")));

                $res = DealLoanRepayCalendarModel::instance()->initDealLoanRepayCalendarByMonth($uid,$year,$month);

                if($res === false) {
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "init loan_repay calendar fail uid:{$uid}")));
                    break 2;
                }
                $setRes = \SiteApp::init()->dataCache->getRedisInstance()->hSet($redisKey, $redisKeyRegin, $uid);
                $idOffset = $uid;
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "init loan_repay calendar success uid:{$uid}")));
            }
        }
    }

    /**
     * 将改天之后的用户的回款数据全部初始化
     * @param $beginDate 开始日期 例如 2016-03-01
     * @param $endDate   结束日期 例如 2016-12-01
     */
    public function initAllUserCalendarFromOneDay($beginDate,$endDate) {
        $redisKey = 'CALENDAR_INIT_FINISH_USER_IDS_NEW';
        $idOffset = \SiteApp::init()->dataCache->getRedisInstance()->get($redisKey);
        if(!$idOffset) {
            $idOffset = 0;
        }
        $beginTime = to_timespan($beginDate);
        $endTime = to_timespan($endDate);

        while(true) {
            $sql = "SELECT id FROM `firstp2p_user` where id > {$idOffset} ORDER  BY id ASC limit 1000";

            $rows = $GLOBALS['db']->get_slave()->getAll($sql);

            if(empty($rows)) {
                echo "所有的用户 以后回款日历生成完毕\n";
                break;
            }

            foreach($rows as $row) {
                $uid = $row['id'];
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "begin init loan_repay calendar uid:{$uid}")));
                $res = DealLoanRepayCalendarModel::instance()->initDealLoanRepayCalendarByTime($uid,$beginTime,$endTime);
                if($res === false) {
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "init loan_repay calendar fail uid:{$uid}")));
                    break 2;
                }
                $setRes = \SiteApp::init()->dataCache->getRedisInstance()->set($redisKey, $uid);
                $hsetRes = \SiteApp::init()->dataCache->getRedisInstance()->hSet(\core\service\DealLoanRepayCalendarService::CALENDAR_INIT_FINISH,$uid,1);

                if(!$setRes || !$hsetRes) {
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "init loan_repay redis set error uid:{$uid}")));
                    break 2;
                }

                $idOffset = $uid;
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "init loan_repay calendar success uid:{$uid}")));
            }
        }
    }

    public function initUserLoanRepayCalendarByYear($year) {
        for($month=1;$month<=12;$month++) {
            $this->initUserLoanRepayCalendarByMonth($year,$month);
        }
    }

    public function resetMonths($year,$months) {
        $months = explode(",",$months);
        foreach($months as $month) {
            $this->resetUserLoanRepayByMonth($year,$month);
        }
    }

    /**
     * 按月份重置用户回款日历
     * @param $year
     * @param $months
     */
    public function resetUserLoanRepayByMonth($year,$month) {
        $redisKey = 'CALENDAR_LOAN_REPAY_USER_MONTH_NEW';
        $redisKeyRegin = $year."_".$month;
        $idOffset = \SiteApp::init()->dataCache->getRedisInstance()->hGet($redisKey,$redisKeyRegin);
        if(!$idOffset) {
            $idOffset = 0;
        }
        $startTime = to_timespan($year."-".$month."-01") + 2678400;

        while(true) {
            $sql = "SELECT id FROM `firstp2p_user` where id > {$idOffset} AND  create_time <=$startTime ORDER  BY id ASC limit 2000";
            $rows = $GLOBALS['db']->get_slave()->getAll($sql);

            if(empty($rows)) {
                echo "所有的用户回款日历重置完毕\n";
                break;
            }

            foreach($rows as $row) {
                $uid = $row['id'];
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "begin reset loan_repay calendar uid:{$uid}")));

                $res = DealLoanRepayCalendarModel::instance()->initDealLoanRepayCalendarByMonth($uid,$year,$month,true);
                if($res === false) {
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "reset loan_repay calendar fail uid:{$uid}")));
                    break 2;
                }
                $setRes = \SiteApp::init()->dataCache->getRedisInstance()->hSet($redisKey, $redisKeyRegin, $uid);
                $idOffset = $uid;
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "reset loan_repay calendar success uid:{$uid}")));
            }
        }
    }


    /**
     * 按照指定用户重置数据
     * @param $year
     * @param bool|false $month
     */
    public function resetCalendarByUserIds(Array $uids,$year,$month=false) {
        foreach($uids as $uid) {
            if(!$month) {
                for($i=1;$i<=12;$i++) {
                    $res = DealLoanRepayCalendarModel::instance()->initDealLoanRepayCalendarByMonth($uid,$year,$i,true);
                    if($res === false) {
                        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "reset loan_repay calendar fail uid:{$uid},year:{$year},month:{$month}")));
                        break 2;
                    }
                }
            }else{
                $res = DealLoanRepayCalendarModel::instance()->initDealLoanRepayCalendarByMonth($uid,$year,$month,true);
                if($res === false) {
                    \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "reset loan_repay calendar fail uid:{$uid},year:{$year},month:{$month}")));
                    break;
                }}
        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new LoanRepayCalendar();
$obj->run();
