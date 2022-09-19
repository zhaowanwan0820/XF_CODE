<?php
//每天的
//crontab: 0 1 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php statistics_everyday_new.php
//每周日 1点10分一次的
//crontab: 10 1 * * 1 cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php statistics_everyday_new.php 1

/**
 * 每日数据统计，统计结果通过邮件发送
 * @author 彭长路 2014年2月12日11:15:21
 * 
 **/

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

set_time_limit(0);


//昨日零点
//$yesterday_start = mktime(-8, 0, 0, date("m"), date("d")-1, date("Y"));
$yesterday_start = mktime(-8, 0, 0, 06, 24, date("Y"));
//今日零点
$today_start = mktime(-8, 0, 0, 06, 25, date("Y"));
$type = '日';
$data_type = 'daily';

$date = date("Y-m-d",$today_start);


//累计投资总额
$total_deal_load = total_deal_load();
$sum_of_user_money = sum_of_user_money();
//一段时间内的用户投标
$deal_load_time = deal_load_time($yesterday_start, $today_start);
//一段时间内的用户提现
$user_carry = user_carry($yesterday_start, $today_start);
//用户预约
$user_preset = user_preset($yesterday_start, $today_start);
//注册用户数
$num_of_user_reg = num_of_user_reg();
$num_of_user_reg_today = num_of_user_reg($yesterday_start, $today_start);

//累计资产总额
$total_deal = total_deal_time();
//当日投放总额
$total_deal_time = total_deal_time($yesterday_start, $today_start);
//当日还款款
$deal_load_repay = deal_load_repay($yesterday_start, $today_start);

//开始写入邮件队列
$Msgcenter = new Msgcenter();

$data = array(
    'title'=>$title_date,
    'type'=>$type,
	'date' => $date,
		
    'total_deal_load'=>$total_deal_load,
    'sum_of_user_money'=>$sum_of_user_money,
	'num_of_user_reg'=>$num_of_user_reg,
	'num_of_user_reg_today'=>$num_of_user_reg_today,
		
    'deal_load_time_money'=>format_num($deal_load_time['money']),
    'deal_load_time_counts'=>$deal_load_time['counts'],
    'deal_load_time_users'=>$deal_load_time['users'],
    'deal_load_time_avg'=>format_num($deal_load_time['avg']),
		
	'user_carry_money'=>format_num($user_carry['money']),
	'user_carry_counts'=>$user_carry['counts'],
	'user_carry_users'=>$user_carry['users'],
	'user_carry_avg'=>format_num($user_carry['avg']),
		
	'user_preset_money'=>format_num($user_preset['money']),
	'user_preset_counts'=>$user_preset['counts'],
	'user_preset_users'=>$user_preset['users'],
	'user_preset_avg'=>format_num($user_preset['avg']),
		
    'total_deal_money'=>format_num($total_deal['money']),
    'total_deal_counts'=>$total_deal['counts'],
    'total_deal_users'=>$total_deal['users'],
    'total_deal_avg'=>format_num($total_deal['avg']),
		
    'total_deal_time_money'=>format_num($total_deal_time['money']),
    'total_deal_time_counts'=>$total_deal_time['counts'],
    'total_deal_time_users'=>$total_deal_time['users'],
    'total_deal_time_avg'=>format_num($total_deal_time['avg']),
		
    'deal_load_repay_money'=>format_num($deal_load_repay['money']),
    'deal_load_repay_counts'=>$deal_load_repay['counts'],
    'deal_load_repay_users'=>$deal_load_repay['users'],
    'deal_load_repay_avg'=>format_num($deal_load_repay['avg']),
);


FP::import("libs.common.dict");
$url = app_conf('STATISTICS_EMAIL_URL');
//$url = "http://10.10.10.145/api/ncfrs/";//线上环境的post接口地址
//$url = "http://10.18.6.77/api/ncfrs/";//测试环境的post接口地址

if($url){
	post_data($data,$url,$data_type,$date);
}

$title = "网信理财 ".$title_date;

foreach (dict::get('STATISTICS_EMAIL_NEW') as $email) {
    $Msgcenter->setMsg($email, 0, $data, "TPL_STATISTICS_EVERYDAY_MAIL_NEW", $title);
}
$Msgcenter->save();

//投资人（资金方）相关
/**
 * 将浮点小数格式化
 *
 * @return string
 **/
function format_num($number)
{
	return sprintf("%2.2f", $number);
}
/**
 * 累计投资总额
 * @return float 总金额
 **/
