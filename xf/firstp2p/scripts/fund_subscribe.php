<?php
//crontab: 0 0  * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php fund_subscribe.php

/**
 * 每日基金预约人员统计汇总邮件
 *
 * @author yangqing <yangqing@ucfgroup.com> 2014年10月11日
 **/

require_once dirname(__FILE__).'/../app/init.php';
// require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

set_time_limit(0);

echo "run ...";

$time_start = get_gmtime() - (3600*24);   //从昨天的当前时间开始计算
$time_end = get_gmtime();     //到今天当前时间结束

echo "\nstart ".$time_start.' end '.$time_end;
/**
 * 一段计时周期内下的单，至今未完成支付的订单列表
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return mixed
 **/
function getList($time_start, $time_end){
    $sql = "SELECT * FROM ".DB_PREFIX."fund_subscribe WHERE `create_time`>{$time_start} AND `create_time`<={$time_end} ORDER BY `id` DESC";
    return $GLOBALS['db']->getAll($sql);
}

function getFundName($id){
    $sql = "SELECT name FROM ".DB_PREFIX."fund WHERE `id`={$id}";
    return $GLOBALS['db']->getOne($sql);
}

$list = getList($time_start, $time_end);

FP::import("libs.common.dict");
$email_list = dict::get("FUND_SUBSCRIBE_EMAIL_SUMMARY");

$fund_count = count($list);
$email_count = count($email_list);
echo "\nfund list count - ".$fund_count;
echo "\nemail list count - ".$email_count;

if(empty($email_count)){
    echo "\nemail sendlist is empty\n";
    exit();
}
if(empty($fund_count)){
    echo "\nfund subscribe list is empty\n";
    exit();
}
$Msgcenter = new Msgcenter();
$day = date('Y年m月d日',get_gmtime());
$title = $day.'基金预约汇总';
$content = '<table border=2>';

$content .= '<tr><th>预约顺序</th><th>姓名</th><th>预约项目名称</th><th>预约金额</th><th>手机号</th><th>备注</th></tr>';
foreach ($list as $data) {
   $content .= '<tr>'; 
   $content .= "<td>{$data['id']}</td>"; 
   $content .= "<td>{$data['realname']}</td>"; 
   $content .= '<td>'.getFundName($data['fund_id']).'</td>'; 
   $content .= "<td>{$data['money']}元</td>"; 
   $content .= "<td>{$data['phone']}</td>"; 
   $content .= "<td>{$data['comment']}</td>"; 
   $content .= '</tr>'; 
}
$count = 0;
foreach ($email_list as $email) {
    $count = $Msgcenter->setMsg($email, 0, $content, false, $title);
    echo "\ninsert success [{$count}] row";
}
$Msgcenter->save();
echo "\nsend email count - ".$count;
exit("\nfinish\n");

