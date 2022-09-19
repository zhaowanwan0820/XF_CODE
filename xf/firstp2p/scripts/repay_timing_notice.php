<?php
/**
 * 每周三上午11点提醒次一周全部项目还款信息, 还款日期前2工作日上午10点提醒当日还款信息(还款日如果遇见节假日，则将还款日提前至上一个工作日)
 * php repay_timing_notice.php 7 (次一周), php repay_timing_notice.php 2 (提前2日)
 */
require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../system/utils/logger.php';

use core\dao\ExchangeModel;
use core\service\UserService;
use core\dao\DealAgencyModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$dayNum = intval($argv[1]);
$holidays = dict::get('REDEEM_HOLIDAYS');
$now_time = mktime(0,0,0,date('m'),date('d'),date('y'));
$holis_time = [];
foreach($holidays as $item){
    $item = strtotime($item);
    if($item >= $now_time - 30*86400 && $item <= $now_time + 30*86400){
        $holis_time[$item] = $item;
    }
}

$maxDayNum = app_conf('MAX_HOLIDAY_NUM') ? app_conf('MAX_HOLIDAY_NUM')+1 : 8;

//获取某工作日的T-2时间
function getT2Time($holis_time, $work_time, $diff_time){
    $notice_time = $work_time;
    for($j = 2; $j > 0; $j--){
        $notice_time = $notice_time + $diff_time;
        while(isset($holis_time[$notice_time])){
            $notice_time = $notice_time + $diff_time;
        }
    }

    return $notice_time;
}

//求当前需要提醒的还款日期
function getNowNoticeTime($holis_time, $maxDayNum){
    $timeArr = array();
    $now_time = mktime(0,0,0,date('m'),date('d'),date('y'));

    //当前日期为节假日不发送邮件
    if(isset($holis_time[$now_time])){
        return [];
    }

    foreach($holis_time as $holiday_time){
        //节假日提醒
        for($i = 1; $i <= $maxDayNum; $i++){
            $work_time = $holiday_time - $i*86400;

            if(!isset($holis_time[$work_time])){
                break;

            }
        }

        //T-2
        $notice_time = getT2Time($holis_time, $work_time, -86400);

        if($notice_time == $now_time){
            $timeArr[] = $holiday_time;
        }

    }

    //当前日T+2
    $after_time = getT2Time($holis_time, $now_time, 86400);
    if(!in_array($after_time, $timeArr)){
        $timeArr[] = $after_time;
    }

    return $timeArr;
}

//发邮件
function _executeSend($timeArr, $dayNum){
    $fromEmailData = array('sender' => '网信-平台运营部','from' => 'platope@s1.firstp2p.com');
    $exchangeModel = new ExchangeModel();
    $repayLists = [];
    foreach($timeArr as $time){
        $stime = $time['stime'];
        $etime = $time['etime'];
        $conditions = sprintf("repay_time >= %d AND repay_time < %d AND status = 1", $stime, $etime);
        $repayList = $exchangeModel->getRepayList($conditions, 'repay_time, batch_id, repay_money');
        $repayLists = array_merge($repayLists, $repayList);
    }

    $msgcenter = new Msgcenter();
    $repayArr = array();

    $dealAgencyModel = new DealAgencyModel();
    // 获取交易所列表
    $jysList = $dealAgencyModel->getDealAgencyList(9);
    // 获取咨询机构列表
    $consultList = $dealAgencyModel->getDealAgencyList(2);

    foreach($repayLists as $key => $value){
        // 获取批次信息
        $batchInfo = $exchangeModel->getBatchInfoById($value['batch_id']);

        // 获取项目信息
        $projectInfo = $exchangeModel->getProjectInfoById($batchInfo['pro_id']);

        // 获取发行人名称
        $userService = new UserService();
        $userInfo = $userService->getUser($projectInfo['fx_uid']);

        $consultInfo = $consultList[$projectInfo['consult_id']];
        $jysInfo = $jysList[$projectInfo['jys_id']];

        $value['repay_money'] = $value['repay_money'] / 100;
        $value['batch_number'] = "{$batchInfo['batch_number']} 期";
        $value['jys_number'] = $projectInfo['jys_number'];
        $value['jys_name'] = $jysInfo['name'];
        $value['fx_name'] = $userInfo['real_name'];
        $value['consult_name'] = $consultInfo['name'];
        $value['exchange_repay_notice_email'] = $consultInfo['exchange_repay_notice_email'];
        $value['repay_time'] = date('Y.m.d',  $value['repay_time']);

        $repayArr[$projectInfo['consult_id']][] = $value;

    }

    if($dayNum == 7){
        $stime_date = date('Y.m.d', $stime);
        $etime_date = date('Y.m.d', $etime - 86400);
        $notice_title = sprintf("【线下交易所-周批次还款提醒】 %s-%s", $stime_date, $etime_date);
        $time_str = sprintf("%s-%s 周期内", $stime_date, $etime_date);
    }else{
        $notice_title = "【线下交易所-还款提醒】";
        $time_str = "";
    }

    foreach($repayArr as $key => $value){
        $notice['list'] = $value;
        $notice['time_str'] = $time_str;
        ob_start();
        include dirname(__DIR__).'/web/views/message/repay_tpl.html';
        $messageData = ob_get_contents();
        @ob_clean();
        $email_arr = explode(',', $value[0]['exchange_repay_notice_email']);
        foreach($email_arr as $email){
            if(is_email($email)){
                $msgcenter->setMsg($email, 0, array('tplData' => $messageData), 'TPL_OFFLINE_EXCHANGE_REPAY_NOTICE', $notice_title, '', '', [], $fromEmailData);
            }
        }
    }
    $msgcenter->save();//发送邮件
}

$timeArr = [];
if($dayNum == 2){
    $noticeTimes = getNowNoticeTime($holis_time, $maxDayNum);
    foreach($noticeTimes as $time){
        $timeArr[] = array('stime' => $time, 'etime' => $time + 86400);
    }
    _executeSend($timeArr, $dayNum);

}else{
    $stime = mktime(0,0,0,date('m'),date('d')-date('w')+1+7,date('y'));
    $etime = $stime + 86400*7;
    $timeArr[] = array('stime' => $stime, 'etime' => $etime);
    _executeSend($timeArr, $dayNum);
}

exit;