function total_deal_load(){
	//$sql = "SELECT SUM(money) FROM ".DB_PREFIX."deal_load WHERE is_repay = 0 AND deal_parent_id != 0";
	$sql = "SELECT SUM(money) FROM ".DB_PREFIX."deal_load WHERE is_repay = 0 AND deal_parent_id != 0 AND deal_id NOT IN (SELECT id FROM ".DB_PREFIX."deal WHERE is_effect = 0 OR deal_status = 3 OR is_delete = 1)";
	return sprintf("%.2f",$GLOBALS['db']->getOne($sql));
}

/**
 * 一段时间内的用户投标
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return float 投标总金额
 **/
function deal_load_time($time_start, $time_end){
	//$sql = "SELECT SUM(money) as money,count(*) as counts,count(user_id) as users,avg(money) as avg   FROM ".DB_PREFIX."deal_load WHERE is_repay = 0 AND deal_parent_id != 0 and create_time > $time_start AND create_time < $time_end";
	$sql = "SELECT SUM(money) as money,count(*) as counts,count(user_id) as users,avg(money) as avg   FROM ".DB_PREFIX."deal_load WHERE is_repay = 0 AND deal_parent_id != 0 and create_time > $time_start AND create_time < $time_end";
	$data = $GLOBALS['db']->getRow($sql);
	$sql_user = "select count(*) from (SELECT user_id FROM ".DB_PREFIX."deal_load WHERE is_repay = 0 AND deal_parent_id != 0 and create_time > $time_start AND create_time < $time_end group by user_id) as t";
	$data['users'] =   $GLOBALS['db']->getOne($sql_user);
	return $data;
}
/**
 * 一段时间内的用户提现
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return float 总金额
 **/
function user_carry($time_start, $time_end){
	//$sql = "SELECT SUM(money) as money,count(*) as counts,count(user_id) as users,avg(money) as avg  FROM ".DB_PREFIX."user_carry WHERE type=1 and status in (0,1,3) AND create_time > $time_start and create_time < $time_end";
	$sql = "SELECT SUM(money) as money,count(*) as counts,count(user_id) as users,avg(money) as avg  FROM ".DB_PREFIX."user_carry WHERE type=1 and status in (0,1,3) AND user_id not in (SELECT user_id FROM ".DB_PREFIX."deal_agency) and  create_time > $time_start and create_time < $time_end";
	$data = $GLOBALS['db']->getRow($sql);
	$sql_user = "select count(*) from (SELECT user_id FROM ".DB_PREFIX."user_carry WHERE type=1 and status in (0,1,3) AND user_id not in (SELECT user_id FROM ".DB_PREFIX."deal_agency) AND create_time > $time_start and create_time < $time_end group by user_id) as t";
	$data['users'] =  $GLOBALS['db']->getOne($sql_user);
	return $data;
}
/**
 * 用户预约
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return float 总金额
 **/
function user_preset($time_start, $time_end){
	//$sql = "SELECT SUM(money) as money,count(*) as counts,count(mobile) as users,avg(money) as avg FROM ".DB_PREFIX."preset WHERE create_time > $time_start and create_time < $time_end";
	$sql = "SELECT SUM(money) as money,count(*) as counts,count(mobile) as users,avg(money) as avg FROM ".DB_PREFIX."preset WHERE create_time > $time_start and create_time < $time_end";
	$data = $GLOBALS['db']->getRow($sql);
	$sql_user = "select count(*) from (SELECT money FROM ".DB_PREFIX."preset WHERE create_time > $time_start and create_time < $time_end group by mobile) as t";
	$data['users'] =  $GLOBALS['db']->getOne($sql_user);
	return $data;
}
/**
 * 注册 用户数
 * @param int $time_start
 * @param int $time_end
 */
function num_of_user_reg($time_start=0, $time_end=0){
	if($time_start != 0){//累计
		$sql = "select count(*) from ".DB_PREFIX."user where is_effect = 1 and is_delete = 0 and create_time >= $time_start and create_time < $time_end";
	}else{
		$sql = "select count(*) from ".DB_PREFIX."user where is_effect = 1 and is_delete = 0";
	}
	return (int)($GLOBALS['db']->getOne($sql));
}
/**
 * @author changlu
 * 充值后，当前时间未使用，留存在账户的余额总数
 */
function sum_of_user_money(){
	$sql = "select sum(money) from ".DB_PREFIX."user where is_effect = 1 and is_delete = 0 and money>0 and id not in (SELECT user_id FROM ".DB_PREFIX."deal_agency)";
	return sprintf("%.2f",$GLOBALS['db']->getOne($sql));
}

//借款人（资产方）相关

/**
 * 一段时间内的借款
 * @param int $time_start 开始时间unix时间戳
 * @param int $time_end 结束时间unix时间戳
 * @return float 借款总金额
 **/
