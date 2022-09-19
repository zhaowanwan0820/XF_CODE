<?php
//crontab: 0 1 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php statistics_everyday.php

/**
 * 每日数据统计，统计结果通过邮件发送
 * 此脚本 2014-02-26 开始停止运行 by liubaikui
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com> 2013年09月23日 16:48:30 
 * @version $Id$
 **/

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

set_time_limit(0);

//昨日零点
$yesterday_start = mktime(0, 0, 0, date("m"), date("d")-1, date("Y"));
//今日零点
$today_start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));

//$yesterday_start = mktime(0, 0, 0, 9, 29, 2013);
//$today_start = mktime(0, 0, 0, 10, 10, 2013);

/**
 * 一段时间内的充值总金额
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @param int $status  支付状态：0 未支付 1 部分支付 2 全部支付 $status=2时视为支付成功，其他视为失败
 * @return float 总金额
 **/
function total_deal_order($time_start, $time_end, $status = null)
{
    $sql = "select sum(deal_total_price) from ".DB_PREFIX."deal_order where create_time > $time_start and create_time < $time_end";
    if(isset($status))
    {
        $sql .= " and pay_status = $status";
    }
    return sprintf("%.2f",$GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的充值总次数
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @param int $status  支付状态：0 未支付 1 部分支付 2 全部支付 $status=2时视为支付成功，其他视为失败
 * @return int 总次数
 **/
function num_of_deal_order($time_start, $time_end, $status = null)
{ 
    $sql = "select count(0) from ".DB_PREFIX."deal_order where create_time > $time_start and create_time < $time_end";
    if(isset($status))
    {
        $sql .= " and pay_status = $status";
    }
    return sprintf("%.2f",$GLOBALS['db']->getOne($sql));
}
/**
 * @author changlu
 * 二次及以上充值后才成功的用户数
 */
function num_of_user_repay_ok_before_lost2($time_start, $time_end){
	$num = 0;
	$sql = "SELECT COUNT(*) AS c ,user_id, MAX(create_time) AS create_time FROM ".DB_PREFIX."deal_order WHERE (pay_status = 0 OR pay_status = 1) and create_time > $time_start and create_time <= $time_end GROUP BY user_id HAVING c>=2 ";
	$list = $GLOBALS['db']->getAll($sql);
	if(empty($list)){
		return 0;
	}
	foreach($list as $v){
		$sql_c = "SELECT COUNT(*) FROM ".DB_PREFIX."deal_order WHERE pay_status = 2 and user_id = ".$v['user_id']." and create_time >".$v['create_time'];
		$c = (int)($GLOBALS['db']->getOne($sql_c));
		if($c){
			$num ++;
		}
	}
	return $num;
}

/**
 * 一段时间内的预约总金额
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return float 总金额
 **/
function total_preset($time_start, $time_end)
{
    $sql = "select sum(money) from ".DB_PREFIX."preset where create_time > $time_start and create_time < $time_end";
    return (float)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的预约总数
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return int
 **/
function num_of_preset($time_start, $time_end)
{
    $sql = "select count(0) from ".DB_PREFIX."preset where create_time > $time_start and create_time < $time_end";
    return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的预约总人数, 以手机号为人数判断标准
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return int 总人数
 **/
function num_of_people_preset($time_start, $time_end)
{
    $sql = "select count(distinct mobile) from ".DB_PREFIX."preset where create_time > $time_start and create_time < $time_end";
    return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的借款总金额
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return float 借款总金额
 **/
function total_deal($time_start, $time_end)
{
    //parent_id!=0统计所有母标以外的标(普通标以及所有子标)
    $sql = "select sum(borrow_amount) from ".DB_PREFIX."deal where create_time > $time_start and create_time < $time_end and parent_id != 0";
    return (float)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的借款总数
 *
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return int 借款总数
 **/
function num_of_deal($time_start, $time_end)
{
    //parent_id!=0统计所有母标以外的标(普通标以及所有子标)
    $sql = "select count(0) from ".DB_PREFIX."deal where create_time > $time_start and create_time < $time_end and parent_id != 0";
    return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的借款总人数
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return int 借款总人数
 **/
function num_of_people_deal($time_start, $time_end)
{
    $sql = "select count(distinct user_id) from ".DB_PREFIX."deal where create_time > $time_start and create_time < $time_end";
    return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的投标总金额
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return float 投标总金额
 **/
function total_deal_load($time_start, $time_end)
{
    $sql = "select sum(money) from ".DB_PREFIX."deal_load where create_time > $time_start and create_time < $time_end";
    return (float)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的投标总数
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return int 投标总数
 **/
function num_of_deal_load($time_start, $time_end)
{
    $sql = "select count(0) from ".DB_PREFIX."deal_load where create_time > $time_start and create_time < $time_end";
    return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的投标总人数
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return int 投标总人数
 **/
function num_of_people_deal_load($time_start, $time_end)
{ 
    $sql = "select count(distinct user_id) from ".DB_PREFIX."deal_load where create_time > $time_start and create_time < $time_end";
    return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的还款总金额
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return float 还款总金额
 **/
function total_deal_repay($time_start, $time_end)
{
    $sql = "select sum(repay_money) from ".DB_PREFIX."deal_repay where true_repay_time > $time_start and true_repay_time < $time_end";
    return (float)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的还款次数
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return int 还款次数
 **/
function num_of_deal_repay($time_start, $time_end)
{
    $sql = "select count(0) from ".DB_PREFIX."deal_repay where true_repay_time > $time_start and true_repay_time < $time_end";
    return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的还款总人数
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return int 总人数
 **/
function num_of_people_deal_repay($time_start, $time_end)
{
    $sql = "select count(distinct user_id) from ".DB_PREFIX."deal_repay where true_repay_time > $time_start and true_repay_time < $time_end";
    return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * @author changlu 
 * 充值后，当前时间未使用，留存在账户的余额总数
 */
function sum_of_user_money(){
	$sql = "select sum(money) from ".DB_PREFIX."user where is_effect = 1 and is_delete = 0";
	return sprintf("%.2f",$GLOBALS['db']->getOne($sql));
}
/**
 * @author changlu 
 * 充值后，留存在账户的冻结余额总数
 */
function sum_of_user_money_locked(){
	$sql = "select sum(lock_money) from ".DB_PREFIX."user where is_effect = 1 and is_delete = 0";
	return sprintf("%.2f",$GLOBALS['db']->getOne($sql));
}

/**
 * 注册 用户数
 * @param int $time_start
 * @param int $time_end
 */
function num_of_user_reg($time_start, $time_end){
	$sql = "select count(*) from ".DB_PREFIX."user where create_time >= $time_start and create_time < $time_end";
	return (int)($GLOBALS['db']->getOne($sql));
}

/**
 * 将浮点小数转换为百分比数字
 *
 * @return string
 **/
function format_percent($number)
{
    return sprintf("%2.2f", $number * 100);
}



/**************充值总金额*******************/
//昨日充值总金额
$total_deal_order = total_deal_order($yesterday_start, $today_start);
//昨日充值成功总金额
$total_deal_order_succeed = total_deal_order($yesterday_start, $today_start, 2);
//昨日充值成功总金额百分比
$total_deal_order_succeed_percent = $total_deal_order_succeed / $total_deal_order;
//昨日充值失败总金额
$total_deal_order_failed = $total_deal_order - $total_deal_order_succeed;
//昨日充值失败总金额百分比
$total_deal_order_failed_percent = $total_deal_order_failed / $total_deal_order;

/**************充值次数*******************/
//昨日充值总次数
$num_of_deal_order = num_of_deal_order($yesterday_start, $today_start);
//昨日充值成功总次数
$num_of_deal_order_succeed = num_of_deal_order($yesterday_start, $today_start, 2);
//昨日充值成功总次数百分比
$num_of_deal_order_succeed_percent = $num_of_deal_order_succeed / $num_of_deal_order;
//昨日充值失败总次数
$num_of_deal_order_failed = $num_of_deal_order - $num_of_deal_order_succeed;
//昨日充值失败总次数百分比
$num_of_deal_order_failed_percent = $num_of_deal_order_failed / $num_of_deal_order;

/**************预约相关*******************/
//昨日预约总金额
$total_preset = total_preset($yesterday_start, $today_start);
//昨日预约总数
$num_of_preset = num_of_preset($yesterday_start, $today_start);
//昨日预约总人数
$num_of_people_preset = num_of_people_preset($yesterday_start, $today_start);

/**************借款相关*******************/
//昨日借款总金额
$total_deal = total_deal($yesterday_start, $today_start);
//昨日借款总数
$num_of_deal = num_of_deal($yesterday_start, $today_start);
//昨日借款总人数
$num_of_people_deal = num_of_people_deal($yesterday_start, $today_start);

/**************投标相关*******************/
//昨日投标总金额
$total_deal_load = total_deal_load($yesterday_start, $today_start);
//昨日投标总数
$num_of_deal_load = num_of_deal_load($yesterday_start, $today_start);
//昨日投标人数
$num_of_people_deal_load = num_of_people_deal_load($yesterday_start, $today_start);

/**************还款相关*******************/
//昨日还款总金额
$total_deal_repay = total_deal_repay($yesterday_start, $today_start);
//昨日还款总次数
$num_of_deal_repay = num_of_deal_repay($yesterday_start, $today_start);
//昨日还款总人数
$num_of_people_deal_repay= num_of_people_deal_repay($yesterday_start, $today_start);
//充值后，当前时间未使用，留存在账户的余额总数
$sum_of_user_money = sum_of_user_money();
//充值后，留存在账户的冻结余额总数
$sum_of_user_money_locked = sum_of_user_money_locked();
//二次及以上充值后才成功的用户数
$num_of_user_repay_ok_before_lost2 = num_of_user_repay_ok_before_lost2($yesterday_start, $today_start);


//开始写入邮件队列
$Msgcenter = new Msgcenter();
$title_date = to_date(get_gmtime() - 24 * 60 * 60, "Y年m月d日");
$data = array(
    "date"=>$title_date,
    "total_deal_order"=>$total_deal_order,
    "total_deal_order_succeed"=>$total_deal_order_succeed,
    "total_deal_order_succeed_percent"=>format_percent($total_deal_order_succeed_percent),
    "total_deal_order_failed"=>$total_deal_order_failed,
    "total_deal_order_failed_percent"=>format_percent($total_deal_order_failed_percent),
    "num_of_deal_order"=>$num_of_deal_order,
    "num_of_deal_order_succeed"=>$num_of_deal_order_succeed,
    "num_of_deal_order_succeed_percent"=>format_percent($num_of_deal_order_succeed_percent),
    "num_of_deal_order_failed"=>$num_of_deal_order_failed,
    "num_of_deal_order_failed_percent"=>format_percent($num_of_deal_order_failed_percent),
    "total_preset"=>$total_preset,
    "num_of_preset"=>$num_of_preset,
    "num_of_people_preset"=>$num_of_people_preset,
    "total_deal"=>$total_deal,
    "num_of_deal"=>$num_of_deal,
    "num_of_people_deal"=>$num_of_people_deal,
    "total_deal_load"=>$total_deal_load,
    "num_of_deal_load"=>$num_of_deal_load,
    "num_of_people_deal_load"=>$num_of_people_deal_load,
    "total_deal_repay"=>$total_deal_repay,
    "num_of_deal_repay"=>$num_of_deal_repay,
    "num_of_people_deal_repay"=>$num_of_people_deal_repay,
		
    "sum_of_user_money"=>$sum_of_user_money,
    "sum_of_user_money_locked"=>$sum_of_user_money_locked,
    "num_of_user_repay_ok_before_lost2"=>$num_of_user_repay_ok_before_lost2,
		
    "num_of_user_reg"=>num_of_user_reg($yesterday_start, $today_start),
);
$title = "网信理财 ".$title_date." 概况";

FP::import("libs.common.dict");
foreach (dict::get('STATISTICS_EMAIL') as $email) {
    $Msgcenter->setMsg($email, 0, $data, "TPL_STATISTICS_EVERYDAY_MAIL", $title);
}
$Msgcenter->save();
