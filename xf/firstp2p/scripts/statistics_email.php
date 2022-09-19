<?php
/**
 * 统计邮件发送情况
 * @author 彭长路 2014年7月9日11:38:00
 *
 **/

require_once dirname(__FILE__).'/../app/init.php';
use libs\db\MysqlDb;
$db = new MysqlDb("10.10.10.72:3306", "bzplan_read", "Ic7rI2UeLE", "firstp2p", "utf8");
$GLOBALS['db'] = $db;
//统计开始时间
$start_time = 1373530091;
//显示的问题
$start_time_show = 1373530091+8*3600;

$start_d = date('j',$start_time_show); //12 日
$start_m = date('n',$start_time_show);//7 月
$start_y = date('Y',$start_time_show);//2013 年
echo $start_d,'==',$start_m,'==',$start_y;//exit;
$file_day = "email_day.csv";
$file_month = "email_month.csv";
$str_day = "日期,个数\n";
$str_month = "月份,总数,最大值,最小值,平均值\n";
file_put_contents($file_day,$str_day,FILE_APPEND );
file_put_contents($file_month,$str_month,FILE_APPEND );
//处理月份
while(true){
    $month = mktime(-8, 0, 0, $start_m, 1, '2013');
    $start_m++;
    $month_end = mktime(-8, 0, 0, $start_m, 1, '2013');
    get_email_count($month,$month_end,$file_day,$file_month);
    if($month>=time()){
        echo $start_m;
        echo 'end .....';
        exit;
    }
}

function get_email_count($time_start, $time_end,$file_day,$file_month){
    echo 1;
    $max = 0;
    $min = 9999999;
    $time_day = $time_start;
    $n = 0;
    while(true){
        $n++;
        $time_day_end = $time_day+86400;
        $sql_count_day = "SELECT count(*) FROM `firstp2p_deal_msg_list` WHERE send_type = 1 AND create_time >= {$time_day} AND create_time < {$time_day_end}";
        $count_day = $GLOBALS['db']->getOne($sql_count_day);
        if($count_day>$max){
            $max = $count_day;
        }
        if($count_day>0 && $count_day<$min){
            $min = $count_day;
        }
        $str_day = date('Y-m-d',$time_day+9*3600).','.$count_day."\n";
        file_put_contents($file_day,$str_day,FILE_APPEND);
        $time_day += 86400;
        if($time_day>=$time_end){
            break;
        }
    }
    $sql_sum = "SELECT count(*) FROM `firstp2p_deal_msg_list` WHERE send_type = 1 AND create_time >= {$time_start} AND create_time < {$time_end}";
    $sum = $GLOBALS['db']->getOne($sql_sum);
    if($n){
        $avg = ceil($sum/$n);
    }
    if($min == 9999999){
        $min = 0;
    }
    $str_m = date('Y-m',$time_end).",{$sum},{$max},{$min},{$avg}\n";
    file_put_contents($file_month,$str_m,FILE_APPEND );
}
