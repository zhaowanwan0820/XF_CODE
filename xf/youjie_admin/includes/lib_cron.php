<?php

/**
 * ECSHOP 计划任务相关函数
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 获得下一次执行时间
 *
 * @param   array  $cron
 * @return  integer
 */
function get_next_time($cron)
{
    $cron['hour'] = $cron['hour'] == 24 ? '' : $cron['hour'];
    $cron['minute'] = $cron['minute'] ? explode(',', $cron['minute']): '';
    list($yearnow, $monthnow, $daynow, $weekdaynow, $hournow, $minutenow) = explode('-', local_date('Y-n-j-w-G-i', gmtime()));

    if($cron['weekday'] == '') {
        if(!$cron['day']) {
            $firstday = $daynow;
            $secondday = $daynow + 1;
        } else {
            $firstday = $cron['day'];
            $secondday = $cron['day'];
        }
    } else {
        $firstday = $daynow + ($cron['weekday'] - $weekdaynow);
        $secondday = $firstday + 7;
    }

    if($firstday < $daynow) {
        $firstday = $secondday;
    }

    if($firstday == $daynow) {
        $todaytime = get_today_next_run($cron);
        if($todaytime['hour'] == '' && $todaytime['minute'] == '') {
            $cron['day'] = $secondday;
            $nexttime = get_today_next_run($cron, 0, -1);
            $cron['hour'] = $nexttime['hour'];
            $cron['minute'] = $nexttime['minute'];
        } else {
            $cron['day'] = $firstday;
            $cron['hour'] = $todaytime['hour'];
            $cron['minute'] = $todaytime['minute'];
        }
    } else {
        $cron['day'] = $firstday;
        $nexttime = get_today_next_run($cron, 0, -1);
        $cron['hour'] = $nexttime['hour'];
        $cron['minute'] = $nexttime['minute'];
    }

    $timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : $GLOBALS['_CFG']['timezone'];
    $nextrun = @gmmktime($cron['hour'] ?: 0, $cron['minute'] ?: 0, 0, $monthnow, $cron['day'], $yearnow) - ($timezone * 3600);
    if($cron['day'] && $nextrun < gmtime()) {       // 修正按月执行的问题(这里若天超过该月最大值会自动进位到下个月)
        $nextrun = @gmmktime($cron['hour'] ?: 0, $cron['minute'] ?: 0, 0, ++$monthnow, $cron['day'], $yearnow) - ($timezone * 3600);
    }

    return $nextrun;
}
function get_today_next_run($cron, $hour = -2, $minute = -2) {

    $hour = $hour == -2 ? local_date('G', gmtime()) : $hour;
    $minute = $minute == -2 ? local_date('i', gmtime()) : $minute;

    $nexttime = array();
    if($cron['hour'] == '' && !$cron['minute']) {       // 没有设置时、分
        $nexttime['hour'] = $hour;
        $nexttime['minute'] = $minute + 1;
    } elseif($cron['hour'] == '' && $cron['minute']) {  // 没有设置时、有设置分
        $nexttime['hour'] = $hour;
        if(($nextminute = get_next_minute($cron['minute'], $minute)) === false) {
            ++$nexttime['hour'];
            $nextminute = $cron['minute'][0];
        }
        $nexttime['minute'] = $nextminute;
    } elseif($cron['hour'] != '' && !$cron['minute']) {    // 有设置时、没有设置分
        if($cron['hour'] < $hour) {
            $nexttime['hour'] = $nexttime['minute'] = '';
        } elseif($cron['hour'] == $hour) {
            $nexttime['hour'] = $cron['hour'];
            $nexttime['minute'] = $minute + 1;
        } else {
            $nexttime['hour'] = $cron['hour'];
            $nexttime['minute'] = 0;
        }
    } elseif($cron['hour'] != '' && $cron['minute']) {    // 有设置时、分
        $nextminute = get_next_minute($cron['minute'], $minute);
        if($cron['hour'] < $hour || ($cron['hour'] == $hour && $nextminute === false)) {
            $nexttime['hour'] = '';
            $nexttime['minute'] = '';
        } else {
            $nexttime['hour'] = $cron['hour'];
            $nexttime['minute'] = $cron['hour'] > $hour ? $cron['minute'][0]: $nextminute;
        }
    }

    return $nexttime;
}
function get_next_minute($nextminutes, $minutenow) {
    foreach($nextminutes as $nextminute) {
        if($nextminute > $minutenow) {
            return $nextminute;
        }
    }
    return false;
}

?>