function total_deal_time($time_start=0, $time_end=0,$where="")
{
	//parent_id!=0统计所有母标以外的标(普通标以及所有子标)
	if($time_start == 0){//总的
		$sql = "select sum(borrow_amount) as money,count(*) as counts,count(user_id) as users,avg(borrow_amount) as avg FROM ".DB_PREFIX."deal where is_delete = 0 and publish_wait=0 and parent_id != 0 and deal_status != 3";
		$data = $GLOBALS['db']->getRow($sql);
		$sql_user = "select count(*) from (select user_id FROM ".DB_PREFIX."deal where is_delete = 0 and parent_id != 0 and deal_status != 3 and publish_wait=0 group by user_id) as t";
		$data['users'] =  $GLOBALS['db']->getOne($sql_user);
		return $data;
	}else{//当日
		//$sql = "select sum(borrow_amount) as money,count(*) as counts,count(user_id) as users,avg(borrow_amount) as avg FROM ".DB_PREFIX."deal where is_effect = 1 and  is_delete = 0 and publish_wait=0 and create_time > $time_start and create_time < $time_end and parent_id != 0 and deal_status != 3";
		$sql = "select sum(borrow_amount) as money,count(*) as counts,count(user_id) as users,avg(borrow_amount) as avg FROM ".DB_PREFIX."deal AS d LEFT JOIN ".DB_PREFIX."deal_ext AS e ON d.id = e.deal_id where is_effect = 1 and  is_delete = 0 and publish_wait=0 and publish_time > $time_start and publish_time < $time_end and parent_id != 0 and deal_status != 3";
		$data = $GLOBALS['db']->getRow($sql);
		//$sql_user = "select count(*) from (select user_id FROM ".DB_PREFIX."deal where is_effect = 1 and is_delete = 0 and create_time > $time_start and create_time < $time_end and parent_id != 0 and deal_status != 3 and publish_wait=0 group by user_id) as t";
		$sql_user = "select count(*) from (select user_id FROM ".DB_PREFIX."deal AS d LEFT JOIN ".DB_PREFIX."deal_ext AS e ON d.id = e.deal_id where is_effect = 1 and is_delete = 0 and publish_time > $time_start and publish_time < $time_end and parent_id != 0 and deal_status != 3 and publish_wait=0 group by user_id) as t";
		$data['users'] =  $GLOBALS['db']->getOne($sql_user);
		return $data;
	}
}
/**
 * 一段时间的还款
 * @param unknown $time_start
 * @param unknown $time_end
 */
function deal_load_repay($time_start, $time_end){
	$data = array();
	$sql_sum = "SELECT SUM(money) AS money FROM ".DB_PREFIX."deal_loan_repay WHERE STATUS = 1 AND TYPE IN (1,2,3,4,5,7) AND real_time > $time_start and real_time < $time_end ";
	$data['money'] = $GLOBALS['db']->getOne($sql_sum);
	//订单数
	$sql_num = "SELECT COUNT(*) AS counts FROM (SELECT deal_id FROM ".DB_PREFIX."deal_loan_repay WHERE STATUS = 1 AND TYPE IN (1,2,3,4,5,7) AND real_time > $time_start and real_time < $time_end GROUP BY deal_id) AS t";
	$data['counts'] = $GLOBALS['db']->getOne($sql_num);
	//还款人数 
	$sql_user = "SELECT COUNT(*) AS counts FROM (SELECT deal_id FROM ".DB_PREFIX."deal_loan_repay WHERE STATUS = 1 AND TYPE IN (1,2,3,4,5,7) AND real_time > $time_start and real_time < $time_end GROUP BY borrow_user_id) AS t";
	$data['users'] = $GLOBALS['db']->getOne($sql_user);
	
	if($data['counts']){
		$data['avg'] = $data['money']/$data['counts'];
	}
	//$sql = "select sum(repay_money) as money,count(*) as counts,count(user_id) as users,avg(repay_money) as avg FROM ".DB_PREFIX."deal_load_repay where true_repay_time > $time_start and true_repay_time < $time_end";
	return $data;
}
/**
 * post 请求接口数据
 * @param array $data post 数据
 * @param string $url 接口地址
 * 
 */
function post_data($data,$url,$type="daily",$date=''){
	if(!$data || !$url){
		return false; 
	}
	if(!$date){
		$date = date("Y-m-d");
	}
	$data = array(
	'pid' => 'ncf',//固定值
	'prd' => 'firstp2p',//固定值
	'ainfo'	=> $type,//日数据：daily，周数据：week
	'date'=> $date,//数据日期，date("Y-m-d")
	'data'=> json_encode($data),
	);
	$data['sign'] = md5("ABC123".$data['date'].$data['data']);//数据校验码
	$data_str = http_build_query($data);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
	$output = curl_exec($ch);
	if (curl_errno($ch)){
		echo curl_error($ch);
	} else {
		echo $output;
	}
	curl_close($ch);
}