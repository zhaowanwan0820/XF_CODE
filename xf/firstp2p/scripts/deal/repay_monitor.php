<?php
/**
 * 还款检查，19点开始每隔半小时执行一次
 */

require_once dirname(__FILE__).'/../../app/init.php';

use core\dao\BaseModel;
use libs\utils\Logger;
use libs\utils\Alarm;

set_time_limit(0);
ini_set('memory_limit', '256M');
error_reporting(E_ALL ^ E_NOTICE);

class RepayMonitor {
    public function run(){

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, '还款检查开始')));
        $model = new BaseModel();
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $key = 'WX_DEAL_REPAY_REMAIN_TODAY_'.date('Ymd');
        $isAlarm = $redis->get($key);
        $isAlarm = $isAlarm ? false : true;

        if(!$isAlarm){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, '今日还款已完成不需要在进行检查')));
            return;
        }

        $today = mktime(-8, 0, 0, date("m"), date("d"), date("Y"));
        $sql = "select count(*) from firstp2p_deal_repay where repay_time=".$today." and status=0";


        $count1 = $model->countBySql($sql, array(), true);

        if($count1 > 0 && $isAlarm){
            $title = '今日还款剩余:' . $count1." 条";
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, '网信有未完成还款状态:'.$count1)));
            Alarm::push('PH_DEAL_REPAY_REMAIN_TODAY',$title,$count1);
        }

        $beforeTen = time() - 28800 - 86399;
        $sql = "select count(*) from firstp2p_jobs WHERE priority = 85 AND create_time >= {$beforeTen} AND status !=2";
        $count2 = $model->countBySql($sql, array(), true);

        if($count2 > 0 && $isAlarm){
            $title = '今日还款JOBS未完成:' . $count2." 条";
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, '网信有未完成JOBS:'.$count2)));
            Alarm::push('PH_DEAL_REPAY_REMAIN_TODAY',$title,$count2);
        }

        if(($count1 + $count2) == 0 && $isAlarm){
            $title = '网信今日还款已完成！';
            Alarm::push('PH_DEAL_REPAY_REMAIN_TODAY',$title,0);

            $redis->set($key,1);
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "还款检查完成 count1:{$count1},count2:{$count2}")));
    }
}

$monitor = new RepayMonitor();
$monitor->run();