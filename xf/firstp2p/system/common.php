<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

//前后台加载的函数库
require_once 'system_init.php';

/**
 * PMT年金计算函数
 * @param $i 期间收益率
 * @param $n 期数
 * @param $p 本金
 * @return 每期应还金额
 */
function PMT($i, $n, $p) {
	//return $i * $p * pow((1 + $i), $n) / (1 - pow((1 + $i), $n));
	return $p * $i * pow((1 + $i), $n) / ( pow((1 + $i), $n) -1);
}

/**
 * 年化收益率计算
 * @param $pn 期末金额
 * @param $n 期数
 * @param $p 本金
 * @return 期间收益率
 */
function RATE($pn, $n, $p) {
	return pow($pn/$p, 1/$n)-1;
}

//获取真实路径
function get_real_path()
{
	return APP_ROOT_PATH;
}

//获取GMTime
function get_gmtime()
{
	return (time() - date('Z'));
}

function format_date($utc_time, $format = 'Y-m-d H:i:s') {
	if (empty ( $utc_time )) {
		return '';
	}
	return date ($format, $utc_time );
}

function to_date($utc_time, $format = 'Y-m-d H:i:s') {
	if (empty ( $utc_time )) {
		return '';
	}
	$timezone = intval(app_conf('TIME_ZONE'));
	$time = $utc_time + $timezone * 3600;
	return date ($format, $time );
}

function to_timespan($str, $format = 'Y-m-d H:i:s')
{
	$timezone = intval(app_conf('TIME_ZONE'));
	//$timezone = 8;
	$time = intval(strtotime($str));
	if($time!=0)
	$time = $time - $timezone * 3600;
    return $time;
}

/**
 * 下个还款日
 */
function next_replay_month($time){
	$y = to_date($time,"Y");
	$m = to_date($time,"m");
	$d = to_date($time,"d");
	if($m == 12){
		++$y;
		$m = 1;
	}
	else{
		++$m;
	}

	return to_timespan($y."-".$m."-".$d,"Y-m-d");
}

/**
 * 下个还款日
 */
function next_replay_month_with_delta($time, $delta_month_time){
	$y = to_date($time,"Y");
	$m = to_date($time,"m");
	$d = to_date($time,"d");
	$target_m = $m + $delta_month_time;
	if($target_m > 12){
		++$y;

	}
	$m = $target_m % 12;
	if ($m == 0) {
		$m = 12;
	}

	return to_timespan($y."-".$m."-".$d,"Y-m-d");
}

function next_replay_day_with_delta($time, $day){
	$y = to_date($time,"Y");
	$m = intval(to_date($time,"m"));
	$d = intval(to_date($time,"d"));

	$td  = mktime(0, 0, 0, $m, $d +$day, $y);
	return $td;
}

//获取客户端IP
function get_client_ip() {
	if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
		$ip = getenv ( "HTTP_CLIENT_IP" );
	else if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
		$ip = getenv ( "HTTP_X_FORWARDED_FOR" );
	else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
		$ip = getenv ( "REMOTE_ADDR" );
	else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
		$ip = $_SERVER ['REMOTE_ADDR'];
	else
		$ip = "unknown";
	return ($ip);
}

function get_real_ip() {
	$ip = false;
	if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
		if ($ip) {
			array_unshift($ips, $ip);
			$ip = FALSE;
		}
		for ($i = 0; $i < count($ips); $i++) {
			if (!eregi("^(10│172.16│192.168).", $ips[$i])) {
				$ip = $ips[$i];
				break;
			}
		}
	}
	if ($ip) {
		return $ip;
	} else {
		if (isset($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		} else {
			return 'unknown';
		}
	}
}

//过滤注入
function filter_injection(&$request)
{
	$pattern = "/(select[\s])|(insert[\s])|(update[\s])|(delete[\s])|(from[\s])|(where[\s])/i";
	foreach($request as $k=>$v)
	{
				if(preg_match($pattern,$k,$match))
				{
						die("SQL Injection denied!");
				}

				if(is_array($v))
				{
					filter_injection($v);
				}
				else
				{

					if(preg_match($pattern,$v,$match))
					{
						die("SQL Injection denied!");
					}
				}
	}

}

//过滤请求
function filter_request(&$request)
{
		if(MAGIC_QUOTES_GPC)
		{
			foreach($request as $k=>$v)
			{
				if(is_array($v))
				{
					filter_request($v);
				}
				else
				{
					$request[$k] = stripslashes(trim($v));
				}
			}
		}

}

function adddeepslashes(&$request)
{

			foreach($request as $k=>$v)
			{
				if(is_array($v))
				{
					adddeepslashes($v);
				}
				else
				{
					$request[$k] = addslashes(trim($v));
				}
			}
}

//request转码
function convert_req(&$req)
{
	foreach($req as $k=>$v)
	{
		if(is_array($v))
		{
			convert_req($req[$k]);
		}
		else
		{
			if(!is_u8($v))
			{
				$req[$k] = iconv("gbk","utf-8",$v);
			}
		}
	}
}

function is_u8($string)
{
	return preg_match('%^(?:
		 [\x09\x0A\x0D\x20-\x7E]            # ASCII
	   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
   )*$%xs', $string);
}

//清除缓存
function clear_cache()
{
		//系统后台缓存
		syn_dealing();
		clear_dir_file(get_real_path()."public/runtime/admin/Cache/");
		clear_dir_file(get_real_path()."public/runtime/admin/Data/_fields/");
		clear_dir_file(get_real_path()."public/runtime/admin/Temp/");
		clear_dir_file(get_real_path()."public/runtime/admin/Logs/");
		@unlink(get_real_path()."public/runtime/admin/~app.php");
		@unlink(get_real_path()."public/runtime/admin/~runtime.php");
		@unlink(get_real_path()."public/runtime/admin/lang.js");
		@unlink(get_real_path()."public/runtime/app/config_cache.php");


		//数据缓存
		clear_dir_file(get_real_path()."public/runtime/app/data_caches/");
		clear_dir_file(get_real_path()."public/runtime/app/db_caches/");
		$GLOBALS['cache']->clear();
		clear_dir_file(get_real_path()."public/runtime/data/");

		//模板页面缓存
		clear_dir_file(get_real_path()."public/runtime/app/tpl_caches/");
		clear_dir_file(get_real_path()."public/runtime/app/tpl_compiled/");
		@unlink(get_real_path()."public/runtime/app/lang.js");

		//脚本缓存
		clear_dir_file(get_real_path()."public/runtime/statics/");



}
function clear_dir_file($path)
{
   if ( $dir = opendir( $path ) )
   {
            while ( $file = readdir( $dir ) )
            {
                $check = is_dir( $path. $file );
                if ( !$check )
                {
                    @unlink( $path . $file );
                }
                else
                {
                 	if($file!='.'&&$file!='..')
                 	{
                 		clear_dir_file($path.$file."/");
                 	}
                 }
            }
            closedir( $dir );
            rmdir($path);
            return true;
   }
}

//同步未过期团购的状态
function syn_dealing()
{
	$deals = $GLOBALS['db']->getAll("select id from ".DB_PREFIX."deal where is_effect = 1 and deal_status not in (3,5) and is_delete = 0 AND load_money/borrow_amount <= 1");
	foreach($deals as $v)
	{
		syn_deal_status($v['id']);
	}
}

function check_install()
{
	if(!file_exists(get_real_path()."public/install.lock"))
	{
	    clear_cache();
		header('Location:'.APP_ROOT.'/install');
		return;
	}
}

function syn_brand_status($id)
{
	//同步品牌状态
	$brand_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."brand where id = ".$id);
	//1 无开始与结束时间
	if($brand_info['begin_time']==0&&$brand_info['end_time']==0)
	{
		if($deal_info['time_status']!=0)
		{
			$GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 0 where id =".$id);
		}
		return 0;
	}

	//2 无开始时间，有结束时间
	if($brand_info['begin_time']==0&&$brand_info['end_time']!=0)
	{

		//进行中
		if($brand_info['end_time']>get_gmtime())
		{
			if($brand_info['time_status']!=0)
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 0 where id =".$id);
			}
			return 0;
		}
		//过期
		if($brand_info['end_time']<=get_gmtime())
		{
			if($brand_info['time_status']!=2)
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 2 where id =".$id);
			}
			return 2;
		}
	}

	//3 有开始时间，无结束时间
	if($brand_info['begin_time']!=0&&$brand_info['end_time']==0)
	{
		//进行中
		if($brand_info['begin_time']<=get_gmtime())
		{
			if($brand_info['time_status']!=0)
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 0 where id =".$id);
			}
			return 0;
		}
		//未开始
		if($brand_info['begin_time']>get_gmtime())
		{
			if($brand_info['time_status']!=1)
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 1 where id =".$id);
			}
			return 1;
		}
	}

	//4 开始结束都有时间
	if($brand_info['begin_time']!=0&&$brand_info['end_time']!=0)
	{
		//未开始
		if($brand_info['begin_time']>get_gmtime())
		{
			if($brand_info['time_status']!=1)
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 1 where id =".$id);
			}
			return 1;
		}
		//进行中
		if($brand_info['begin_time']<=get_gmtime()&&$brand_info['end_time']>get_gmtime())
		{
			if($brand_info['time_status']!=0)
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 0 where id =".$id);
			}
			return 0;
		}
		//过期

		if($brand_info['end_time']<=get_gmtime())
		{
			if($brand_info['time_status']!=2)
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 2 where id =".$id);
			}
			return 2;
		}
	}
}

function ceilfix($number, $bits=2) {
	$t = pow(10, $bits);
	//echo $t,'<br>';
	if($t==0) return 0;
	return floatval(ceil($number * $t)) / $t;
}

//同步XXID的团购商品的状态,time_status,buy_status
function syn_deal_status($id)
{
	$deal_info = $GLOBALS['db']->getRow("select *,(start_time + enddate*24*3600 - ".get_gmtime().") as remain_time,(point_percent*100) as progress_point from ".DB_PREFIX."deal where id = ".$id);
	$deal_info['name'] = get_deal_title($deal_info['name'], '', $deal_info['id']);

	if($deal_info['deal_status'] == 5){
		return true;
	}

	if($deal_info['deal_status']!=3){
		//if($deal_info['progress_point'] <100){
		//	$data['load_money'] = $GLOBALS['db']->getOne("SELECT sum(money) FROM ".DB_PREFIX."deal_load  WHERE deal_id=$id ");
		//	$data['progress_point'] = $deal_info['progress_point'] = round($data['load_money']/$deal_info['borrow_amount']*100,2);
		//}
		$data['progress_point'] = $deal_info['point_percent'] * 100;
		// print $deal_info['progress_point'];
		// print ",";
		// print $data['progress_point'];
		// print ",";


		if($deal_info['progress_point'] >=100 || $data['progress_point'] >=100){

			if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal_inrepay_repay WHERE deal_id=$id") >0){
				$data['deal_status'] = 5;
				$data['last_repay_time'] = $GLOBALS['db']->getOne("SELECT true_repay_time FROM ".DB_PREFIX."deal_inrepay_repay WHERE deal_id=$id");
			}
			//判断是否是借款状态还是已还款完毕
			elseif(($deal_info['deal_status']==4&&$deal_info['repay_start_time']>0) || ($deal_info['deal_status']==2 && $deal_info['repay_start_time']>0 && $deal_info['repay_start_time'] <= get_gmtime())){

				// 根据借款人的还款记录获得他实际已经还了的钱
				$sql = "SELECT sum(repay_money) As all_repay_money ,MAX(repay_time) AS last_repay_time FROM ".DB_PREFIX."deal_repay WHERE deal_id=$id";

				$repay_info =  $GLOBALS['db']->getRow($sql);
				if($repay_info){
					// 总共已还的钱
					$data['repay_money'] = $repay_info['all_repay_money'];
					// 最近的一次还款时间
					$data['last_repay_time'] = $repay_info['last_repay_time'];
				}
				//判断是否完成还款
				require_once APP_ROOT_PATH."app/Lib/deal.php";

                                if($deal_info['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
                $has_repay_money = get_deal_total_repay_money_month_interest($deal_info['loantype'], $deal_info['repay_time'], $deal_info['borrow_amount'], $deal_info['parent_id'], $deal_info['rate']);
                                } else if($deal_info['id'] > $GLOBALS['dict']['OLD_DEAL_ID']) {
					$has_repay_money = get_deal_total_repay_money_from_pmt($deal_info['id']);
                                }
				else {
					$has_repay_money  = get_deal_total_repay_money($deal_info['loantype'], $deal_info['repay_time'], $deal_info['borrow_amount'], $deal_info['parent_id'],$deal_info['rate']);
                                }

				// if($data['loantype'] == 0){
					// $has_repay_money = av_it_formula($deal_info['borrow_amount'],$deal_info['rate']/12/100) * $deal_info['repay_time'] + $deal_info['borrow_amount'];
				// }
				// elseif($data['loantype'] == 1)
				// {
					// $has_repay_money = pl_it_formula($deal_info['borrow_amount'],$deal_info['rate']/12/100,$deal_info['repay_time'])* $deal_info['repay_time'];
				// }

				// print("repay_money: ");
				// print($data['repay_money']);
				// print("has_repay_money: ");
				// print($has_repay_money);
				if($data['repay_money'] > 0 && $data['repay_money'] >= round($has_repay_money,2)){
					$data['deal_status'] = 5;
				}
				else{
					$data['deal_status'] = 4;
				}
			}
			else{
				//获取最后一次的投资记录
				if($deal_info['success_time'] == 0){
					$data['success_time'] = $deal_info['success_time'] = $GLOBALS['db']->getOne("SELECT create_time FROM ".DB_PREFIX."deal_load WHERE deal_id=$id ORDER BY id DESC");
				}

				$data['deal_status'] = 2;
			}

			// 如果是子标，可能情况是该子标已经满标但其他子标被设为了流标，这样需要判断其他子标状态是不是流标，因为母标也会被设为满标状态
			if($deal_info['parent_id'] > 0)
			{
				$sql = "SELECT *,(start_time + enddate*24*3600 - ".get_gmtime().") as remain_time,(point_percent*100) as progress_point FROM ".DB_PREFIX."deal WHERE parent_id=" . $deal_info['parent_id'] . ' ORDER BY load_money DESC';
				$sub_deal_list = $GLOBALS['db']->getAll($sql);
				// 如果有其他子标已经流标则设置该子标也为流标
				foreach($sub_deal_list as $sub_info)
				{
					if($sub_info['remain_time'] <= 0 && ( $sub_info['deal_status']==1 || $sub_info['deal_status']==3 ) )
					{
						$data['deal_status'] = 3;
						$data['bad_time'] = $deal_info['start_time'] + $deal_info['enddate']*24*3600;
						break;
					}
				}
			}

		}
		elseif($deal_info['remain_time'] <= 0 && $deal_info['deal_status']==1){
			//投资时间超出 更新为流标
			$data['deal_status'] = 3;
			$data['bad_time'] = $deal_info['start_time'] + $deal_info['enddate']*24*3600;

		}
		//进行中可以改为等待确认 20140324
		/* elseif($deal_info['remain_time'] > 0 && $deal_info['deal_status']==0){
			$data['deal_status'] = 1;
		} */
	}

	//投资人数
	$sdata = $GLOBALS['db']->getRow("SELECT count(*) as buy_count,sum(money) as load_money FROM ".DB_PREFIX."deal_load WHERE deal_id=$id");
	$data['buy_count'] = $sdata['buy_count'];
	$data['load_money'] = $sdata['load_money'];

	#echo $deal_info['deal_status'] . '@'. $data['deal_status'] . '---------->' .$deal_info['parent_id'] . '@' .$deal_info['remain_time'];

	//流标
	if($deal_info['deal_status'] ==3 || $data['deal_status']==3){
		//流标时返还
		require_once APP_ROOT_PATH."system/libs/user.php";
		$r_load_list = $GLOBALS['db']->getAll("SELECT id,user_id,money FROM ".DB_PREFIX."deal_load WHERE is_repay=0 AND deal_id=$id AND from_deal_id=0");//只针对子单和未拆的单进行返还

		foreach($r_load_list as $k=>$v){
			// TODO finance 前台老系统 流标返还
			modify_account(array("lock_money"=>-$v['money']),$v['user_id'],"流标返还",1,'编号'.$deal_info['id'].' '.$deal_info['name']);
			$GLOBALS['db']->query("UPDATE ".DB_PREFIX."deal_load SET is_repay=1 WHERE id=".$v['id']);
		}


		if($deal_info['is_send_bad_msg']==0){
			$data['is_send_bad_msg'] = 1;

			$is_send = 1;
			// 如果是子单则判断其他子单是否已经发过
			if($deal_info['parent_id'] > 0)
			{
				$subdeal_list = get_sub_deal_list_by_parentid($deal_info['parent_id']);
				foreach($subdeal_list as $sub_info)
				{
					if($sub_info['is_send_bad_msg'] == 1)
					{
						$is_send = 0;
					}
				}

				// 如果是子单流标则也把母单设置为流标
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal", array('deal_status' => 3),"UPDATE","id=". $deal_info['parent_id']);
			}

			if($is_send)
				send_full_failed_deal_message($deal_info, 'failed');


			// 如果是母单流标则同步子标信息
			if($deal_info['parent_id'] == 0)
			{
				$subdeallist = get_sub_deal_list_by_parentid($deal_info['id']);
				foreach($subdeallist as $sub_info)
				{
					syn_deal_status($sub_info['id']);
				}
			}

		}

		/* 已使用send_full_failed_deal_message方法发送消息
		//发送流标通知
		if($deal_info['is_send_bad_msg']==0){
			$data['is_send_bad_msg'] = 1;
			//发邮件
			send_deal_faild_mail($id,$deal_info,$deal_info['user_id']);
			//站内信
			send_deal_faild_site_sms($id,$deal_info,$deal_info['user_id']);

			//添加到动态
			insert_topic("deal_bad",$id,$deal_info['user_id'],get_user_name($deal_info['user_id'],false),0);
		}
		*/
	}


	//放款给借款人
    //放款移动至后台，状态改为还款中时打款及扣款 by zrs
    /**
	if($deal_info['is_has_loans']==0 && $data['deal_status']==4 && $deal_info['parent_id']!=0){
		$data['is_has_loans'] = 1;
		require_once APP_ROOT_PATH."system/libs/user.php";
		modify_account(array("money"=>$deal_info['borrow_amount']),$deal_info['user_id'],"招标成功");
		//此时要一次性扣除服务费和担保费
		//$services_fee = $deal_info['borrow_amount']*floatval(trim($deal_info['services_fee']))/100;
		$services_fee = $deal_info['borrow_amount'] * (floatval($deal_info['loan_fee_rate']) + floatval($deal_info['guarantee_fee_rate'])) / 100.0;
		modify_account(array("money"=>-$services_fee),$deal_info['user_id'],"服务费和担保费");

	}
     *
     */

	$GLOBALS['db']->autoExecute(DB_PREFIX."deal",$data,"UPDATE","id=".$id);

	/*
	 * by liubaikui
	//自动投资功能
	if(($deal_info['deal_status'] == 1 || $data['deal_status']==1) && $deal_info['remain_time'] >0){
		//point
		$user_level_id =  $GLOBALS['db']->getOne("SELECT level_id FROM  ".DB_PREFIX."user WHERE id = ".$deal_info['user_id']);
		$level = load_auto_cache("level");
		$deal_user_point = $level['point'][$user_level_id];
		if (empty($deal_user_point)) {
			$deal_user_point = 0;
		}
		$sql = "SELECT usa.user_id,usa.fixed_amount,u.user_name FROM ".DB_PREFIX."user_autobid usa LEFT JOIN ".DB_PREFIX."user u ON u.id=usa.user_id " .
				"WHERE usa.fixed_amount >=".$deal_info['min_loan_money']." AND usa.is_effect = 1 " .
				"AND u.money-usa.retain_amount >= usa.fixed_amount " .
				"AND ".$deal_info['rate']." between usa.min_rate AND usa.max_rate " .
				"AND ".$deal_info['repay_time']." between usa.min_period AND usa.max_period " .
				"AND usa.user_id not in (SELECT user_id FROM ".DB_PREFIX."deal_load WHERE deal_id=$id) " .
				"AND $deal_user_point between (SELECT point FROM ".DB_PREFIX."user_level WHERE id = usa.min_level) AND (SELECT point FROM ".DB_PREFIX."user_level WHERE id = usa.max_level) " .
				"AND usa.fixed_amount <=".($deal_info['borrow_amount'] - $data['load_money'])." AND MOD(usa.fixed_amount,".$deal_info['min_loan_money'].")=0 ".
				"GROUP BY usa.user_id ORDER BY usa.last_bid_time ASC";

		$autobid_user = $GLOBALS['db']->getRow($sql);
		//开始投资
		if($autobid_user)
		{
			$biddata['user_id'] = $autobid_user['user_id'];
			$biddata['user_name'] = $autobid_user['user_name'];
			$biddata['deal_id'] = $id;
			$biddata['money'] = $autobid_user['fixed_amount'];
			$biddata['create_time'] = get_gmtime();

			$GLOBALS['db']->autoExecute(DB_PREFIX."deal_load",$biddata,"INSERT");
			$load_id = $GLOBALS['db']->insert_id();
			if($load_id > 0){
				require_once APP_ROOT_PATH."system/libs/user.php";
				$msg = sprintf('编号%s的投资,付款单号%s[自动]',$id,$load_id);
				modify_account(array("money"=>-$autobid_user['fixed_amount']),$autobid_user['user_id'],$msg);
				$GLOBALS['db']->query("UPDATE ".DB_PREFIX."user_autobid SET last_bid_time=".get_gmtime()." WHERE user_id=".$autobid_user['user_id']);
			}
		}
	} */

}
/*
//放款操作
function make_loans($id){
	$deal_info = get_deal($id);//$GLOBALS['db']->getRow("select *,(start_time + enddate*24*3600 - ".get_gmtime().") as remain_time,(point_percent*100) as progress_point from ".DB_PREFIX."deal where id = ".$id);
	if($deal_info['is_has_loans']==0 && $data['deal_status']==4 && $deal_info['parent_id']!=0){
		$data['is_has_loans'] = 1;
		require_once APP_ROOT_PATH."system/libs/user.php";
		modify_account(array("money"=>$deal_info['borrow_amount']),$deal_info['user_id'],"招标成功");
		//此时要一次性扣除服务费和担保费
		//$services_fee = $deal_info['borrow_amount']*floatval(trim($deal_info['services_fee']))/100;
		$services_fee = $deal_info['borrow_amount'] * (floatval($deal_info['loan_fee_rate']) + floatval($deal_info['guarantee_fee_rate'])) / 100.0;
		modify_account(array("money"=>-$services_fee),$deal_info['user_id'],"服务费和担保费");
		$GLOBALS['db']->autoExecute(DB_PREFIX."deal",$data,"UPDATE","id=".$id);
	}
}*/

//更新用户统计
function sys_user_status($user_id,$is_cache = false,$make_cache=false,$site_id=1){
	if($user_id == 0)
		return ;
	$data = false;
	/* if($make_cache == false){
		if($is_cache == true){
			$key = md5("USER_STATICS_".$user_id);
			$data = load_dynamic_cache($key);
		}
	} */
	if($data==false){
		//留言数
		$data['dp_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."message WHERE user_id=$user_id AND is_effect = 1");
		//总借款额
		$data['borrow_amount'] = $GLOBALS['db']->getOne("SELECT sum(borrow_amount) FROM ".DB_PREFIX."deal WHERE deal_status in(4,5) AND user_id=$user_id AND publish_wait = 0 AND parent_id != 0");
		//已还本息
		$data['repay_amount'] = $GLOBALS['db']->getOne("SELECT sum(repay_money) FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id");

		//发布借款笔数
		$data['deal_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal WHERE user_id=$user_id AND publish_wait = 0 AND parent_id != 0 AND is_delete = 0");
		//成功借款笔数
		$data['success_deal_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal WHERE deal_status in (4,5) AND user_id=$user_id AND publish_wait = 0 AND parent_id != 0");
		//还清笔数
		$data['repay_deal_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal WHERE deal_status = 5 AND user_id=$user_id AND publish_wait = 0 AND parent_id != 0");
		//未还清笔数
		$data['wh_repay_deal_count'] = $data['success_deal_count'] - $data['repay_deal_count'];
		//提前还清笔数
		$data['tq_repay_deal_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal_inrepay_repay WHERE user_id=$user_id");
		//正常还清笔数
		$data['zc_repay_deal_count'] = $data['repay_deal_count'] - $data['tq_repay_deal_count'];
		//加权平均借款利率
		$data['avg_rate'] = $GLOBALS['db']->getOne("SELECT sum(rate)/count(*) FROM ".DB_PREFIX."deal WHERE deal_status in (4,5) AND user_id=$user_id AND publish_wait = 0");
		//平均每笔借款金额
		$data['avg_borrow_amount'] = $data['borrow_amount'] / $data['success_deal_count'];

		//逾期本息
		$data['yuqi_amount'] = $GLOBALS['db']->getOne("SELECT (sum(repay_money) + sum(impose_money)) as new_amount FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id AND status in(2,3)");
		//逾期费用
		$data['yuqi_impose'] = $GLOBALS['db']->getOne("SELECT sum(repay_money) FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id AND status in(2,3)");

		//逾期次数
		$data['yuqi_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id AND status = 2");
		//严重逾期次数
		$data['yz_yuqi_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id AND status = 3");

		//待还本息
		$data['need_repay_amount'] = 0;
		//待还管理费
		$data['need_manage_amount'] = 0;
		$deals = $GLOBALS['db']->getOne("SELECT id FROM ".DB_PREFIX."deal WHERE deal_status in(4,5) AND user_id=$user_id AND publish_wait = 0");
		if($deals){
			require_once APP_ROOT_PATH."app/Lib/deal.php";
			foreach($deals as $k=>$v){
				$deal = get_deal($v['id']);
				$loan= get_deal_load_list($deal);
				foreach($loan as $kk=>$vv){
					if($vv['status']==0)
					{
						$data['need_repay_amount'] +=$vv['month_repay_money'];
						$data['need_manage_amount'] +=$vv['month_manage_money'];
					}
				}
			}
		}

		//按月付息到期还款方式
		$sql_load_info_month = "SELECT (sum(repay_money)-sum(manage_money)+sum(impose_money)) as load_earnings,sum(repay_money) AS load_repay_money FROM ".DB_PREFIX."deal_load_repay WHERE user_id=$user_id and deal_id in (SELECT DISTINCT repay.deal_id AS deal_id FROM `".DB_PREFIX."deal_load_repay` AS repay LEFT JOIN `".DB_PREFIX."deal` AS deal ON repay.deal_id = deal.id  WHERE repay.user_id=$user_id and deal.loantype =".$GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY'] .")";
		$load_info_month = $GLOBALS['db']->getRow($sql_load_info_month);
		//其他还款方式
		$sql_load_info = "SELECT (sum(repay_money)-sum(self_money)-sum(manage_money)+sum(impose_money)) as load_earnings,sum(repay_money) AS load_repay_money FROM ".DB_PREFIX."deal_load_repay WHERE user_id=$user_id and deal_id in (SELECT DISTINCT repay.deal_id AS deal_id FROM `".DB_PREFIX."deal_load_repay` AS repay LEFT JOIN `".DB_PREFIX."deal` AS deal ON repay.deal_id = deal.id  WHERE repay.user_id=$user_id and deal.loantype !=".$GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY'] .")";
		$load_info = $GLOBALS['db']->getRow($sql_load_info);

		//之前的处理 $load_info = $GLOBALS['db']->getRow("SELECT (sum(repay_money)-sum(self_money)-sum(manage_money)+sum(impose_money)) as load_earnings,sum(repay_money) AS load_repay_money FROM ".DB_PREFIX."deal_load_repay WHERE user_id=$user_id");

		//需要修正的按月付息到期还款方式 deal list
		$sql_deal_list = "SELECT repay.deal_id AS deal_id , deal.loantype AS loantype,deal.repay_time,self_money FROM `".DB_PREFIX."deal_load_repay` AS repay LEFT JOIN `".DB_PREFIX."deal` AS deal ON repay.deal_id = deal.id  WHERE repay.user_id=$user_id and deal.loantype =".$GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY'];
		$deal_list = $GLOBALS['db']->getAll($sql_deal_list);
		$temp_arr = array();
		foreach($deal_list as $v){
			if($temp_arr[$v['deal_id']]) {
				$temp_arr[$v['deal_id']]['count'] ++;
			}else{
				$temp_arr[$v['deal_id']]['count'] = 1;
				$temp_arr[$v['deal_id']]['repay_time'] = $v['repay_time'];
				$temp_arr[$v['deal_id']]['self_money'] = $v['self_money'];
			}
		}
		//已回收本息
		$data['load_repay_money'] = $load_info['load_repay_money'] +$load_info_month['load_repay_money'];
		//已赚利息
		$data['load_earnings'] = $load_info['load_earnings'] +  $load_info_month['load_earnings'];
		//需要修正的按月付息到期还款方式
		if($temp_arr){
			foreach($temp_arr as $k=>$v){
				if($v['count'] >= $v['repay_time']){
					$data['load_earnings'] = $data['load_earnings'] -$v['repay_time']*$v['self_money'];
				}
			}
		}
		//已赚提前还款违约金
		$data['load_tq_impose'] = $GLOBALS['db']->getOne("SELECT sum(impose_money) FROM ".DB_PREFIX."deal_load_repay WHERE status = 0 AND user_id=$user_id");
		//已赚逾期罚息
		$data['load_yq_impose'] = $GLOBALS['db']->getOne("SELECT sum(impose_money) FROM ".DB_PREFIX."deal_load_repay WHERE status in (2,3) AND user_id=$user_id");

		//借出加权平均收益率
		$data['load_avg_rate'] = $GLOBALS['db']->getOne("SELECT sum(rate)/count(*) FROM ".DB_PREFIX."deal_load dl LEFT JOIN ".DB_PREFIX."deal d ON d.id=dl.deal_id WHERE d.deal_status in(4,5) AND dl.user_id=$user_id");

		//总借出笔数    edit by wenyanlei  20130710  去掉查询条件 ：d.deal_status in(4,5) AND
		$u_load = $GLOBALS['db']->getRow("SELECT count(*) as load_count,sum(dl.money) as load_money FROM ".DB_PREFIX."deal_load dl LEFT JOIN ".DB_PREFIX."deal d ON d.id=dl.deal_id WHERE dl.site_id=$site_id AND dl.user_id=$user_id AND d.parent_id!=0 AND d.deal_status!=3");
		$data['load_count'] = $u_load['load_count'];
		//总借出金额
		$data['load_money'] = $u_load['load_money'];


		//已回收笔数
		$sql = "SELECT count(*)  FROM ".DB_PREFIX."deal_load dl LEFT JOIN ".DB_PREFIX."deal d ON d.id=dl.deal_id WHERE d.deal_status =5 AND dl.user_id=$user_id AND dl.deal_parent_id!=0";

		$data['reback_load_count'] = $GLOBALS['db']->getOne($sql);

		//待回收笔数
		$data['wait_reback_load_count'] = $data['load_count'] - $data['reback_load_count'];

		//待回收本息
		$data['load_wait_repay_money'] = 0;

		if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."user_sta WHERE user_id=".$user_id) > 0)
			$GLOBALS['db']->autoExecute(DB_PREFIX."user_sta",$data,"UPDATE","user_id=".$user_id);
		else{
			$data['user_id'] = $user_id;
			$GLOBALS['db']->autoExecute(DB_PREFIX."user_sta",$data,"INSERT");
		}

		if($data['deal_count'] > 0 || $data['load_count']){
			if($data['deal_count'] > 0)
				$u_data['is_borrow_in'] = 1;
			if($data['load_count'] > 0)
				$u_data['is_borrow_out'] = 1;
			$GLOBALS['db']->autoExecute(DB_PREFIX."user",$u_data,"UPDATE","id=".$user_id);
		}
		if($is_cache == true || $make_cache == true){
			set_dynamic_cache($key,$data);
		}
	}
	return $data;
}

//发放团购券
function send_deal_coupon($deal_coupon_id)
{
	$GLOBALS['db']->query("update ".DB_PREFIX."deal_coupon set is_valid = 1 where id = ".$deal_coupon_id." and user_id <> 0 and is_delete = 0 and is_valid = 0");
	$rs = $GLOBALS['db']->affected_rows();
	if($rs)
	{
		//发邮件团购券
		send_deal_coupon_mail($deal_coupon_id);
		//发短信团购券
		send_deal_coupon_sms($deal_coupon_id);
	}
}

//发送流标通知邮件
function send_deal_faild_mail($deal_id,$deal_info=false,$user_id){
	send_full_failed_deal_message($deal_id, "failed");
	/*
	if(!$deal_info && $deal_id ==0)
		return false;

	if(app_conf('MAIL_ON')==0)
		return false;

	if(!$deal_info)
		$deal_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$deal_id);


	if(intval($deal_info['is_send_bad_msg'])==1)
		return false;

	$msg_conf = get_user_msg_conf($user_id);

	if($msg_conf['mail_myfail'] == 1 || !$msg_conf){
		$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$deal_info['user_id']);
		$tmpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_DEAL_FAILED'");
		$tmpl_content = $tmpl['content'];

		$notice['user_name'] = $user_info['user_name'];
		$notice['deal_name'] = $deal_info['name'];
		$notice['deal_publish_time'] = to_date($deal_info['create_time'],"Y年m月d日");
		$notice['site_name'] = app_conf("SHOP_TITLE");
		$notice['site_url'] = get_domain().APP_ROOT;
		$notice['send_deal_url'] = get_domain().url("index","borrow");
		$notice['help_url'] = get_domain().url("index","helpcenter");
		$notice['msg_cof_setting_url'] = get_domain().url("index","uc_msg#setting");
		$notice['bad_msg'] = $deal_info['bad_msg'];

		$GLOBALS['tmpl']->assign("notice",$notice);

		$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
		$msg_data['dest'] = $user_info['email'];
		$msg_data['send_type'] = 1;
		$msg_data['title'] = "您的借款列表“".$deal_info['name']."”已流标！";
		$msg_data['content'] = addslashes($msg);
		$msg_data['send_time'] = 0;
		$msg_data['is_send'] = 0;
		$msg_data['create_time'] = get_gmtime();
		$msg_data['user_id'] = $user_info['id'];
		$msg_data['is_html'] = $tmpl['is_html'];
		$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
	}

	//获取投资列表
	$load_user_list = $GLOBALS['db']->getAll("SELECT user_name,user_id,create_time FROM ".DB_PREFIX."deal_load WHERE deal_id=".$deal_info['id']);
	if($load_user_list){
		$load_tmpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_LOAD_FAILED'");
		foreach($load_user_list as $k=>$v){
			$user_info = $GLOBALS['db']->getRow("select email from ".DB_PREFIX."user where id = ".$v['user_id']);
			$load_msg_conf = get_user_msg_conf($v['user_id']);
			if($load_msg_conf['mail_myfail'] == 1){
				$tmpl_content = $load_tmpl['content'];
				$notice['user_name'] = $v['user_name'];
				$notice['deal_name'] = $deal_info['name'];
				$notice['deal_url'] = get_domain().$deal_info['url'];
				$notice['deal_load_time'] = to_date($v['create_time'],"Y年m月d日");
				$notice['site_name'] = app_conf("SHOP_TITLE");
				$notice['site_url'] = get_domain().APP_ROOT;
				$notice['help_url'] = get_domain().url("index","helpcenter");
				$notice['msg_cof_setting_url'] = get_domain().url("index","uc_msg#setting");
				$notice['bad_msg'] = $deal_info['bad_msg'];

				$GLOBALS['tmpl']->assign("notice",$notice);

				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				$msg_data['dest'] = $user_info['email'];
				$msg_data['send_type'] = 1;
				$msg_data['title'] = "您的所投的借款列表“".$deal_info['name']."”已流标！";
				$msg_data['content'] = addslashes($msg);
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] =  $v['user_id'];
				$msg_data['is_html'] = $load_tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
			}
		}
	}
	*/
}

/**
 * 发送满标or流标相关Message
 *
 * @param  $deal 标信息
 * @param  $type full:满标, failed：流标
 * @return int 写入数据数量
 */
function send_full_failed_deal_message($deal,$type){
	if(empty($deal)) return false;

	if($type == 'full'){
		$mail_title = "满标";
		$message_deal_status = "满标，请到个人中心查看合同并确认！";
		$mail_tpl = "TPL_DEAL_FULL_EMAIL";
		$sms_tpl = "TPL_SMS_DEAL_FULL";
	}elseif ($type == 'failed'){
		$mail_title = "流标提示";
		$message_deal_status = "流标";
		$mail_tpl = "TPL_DEAL_FAILED_MAIL";
		$sms_tpl = "TPL_DEAL_FAILED_SMS";
	}

	$deal['url'] = url("index","deal",array("id"=>$deal['id']));

	$Msgcenter = new Msgcenter();

	if($deal['parent_id'] == -1){  //如果是普通单子
		$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$deal['user_id']);
		#####################  给借款人发送信息   ######################
		//邮件
		$notice_mail = array(
			'user_name' =>$user_info['real_name'],
			'deal_url' => get_domain().$deal['url'],
			'deal_name' => $deal['name'],
			'help_url' => get_domain().url("index","helpcenter"),
			'site_url' => get_domain().$deal['url'],
			'site_name' => app_conf("SHOP_TITLE"),
			'msg_cof_setting_url' => get_domain().url("index","uc_msg#setting"),
            'do'=>"申请的",
            'send_deal_url' => get_domain().url('index','borrow#aboutborrow'),
		);

		$Msgcenter->setMsg($user_info['email'], $deal['user_id'], $notice_mail, $mail_tpl,$mail_title,NULL,get_deal_domain_title($deal['id']));

		//站内信
		$content = "<p>您申请的借款“<a href=\"".$deal['url']."\">".$deal['name']."</a>”已经".$message_deal_status;

		if($type == 'failed'){
		    send_user_msg($mail_title,$content,0,$deal['user_id'],get_gmtime(),0,true,10);
		}else{
		    send_user_msg($mail_title,$content,0,$deal['user_id'],get_gmtime(),0,true,16);
		}

        // 判断是否是企业用户
        if ($user_info['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
        {
            $_mobile = 'enterprise';
            $userName = get_company_shortname($user_info['id']); // by fanjingwen
        } else {
            $_mobile = $user_info['mobile'];
            $userName = $user_info['user_name'];
        }

		//短信
		$notice_sms = array(
			'user_name' => $userName,
			'deal_name' => $deal['name'],
            'site_name' => app_conf("SHOP_TITLE"),
			'do' => "申请的",
		);

		$Msgcenter->setMsg($_mobile,$deal['user_id'], $notice_sms, $sms_tpl,NULL,'',get_deal_domain_title($deal['id']));

	//如果是子单
	}elseif($deal['parent_id'] > 0){
		//根据母单ID获取所有子单ID
		$sub_deal_list = get_sub_deal_list_by_parentid($deal['parent_id']);
		foreach ($sub_deal_list as $sub_deal){

            //提前放款的标，需要将无人投资的子标单独处理成流标，有人投的子标满标，在此根据子标状态单独判断发送的类型
            if ($sub_deal['deal_status'] == '3'){
                $mail_title = "流标提示";
                $message_deal_status = "流标";
                $mail_tpl = "TPL_DEAL_FAILED_MAIL";
                $sms_tpl = "TPL_DEAL_FAILED_SMS";
            }

            $user_info = get_user_info($sub_deal['user_id'],TRUE);  //下次改成单独获取。
			$deal_url = url("index","deal",array("id"=>$sub_deal['id']));

			#####################  给借款人发送信息   ######################
			//邮件
			$notice_mail = array(
				'user_name' =>$user_info['user_name'],
				'deal_url' => get_domain().$deal_url,
				'deal_name' => $sub_deal['name'],
				'help_url' => get_domain().url("index","helpcenter"),
				'site_url' => get_domain().$sub_deal['url'],
				'site_name' => app_conf("SHOP_TITLE"),
				'msg_cof_setting_url' => get_domain().url("index","uc_msg#setting"),
                'do'=>"申请的",
                'send_deal_url' => get_domain().url('index','borrow#aboutborrow'),
			);
			$Msgcenter->setMsg($user_info['email'], $sub_deal['user_id'], $notice_mail, $mail_tpl,$mail_title,'',get_deal_domain_title($sub_deal['id']));
			//站内信
			$content = "<p>您的借款“<a href=\"".$deal_url."\">".$sub_deal['name']."</a>”已经".$message_deal_status;

			if($type == 'failed')
				send_user_msg($mail_title,$content,0,$sub_deal['user_id'],get_gmtime(),0,true,10);
			else
				send_user_msg($mail_title,$content,0,$sub_deal['user_id'],get_gmtime(),0,true,16);

            // 判断是否是企业用户
            if ($user_info['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
            {
                $_mobile = 'enterprise';
                $userName = get_company_shortname($user_info['id']); // by fanjingwen
            } else {
                $_mobile = $user_info['mobile'];
                $userName = $user_info['user_name'];
            }
			//短信
			$notice_sms = array(
				'user_name' => $userName,
				'deal_name' => $sub_deal['name'],
				'do' => "申请的",
			);
			$Msgcenter->setMsg($_mobile,$sub_deal['user_id'], $notice_sms, $sms_tpl,'','',get_deal_domain_title($sub_deal['id']));
		}
	}
	//写入数据
	return $Msgcenter->save();
}

//发送流标站内信
function send_deal_faild_site_sms($deal_id,$deal_info=false,$user_id){
	if(!$deal_info && $deal_id ==0)
		return false;

	if(!$deal_info){
		$deal_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$deal_id);
	    $deal_info['name'] = get_deal_title($deal_info['name'], '', $deal_id);
	}

	if(intval($deal_info['is_send_bad_msg'])==1)
		return false;

	$msg_conf = get_user_msg_conf($user_id);

	if($msg_conf['sms_myfail'] == 1){
		$content = "<p>感谢您使用".app_conf("SHOP_TITLE")."贷款融资，但有一些遗憾的通知您，您于".to_date($deal_info['create_time'],"Y年m月d日")."发布的借款列表";
		$content .= "<a href=\"".url("index","deal",array("id"=>$deal_info['id']))."\">“".$deal_info['name']."”</a>流标，导致您本次贷款列表流标的原因可能包括的原因：</p>";
		$content .= $deal_info['bad_msg'];
		send_user_msg("",$content,0,$user_id,get_gmtime(),0,true,10);
	}

	//获取投资列表
	$load_user_list = $GLOBALS['db']->getAll("SELECT user_name,user_id,create_time FROM ".DB_PREFIX."deal_load WHERE deal_id=".$deal_info['id']);
	if($load_user_list){
		foreach($load_user_list as $k=>$v){
			$user_info = $GLOBALS['db']->getRow("select email from ".DB_PREFIX."user where id = ".$v['user_id']);
			$load_msg_conf = get_user_msg_conf($v['user_id']);
			if($load_msg_conf['sms_myfail'] == 1 || !$load_msg_conf){
				$content = "<p>感谢您使用".app_conf("SHOP_TITLE")."贷款融资，但有一些遗憾的通知您，您于".to_date($v['create_time'],"Y年m月d日")."投资的借款列表";
				$content .= "“<a href=\"".url("index","deal",array("id"=>$deal_info['id']))."\">".$deal_info['name']."</a>”流标，导致您本次所投的贷款列表流标的原因可能包括的原因：</p>";
				$content .= "1. 借款者没能按时提交四项必要信用认证的材料。<br>2. 借款者在招标期间没有筹集到足够的借款。";
				send_user_msg("",$content,0,$v['user_id'],get_gmtime(),0,true,11);
			}
		}
	}
}

//发邮件团购券
function send_deal_coupon_mail($deal_coupon_id)
{
	if(app_conf("MAIL_ON")==1&&app_conf("MAIL_SEND_COUPON")==1)
	{
		$coupon_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_coupon where id = ".$deal_coupon_id);
		if($coupon_data)
		{
			$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_COUPON'");
			$tmpl_content = $tmpl['content'];
			$coupon_data['begin_time_format'] = $coupon_data['begin_time']==0?$GLOBALS['lang']['NO_BEGIN_TIME']:to_date($coupon_data['begin_time'],'Y-m-d');
			$coupon_data['end_time_format'] = $coupon_data['end_time']==0?$GLOBALS['lang']['NO_END_TIME']:to_date($coupon_data['end_time'],'Y-m-d');
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$coupon_data['user_id']);
			$coupon_data['user_name'] = $user_info['user_name'];
			$coupon_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
			$coupon_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
					$deal_id = $coupon_data['deal_id'];
					if(!$coupon_data['deal_name']||!$coupon_data['deal_sub_name'])
					{
						$deal_info = $GLOBALS['db']->getRow("select name,sub_name from ".DB_PREFIX."deal where id = ".$deal_id);
						if(!$coupon_data['deal_name'])
						$coupon_data['deal_name'] = $deal_info['name'];
						if(!$coupon_data['deal_sub_name'])
						$coupon_data['deal_sub_name'] = $deal_info['sub_name'];
					}
			$order_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order_item where id = ".intval($coupon_data['order_deal_id']));
			$deal_type = intval($GLOBALS['db']->getOne("select deal_type from ".DB_PREFIX."deal where id = ".intval($order_item['deal_id'])));
			if($deal_type == 1&&$order_item)
			{
					$coupon_data['deal_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
					$coupon_data['deal_sub_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
			}

			$GLOBALS['tmpl']->assign("coupon",$coupon_data);
			$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
			$msg_data['dest'] = $user_info['email'];
			$msg_data['send_type'] = 1;
			$msg_data['title'] = $GLOBALS['lang']['YOU_GOT_COUPON'];
			$msg_data['content'] = addslashes($msg);
			$msg_data['send_time'] = 0;
			$msg_data['is_send'] = 0;
			$msg_data['create_time'] = get_gmtime();
			$msg_data['user_id'] = $user_info['id'];
			$msg_data['is_html'] = $tmpl['is_html'];
			$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入

		}
	}
}

//发短信团购券
function send_deal_coupon_sms($deal_coupon_id)
{
	if(app_conf("SMS_ON")==1&&app_conf("SMS_SEND_COUPON")==1)
	{
		$coupon_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_coupon where id = ".$deal_coupon_id);
		if($coupon_data)
		{
			$forbid_sms = intval($GLOBALS['db']->getOne("select forbid_sms from ".DB_PREFIX."deal where id = ".$coupon_data['deal_id']));
			if($forbid_sms==0)
			{
				$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$coupon_data['user_id']);
				if($user_info['mobile']!='')
				{
					$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_COUPON'");
					$tmpl_content = $tmpl['content'];
					$coupon_data['begin_time_format'] = $coupon_data['begin_time']==0?$GLOBALS['lang']['NO_BEGIN_TIME']:to_date($coupon_data['begin_time'],'Y-m-d');
					$coupon_data['end_time_format'] = $coupon_data['end_time']==0?$GLOBALS['lang']['NO_END_TIME']:to_date($coupon_data['end_time'],'Y-m-d');
					$coupon_data['user_name'] = $user_info['user_name'];
					$coupon_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
					$coupon_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
					$deal_id = $coupon_data['deal_id'];
					if(!$coupon_data['deal_name']||!$coupon_data['deal_sub_name'])
					{
						$deal_info = $GLOBALS['db']->getRow("select name,sub_name from ".DB_PREFIX."deal where id = ".$deal_id);
						if(!$coupon_data['deal_name'])
						$coupon_data['deal_name'] = $deal_info['name'];
						if(!$coupon_data['deal_sub_name'])
						$coupon_data['deal_sub_name'] = $deal_info['sub_name'];
					}
					$order_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order_item where id = ".intval($coupon_data['order_deal_id']));
					$deal_type = intval($GLOBALS['db']->getOne("select deal_type from ".DB_PREFIX."deal where id = ".intval($order_item['deal_id'])));
					if($deal_type == 1&&$order_item)
					{
						$coupon_data['deal_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
						$coupon_data['deal_sub_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
					}


					$GLOBALS['tmpl']->assign("coupon",$coupon_data);
					$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
					$msg_data['dest'] = $user_info['mobile'];
					$msg_data['send_type'] = 0;
					$msg_data['content'] = addslashes($msg);;
					$msg_data['send_time'] = 0;
					$msg_data['is_send'] = 0;
					$msg_data['create_time'] = get_gmtime();
					$msg_data['user_id'] = $user_info['id'];
					$msg_data['is_html'] = $tmpl['is_html'];
					$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
				}
			}
		}
	}
}


//发团购券确认使用的短信
function send_use_coupon_sms($deal_coupon_id)
{
	if(app_conf("SMS_ON")==1&&app_conf("SMS_USE_COUPON")==1)
	{
		$coupon_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_coupon where id = ".$deal_coupon_id);
		if($coupon_data)
		{
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$coupon_data['user_id']);
			if($user_info['mobile']!='')
			{
				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_USE_COUPON'");
				$tmpl_content = $tmpl['content'];
				$coupon_data['confirm_time_format'] = to_date($coupon_data['confirm_time'],'Y-m-d H:i:s');
				$coupon_data['user_name'] = $user_info['user_name'];
				$coupon_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
				$coupon_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
					$deal_id = $coupon_data['deal_id'];
					if(!$coupon_data['deal_name']||!$coupon_data['deal_sub_name'])
					{
						$deal_info = $GLOBALS['db']->getRow("select name,sub_name from ".DB_PREFIX."deal where id = ".$deal_id);
						if(!$coupon_data['deal_name'])
						$coupon_data['deal_name'] = $deal_info['name'];
						if(!$coupon_data['deal_sub_name'])
						$coupon_data['deal_sub_name'] = $deal_info['sub_name'];
					}
				$order_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order_item where id = ".intval($coupon_data['order_deal_id']));
				$deal_type = intval($GLOBALS['db']->getOne("select deal_type from ".DB_PREFIX."deal where id = ".intval($order_item['deal_id'])));
				if($deal_type == 1&&$order_item)
				{
					$coupon_data['deal_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
					$coupon_data['deal_sub_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
				}
				$GLOBALS['tmpl']->assign("coupon",$coupon_data);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				$msg_data['dest'] = $user_info['mobile'];
				$msg_data['send_type'] = 0;
				$msg_data['content'] = addslashes($msg);;
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = $tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
			}
		}
	}
}


//发团购券确认使用的邮件
function send_use_coupon_mail($deal_coupon_id)
{
	if(app_conf("MAIL_ON")==1&&app_conf("MAIL_USE_COUPON")==1)
	{
		$coupon_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_coupon where id = ".$deal_coupon_id);
		if($coupon_data)
		{
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$coupon_data['user_id']);

				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_USE_COUPON'");
				$tmpl_content = $tmpl['content'];
				$coupon_data['confirm_time_format'] = to_date($coupon_data['confirm_time'],'Y-m-d H:i:s');
				$coupon_data['user_name'] = $user_info['user_name'];
				$coupon_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
				$coupon_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
					$deal_id = $coupon_data['deal_id'];
					if(!$coupon_data['deal_name']||!$coupon_data['deal_sub_name'])
					{
						$deal_info = $GLOBALS['db']->getRow("select name,sub_name from ".DB_PREFIX."deal where id = ".$deal_id);
						if(!$coupon_data['deal_name'])
						$coupon_data['deal_name'] = $deal_info['name'];
						if(!$coupon_data['deal_sub_name'])
						$coupon_data['deal_sub_name'] = $deal_info['sub_name'];
					}
				$order_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order_item where id = ".intval($coupon_data['order_deal_id']));
				$deal_type = intval($GLOBALS['db']->getOne("select deal_type from ".DB_PREFIX."deal where id = ".intval($order_item['deal_id'])));
				if($deal_type == 1&&$order_item)
				{
					$coupon_data['deal_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
					$coupon_data['deal_sub_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
				}
				$GLOBALS['tmpl']->assign("coupon",$coupon_data);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				$msg_data['dest'] = $user_info['email'];
				$msg_data['send_type'] = 1;
				$msg_data['content'] = addslashes($msg);;
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = $tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入

		}
	}
}


//发短信抽奖
function send_lottery_sms($lottery_id)
{
	if(app_conf("SMS_ON")==1&&app_conf("LOTTERY_SN_SMS")==1&&$lottery_id>0)
	{
		$lottery_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."lottery where id = ".$lottery_id);
		if($lottery_data)
		{
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$lottery_data['user_id']);

				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_LOTTERY'");
				$tmpl_content = $tmpl['content'];
				$lottery_data['user_name'] = $user_info['user_name'];
				$lottery_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal where id = ".$lottery_data['deal_id']);
				$lottery_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal where id = ".$lottery_data['deal_id']);

				$GLOBALS['tmpl']->assign("lottery",$lottery_data);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				$msg_data['dest'] = $lottery_data['mobile'];
				$msg_data['send_type'] = 0;
				$msg_data['content'] = addslashes($msg);;
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = $tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入

		}
	}
}

//发注册验证邮件
function send_user_verify_mail($user_id)
{
	if(app_conf("MAIL_ON")==1)
	{
		$verify_code = rand(111111,999999);
		$GLOBALS['db']->query("update ".DB_PREFIX."user set verify = '".$verify_code."' where id = ".$user_id);
		$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
		if($user_info)
		{
			$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_USER_VERIFY'");
			$tmpl_content=  $tmpl['content'];
			$user_info['verify_url'] = get_domain().url("index","user#verify",array("id"=>$user_info['id'],"code"=>$user_info['verify']));
			$GLOBALS['tmpl']->assign("user",$user_info);
			$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
			$msg_data['dest'] = $user_info['email'];
			$msg_data['send_type'] = 1;
			$msg_data['title'] = $GLOBALS['lang']['REGISTER_SUCCESS'];
			$msg_data['content'] = addslashes($msg);;
			$msg_data['send_time'] = 0;
			$msg_data['is_send'] = 0;
			$msg_data['create_time'] = get_gmtime();
			$msg_data['user_id'] = $user_info['id'];
			$msg_data['is_html'] = $tmpl['is_html'];
			$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
		}
	}
}


//发密码验证邮件
function send_user_password_mail($user_id)
{
	if(app_conf("MAIL_ON")==1)
	{
		$verify_code = rand(111111,999999);
		$GLOBALS['db']->query("update ".DB_PREFIX."user set password_verify = '".$verify_code."' where id = ".$user_id);
		$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
		if($user_info)
		{
			$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_USER_PASSWORD'");
			$tmpl_content=  $tmpl['content'];
			$user_info['password_url'] = get_domain().url("index","user#modify_password", array("code"=>$user_info['password_verify'],"id"=>$user_info['id']));
			$GLOBALS['tmpl']->assign("user",$user_info);
			$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
			$msg_data['dest'] = $user_info['email'];
			$msg_data['send_type'] = 1;
			$msg_data['title'] = $GLOBALS['lang']['RESET_PASSWORD'];
			$msg_data['content'] = addslashes($msg);
			$msg_data['send_time'] = 0;
			$msg_data['is_send'] = 0;
			$msg_data['create_time'] = get_gmtime();
			$msg_data['user_id'] = $user_info['id'];
			$msg_data['is_html'] = $tmpl['is_html'];
			$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
		}
	}
}


//发短信收款单
function send_payment_sms($notice_id)
{
	if(app_conf("SMS_ON")==1&&app_conf("SMS_SEND_PAYMENT")==1)
	{
		$notice_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$notice_id);
		if($notice_data)
		{
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$notice_data['user_id']);
			$order_info = array('mobile'=>'');//$GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$notice_data['order_id']);
			if($user_info['mobile']!=''||$order_info['mobile']!='')
			{
				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_PAYMENT'");
				$tmpl_content = $tmpl['content'];
				$notice_data['user_name'] = $user_info['user_name'];
				$notice_data['order_sn'] = $notice_data['notice_sn'];//$GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$notice_data['order_id']);
				$notice_data['pay_time_format'] = to_date($notice_data['pay_time']);
				$notice_data['money_format'] = format_price($notice_data['money']);
				$GLOBALS['tmpl']->assign("payment_notice",$notice_data);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				if($user_info['mobile']!='')
				{
					$msg_data['dest'] = $user_info['mobile'];
				}
				else
				{
					$msg_data['dest'] = $order_info['mobile'];
				}
				$msg_data['send_type'] = 0;
				$msg_data['content'] = addslashes($msg);;
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = $tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入

			}
		}
	}
}

//发邮件收款单
function send_payment_mail($notice_id)
{
	if(app_conf("MAIL_ON")==1&&app_conf("MAIL_SEND_PAYMENT")==1)
	{
		$notice_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$notice_id);
		if($notice_data)
		{
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$notice_data['user_id']);
			if($user_info['email']!='')
			{
				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_PAYMENT'");
				$tmpl_content = $tmpl['content'];
				$notice_data['user_name'] = $user_info['user_name'];
				$notice_data['order_sn'] = $notice_data['notice_sn'];//$GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$notice_data['order_id']);
				$notice_data['pay_time_format'] = to_date($notice_data['pay_time']);
				$notice_data['money_format'] = format_price($notice_data['money']);
				$GLOBALS['tmpl']->assign("payment_notice",$notice_data);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				$msg_data['dest'] = $user_info['email'];
				$msg_data['send_type'] = 1;
				$msg_data['title'] = $GLOBALS['lang']['PAYMENT_NOTICE'];
				$msg_data['content'] = addslashes($msg);;
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = $tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
			}
		}
	}
}



//发邮件发货单
function send_delivery_mail($notice_sn,$deal_names = '',$order_id)
{
	if(app_conf("MAIL_ON")==1&&app_conf("MAIL_SEND_DELIVERY")==1)
	{
		$notice_data = $GLOBALS['db']->getRow("select dn.* from ".DB_PREFIX."delivery_notice as dn left join ".DB_PREFIX."deal_order_item as doi on dn.order_item_id = doi.id where dn.notice_sn = '".$notice_sn."' and doi.order_id = ".$order_id);
		if($notice_data)
		{
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$notice_data['user_id']);
			if($user_info['email']!='')
			{
				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_DELIVERY'");
				$tmpl_content = $tmpl['content'];
				$notice_data['user_name'] = $user_info['user_name'];
				$notice_data['order_sn'] = $GLOBALS['db']->getOne("select do.order_sn from ".DB_PREFIX."deal_order_item as doi left join ".DB_PREFIX."deal_order as do on doi.order_id = do.id where doi.id = ".$notice_data['order_item_id']);
				$notice_data['delivery_time_format'] = to_date($notice_data['delivery_time']);
				$notice_data['deal_names'] = $deal_names;
				$GLOBALS['tmpl']->assign("delivery_notice",$notice_data);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				$msg_data['dest'] = $user_info['email'];
				$msg_data['send_type'] = 1;
				$msg_data['title'] = $GLOBALS['lang']['DELIVERY_NOTICE'];
				$msg_data['content'] = addslashes($msg);;
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = $tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
			}
		}
	}
}

//发短信发货单
function send_delivery_sms($notice_sn,$deal_names = '',$order_id)
{
	if(app_conf("SMS_ON")==1&&app_conf("SMS_SEND_DELIVERY")==1)
	{
		$notice_data = $GLOBALS['db']->getRow("select dn.* from ".DB_PREFIX."delivery_notice as dn left join ".DB_PREFIX."deal_order_item as doi on dn.order_item_id = doi.id where dn.notice_sn = '".$notice_sn."' and doi.order_id = ".$order_id);
		if($notice_data)
		{
			$order_info = array('mobile'=>'');//$GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$order_id);
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$notice_data['user_id']);
			if($user_info['mobile']!=''||$order_info['mobile']!='')
			{
				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_DELIVERY'");
				$tmpl_content = $tmpl['content'];
				$notice_data['user_name'] = $user_info['user_name'];
				$notice_data['order_sn'] = $GLOBALS['db']->getOne("select do.order_sn from ".DB_PREFIX."deal_order_item as doi left join ".DB_PREFIX."deal_order as do on doi.order_id = do.id where doi.id = ".$notice_data['order_item_id']);
				$notice_data['delivery_time_format'] = to_date($notice_data['delivery_time']);
				$notice_data['deal_names'] = $deal_names;
				$GLOBALS['tmpl']->assign("delivery_notice",$notice_data);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				if($user_info['mobile']!='')
				{
					$msg_data['dest'] = $user_info['mobile'];
					$msg_data['send_type'] = 0;
					$msg_data['content'] = addslashes($msg);;
					$msg_data['send_time'] = 0;
					$msg_data['is_send'] = 0;
					$msg_data['create_time'] = get_gmtime();
					$msg_data['user_id'] = $user_info['id'];
					$msg_data['is_html'] = $tmpl['is_html'];
					$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
				}

				if($order_info['mobile']!=''&&$order_info['mobile']!=$user_info['mobile'])
				{
					$msg_data['dest'] = $order_info['mobile'];
					$msg_data['send_type'] = 0;
					$msg_data['content'] = addslashes($msg);;
					$msg_data['send_time'] = 0;
					$msg_data['is_send'] = 0;
					$msg_data['create_time'] = get_gmtime();
					$msg_data['user_id'] = $user_info['id'];
					$msg_data['is_html'] = $tmpl['is_html'];
					$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
				}
			}
		}
	}
}


//发短信验证码
function send_verify_sms($mobile,$code,$user_info)
{
	if(app_conf("SMS_ON")==1)
	{

				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_VERIFY_CODE'");
				$tmpl_content = $tmpl['content'];
				$verify['mobile'] = $mobile;
				$verify['code'] = $code;
				$GLOBALS['tmpl']->assign("verify",$verify);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				$msg_data['dest'] = $mobile;
				$msg_data['send_type'] = 0;
				$msg_data['title'] = addslashes($msg);
				$msg_data['content'] = $msg_data['title'].'【'.app_conf("SHOP_TITLE").'】';
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = $tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
	}
}


//发邮件退订验证
function send_unsubscribe_mail($email)
{
	if(app_conf("MAIL_ON")==1)
	{
		if($email)
		{
			$GLOBALS['db']->query("update ".DB_PREFIX."mail_list set code = '".rand(1111,9999)."' where mail_address='".$email."' and code = ''");
			$email_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."mail_list where mail_address = '".$email."' and code <> ''");
			if($email_item)
			{
				$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_UNSUBSCRIBE'");
				$tmpl_content = $tmpl['content'];
				$mail = $email_item;
				$mail['url'] = get_domain().url("index","subscribe#dounsubscribe", array("code"=>base64_encode($mail['code']."|".$mail['mail_address'])));
				$GLOBALS['tmpl']->assign("mail",$mail);
				$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
				$msg_data['dest'] = $mail['mail_address'];
				$msg_data['send_type'] = 1;
				$msg_data['title'] = $GLOBALS['lang']['MAIL_UNSUBSCRIBE'];
				$msg_data['content'] = addslashes($msg);;
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = 0;
				$msg_data['is_html'] = $tmpl['is_html'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
			}
		}
	}
}

function get_deal_cate_name($cate_id)
{
	return $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_cate where id =".$cate_id);
}

function get_loan_type_name($type_id){
	return $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_loan_type where id =".$type_id);
}

function format_price($price)
{
	return app_conf("CURRENCY_UNIT")."".number_format($price,2);
}
function format_score($score)
{
	return intval($score)."".app_conf("SCORE_UNIT");
}

//utf8 字符串截取
function msubstr($str, $start=0, $length=15, $charset="utf-8", $suffix=true)
{
	if(function_exists("mb_substr"))
    {
        $slice =  mb_substr($str, $start, $length, $charset);
        if($suffix&$slice!=$str) return $slice."…";
    	return $slice;
    }
    elseif(function_exists('iconv_substr')) {
        return iconv_substr($str,$start,$length,$charset);
    }
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    if($suffix&&$slice!=$str) return $slice."…";
    return $slice;
}

 /**
  * PHP获取字符串中英文混合长度
  * @param $str string 字符串
  * @param $$charset string 编码
  * @return 返回长度，1中文=1位，2英文=1位
  */
  function strLength($str,$charset='utf-8'){
  	if($charset=='utf-8') $str = iconv('utf-8','gb2312',$str);
    $num = strlen($str);
    $cnNum = 0;
    for($i=0;$i<$num;$i++){
        if(ord(substr($str,$i+1,1))>127){
            $cnNum++;
            $i++;
        }
    }
    $enNum = $num-($cnNum*2);
    $number = ($enNum/2)+$cnNum;
    return ceil($number);
 }


//字符编码转换
if(!function_exists("iconv"))
{
	function iconv($in_charset,$out_charset,$str)
	{
		require 'libs/iconv.php';
		$chinese = new Chinese();
		return $chinese->Convert($in_charset,$out_charset,$str);
	}
}

//JSON兼容
if(!function_exists("json_encode"))
{
	function json_encode($data)
	{
		require_once 'libs/json.php';
		$JSON = new JSON();
		return $JSON->encode($data);
	}
}
if(!function_exists("json_decode"))
{
	function json_decode($data)
	{
		require_once 'libs/json.php';
		$JSON = new JSON();
		return $JSON->decode($data,1);
	}
}

//邮件格式验证的函数
function check_email($email)
{
	if(!preg_match("/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/",$email))
	{
		return false;
	}
	else
	return true;
}

//验证手机号码
function check_mobile($mobile)
{
	if(!empty($mobile) && !preg_match("/^\d{6,}$/",$mobile))
	{
		return false;
	}
	else
	return true;
}

//跳转
function app_redirect($url,$time=0,$msg='')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if(empty($msg))
        $msg    =   "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if(0===$time) {
        	if(substr($url,0,1)=="/")
        	{
        		header("Location:".get_domain().$url);
        	}
        	else
        	{
        		header("Location:".$url);
        	}

        }else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        return false;
    }else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if($time!=0)
            $str   .=   $msg;
        echo $str;
        return false;
    }
}



/**
 * 验证访问IP的有效性
 * @param ip地址 $ip_str
 * @param 访问页面 $module
 * @param 时间间隔 $time_span
 * @param 数据ID $id
 */
function check_ipop_limit($ip_str,$module,$time_span=0,$id=0)
{
		$op = es_session::get($module."_".$id."_ip");
    	if(empty($op))
    	{
    		$check['ip']	=	 get_client_ip();
    		$check['time']	=	get_gmtime();
    		es_session::set($module."_".$id."_ip",$check);
    		return true;  //不存在session时验证通过
    	}
    	else
    	{
    		$check['ip']	=	 get_client_ip();
    		$check['time']	=	get_gmtime();
    		$origin	=	es_session::get($module."_".$id."_ip");

    		if($check['ip']==$origin['ip'])
    		{
    			if($check['time'] - $origin['time'] < $time_span)
    			{
    				return false;
    			}
    			else
    			{
    				es_session::set($module."_".$id."_ip",$check);
    				return true;  //不存在session时验证通过
    			}
    		}
    		else
    		{
    			es_session::set($module."_".$id."_ip",$check);
    			return true;  //不存在session时验证通过
    		}
    	}
    }

//发放返利的函数
function pay_referrals($id)
{
	$referrals_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."referrals where id = ".$id);
	if($referrals_data)
	{
		$sql = "update ".DB_PREFIX."referrals set pay_time = ".get_gmtime()." where id = ".$id." and pay_time = 0 ";
		$GLOBALS['db']->query($sql);
		$rs = $GLOBALS['db']->affected_rows();
		if($rs)
		{
			//开始发放返利
			require_once APP_ROOT_PATH."system/libs/user.php";
			$order_sn = $GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$referrals_data['order_id']);
			$user_name = $GLOBALS['db']->getOne("select user_name from ".DB_PREFIX."user where id = ".$referrals_data['user_id']);
			$rel_user_name = $GLOBALS['db']->getOne("select user_name from ".DB_PREFIX."user where id = ".$referrals_data['rel_user_id']);
			$referral_amount = $referrals_data['money']>0?format_price($referrals_data['money']):format_score($referrals_data['score']);
			$msg = sprintf($GLOBALS['lang']['REFERRALS_LOG'],$order_sn,$rel_user_name,$referral_amount);
			// TODO finance 发放返利
			modify_account(array('money'=>$referrals_data['money'],'score'=>$referrals_data['score']),$referrals_data['user_id'],'发放返利',1,$msg);
			return true;
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}


function get_deal_mail_content($deal_rs)
{
	$tmpl_content = file_get_contents(APP_ROOT_PATH."app/Tpl/".app_conf("TEMPLATE")."/deal_mail.html");
	$GLOBALS['tmpl']->assign("APP_ROOT",APP_ROOT);

	if($deal_rs)
	{
		foreach($deal_rs as $k=>$deal)
		{
			$deal_city = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_city where id = ".$deal['city_id']);
			$deal['city_name'] = $deal_city['name'];

			$send_date = to_date(get_gmtime(),'Y年m月d日');
			$weekarray = array("日","一","二","三","四","五","六");
			$send_date .= " 星期".$weekarray[to_date(get_gmtime(),"w")];
			$deal['send_date'] = $send_date;


			$deal['url'] = url("tuan","deal",array("id"=>$deal['id'],"city"=>$deal_city['uname']));

			if($deal['origin_price']>0&&floatval($deal['discount'])==0) //手动折扣
			$deal['save_money'] = $deal['origin_price'] - $deal['current_price'];
			else
			$deal['save_money'] = $deal['origin_price']*((10-$deal['discount'])/10);

			if($deal['origin_price']>0&&floatval($deal['discount'])==0)
			$deal['discount'] = round(($deal['current_price']/$deal['origin_price'])*10,2);

			$deal['discount'] = round($deal['discount'],2);


			$supplier_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."supplier where id = ".$deal['supplier_id']);
			$supplier_address_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."supplier_location where supplier_id = ".$deal['supplier_id']." and is_main = 1");
			$deal['saler_name'] = $supplier_info['name'];
			$deal['saler_address'] = $supplier_address_info['address'];
			$deal['saler_tel'] = $supplier_address_info['tel'];

			if(app_conf("INVITE_REFERRALS_TYPE")==0)
			{
				$deal['referrals'] = format_price(app_conf("INVITE_REFERRALS"));
			}
			else
			{
				$deal['referrals'] = format_score(app_conf("INVITE_REFERRALS"));
			}


			$deal['referrals_url'] = url("tuan","referral",array("id"=>$deal['deal_id'],"city"=>$deal_city['uname']));
			$deal_rs[$k] = $deal;

		}
		$GLOBALS['tmpl']->assign("deal_rs",$deal_rs);
		$content = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);

		$tmpl_path = app_conf("TMPL_DOMAIN_ROOT")==''?get_domain().APP_ROOT."/app/Tpl/":app_conf("TMPL_DOMAIN_ROOT")."/";
		$content = str_replace("deal_mail/",$tmpl_path.app_conf("TEMPLATE")."/deal_mail/",$content);
		return $content;
	}
	else
	return '';
}

/**
 * $notice.site_name
 * $notice.deal_name
 * $notice.site_url
 * @param $deal_id
 */
function get_deal_sms_content($deal_id)
{
	$tmpl_content = $GLOBALS['db']->getOne("select content from ".DB_PREFIX."msg_template where name = 'TPL_DEAL_NOTICE_SMS'");
	$deal = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$deal_id);
	if($deal)
	{
		$notice['site_name'] = app_conf("SHOP_TITLE");
		$notice['deal_name'] = $deal['sub_name'];
		$notice['site_url'] = get_domain().APP_ROOT;
		$GLOBALS['tmpl']->assign("notice",$notice);
		$content = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
		return $content;
	}
	else
	return '';
}

/**
 * $bond.sn
 * $bond.password
 * $bond.name
 * $bond.user_name
 * $bond.begin_time_format
 * $bond.end_time_format
 * $bond.tel
 * $bond.address
 * $bond.route
 * $bond.open_time
 * @param $coupon_id
 * @param $location_id
 */


function gzip_out($content)
{
	header("Content-type: text/html; charset=utf-8");
    header("Cache-control: private");  //支持页面回跳
	$gzip = app_conf("GZIP_ON");
	if( intval($gzip)==1 )
	{
		if(!headers_sent()&&extension_loaded("zlib")&&preg_match("/gzip/i",$_SERVER["HTTP_ACCEPT_ENCODING"]))
		{
			$content = gzencode($content,9);
			header("Content-Encoding: gzip");
			header("Content-Length: ".strlen($content));
			echo $content;
		}
		else
		echo $content;
	}else{
		echo $content;
	}

}

function order_log($log_info,$order_id)
{
	$data['id'] = 0;
	$data['log_info'] = $log_info;
	$data['log_time'] = get_gmtime();
	$data['order_id'] = $order_id;
	$GLOBALS['db']->autoExecute(DB_PREFIX."deal_order_log", $data);
}


/**
	 * 保存图片
	 * @param array $upd_file  即上传的$_FILES数组
	 * @param array $key $_FILES 中的键名 为空则保存 $_FILES 中的所有图片
	 * @param string $dir 保存到的目录
	 * @param array $whs
	 	可生成多个缩略图
		数组 参数1 为宽度，
			 参数2为高度，
			 参数3为处理方式:0(缩放,默认)，1(剪裁)，
			 参数4为是否水印 默认为 0(不生成水印)
	 	array(
			'thumb1'=>array(300,300,0,0),
			'thumb2'=>array(100,100,0,0),
			'origin'=>array(0,0,0,0),  宽与高为0为直接上传
			...
		)，
	 * @param array $is_water 原图是否水印
	 * @return array
	 	array(
			'key'=>array(
				'name'=>图片名称，
				'url'=>原图web路径，
				'path'=>原图物理路径，
				有略图时
				'thumb'=>array(
					'thumb1'=>array('url'=>web路径,'path'=>物理路径),
					'thumb2'=>array('url'=>web路径,'path'=>物理路径),
					...
				)
			)
			....
		)
	 */
//$img = save_image_upload($_FILES,'avatar','temp',array('avatar'=>array(300,300,1,1)),1);
function save_image_upload($upd_file, $key='',$dir='temp', $whs=array(),$is_water=false,$need_return = false)
{
		require_once APP_ROOT_PATH."system/utils/es_imagecls.php";
		$image = new es_imagecls();
		$image->max_size = intval(app_conf("MAX_IMAGE_SIZE"));

		$list = array();

		if(empty($key))
		{
			foreach($upd_file as $fkey=>$file)
			{
				$list[$fkey] = false;
				$image->init($file,$dir);
				if($image->save())
				{
					$list[$fkey] = array();
					$list[$fkey]['url'] = $image->file['target'];
					$list[$fkey]['path'] = $image->file['local_target'];
					$list[$fkey]['name'] = $image->file['prefix'];
				}
				else
				{
					if($image->error_code==-105)
					{
						if($need_return)
						{
							return array('error'=>1,'message'=>'上传的图片太大');
						}
						else
						echo "上传的图片太大";
					}
					elseif($image->error_code==-104||$image->error_code==-103||$image->error_code==-102||$image->error_code==-101)
					{
						if($need_return)
						{
							return array('error'=>1,'message'=>'非法图像');
						}
						else
						echo "非法图像";
					}
					return;
				}
			}
		}
		else
		{
			$list[$key] = false;
			$image->init($upd_file[$key],$dir);
			if($image->save())
			{
				$list[$key] = array();
				$list[$key]['url'] = $image->file['target'];
				$list[$key]['path'] = $image->file['local_target'];
				$list[$key]['name'] = $image->file['prefix'];
			}
			else
				{
					if($image->error_code==-105)
					{
						if($need_return)
						{
							return array('error'=>1,'message'=>'上传的图片太大');
						}
						else
						echo "上传的图片太大";
					}
					elseif($image->error_code==-104||$image->error_code==-103||$image->error_code==-102||$image->error_code==-101)
					{
						if($need_return)
						{
							return array('error'=>1,'message'=>'非法图像');
						}
						else
						echo "非法图像";
					}
					return;
				}
		}

		$water_image = APP_ROOT_PATH.app_conf("WATER_MARK");
		$alpha = app_conf("WATER_ALPHA");
		$place = app_conf("WATER_POSITION");

		foreach($list as $lkey=>$item)
		{
				//循环生成规格图
				foreach($whs as $tkey=>$wh)
				{
					$list[$lkey]['thumb'][$tkey]['url'] = false;
					$list[$lkey]['thumb'][$tkey]['path'] = false;
					if($wh[0] > 0 || $wh[1] > 0)  //有宽高度
					{
						$thumb_type = isset($wh[2]) ? intval($wh[2]) : 0;  //剪裁还是缩放， 0缩放 1剪裁
						if($thumb = $image->thumb($item['path'],$wh[0],$wh[1],$thumb_type))
						{
							$list[$lkey]['thumb'][$tkey]['url'] = $thumb['url'];
							$list[$lkey]['thumb'][$tkey]['path'] = $thumb['path'];
							if(isset($wh[3]) && intval($wh[3]) > 0)//需要水印
							{
								$paths = pathinfo($list[$lkey]['thumb'][$tkey]['path']);
								$path = $paths['dirname'];
				        		$path = $path."/origin/";
				        		if (!is_dir($path)) {
						             @mkdir($path);
						             @chmod($path, 0777);
					   			}
				        		$filename = $paths['basename'];
								@file_put_contents($path.$filename,@file_get_contents($list[$lkey]['thumb'][$tkey]['path']));
								$image->water($list[$lkey]['thumb'][$tkey]['path'],$water_image,$alpha, $place);
							}
						}
					}
				}
			if($is_water)
			{
				$paths = pathinfo($item['path']);
				$path = $paths['dirname'];
        		$path = $path."/origin/";
        		if (!is_dir($path)) {
		             @mkdir($path);
		             @chmod($path, 0777);
	   			}
        		$filename = $paths['basename'];
				@file_put_contents($path.$filename,@file_get_contents($item['path']));
				$image->water($item['path'],$water_image,$alpha, $place);
			}
		}
		return $list;
}

function empty_tag($string)
{
	$string = preg_replace(array("/\[img\]\d+\[\/img\]/","/\[[^\]]+\]/"),array("",""),$string);
	if(trim($string)=='')
	return $GLOBALS['lang']['ONLY_IMG'];
	else
	return $string;
	//$string = str_replace(array("[img]","[/img]"),array("",""),$string);
}

//验证是否有非法字汇，未完成
function valid_str($string)
{
	$string = msubstr($string,0,5000);
	if(app_conf("FILTER_WORD")!='')
	$string = preg_replace("/".app_conf("FILTER_WORD")."/","*",$string);
	return $string;
}


/**
 * utf8字符转Unicode字符
 * @param string $char 要转换的单字符
 * @return void
 */
function utf8_to_unicode($char)
{
	switch(strlen($char))
	{
		case 1:
			return ord($char);
		case 2:
			$n = (ord($char[0]) & 0x3f) << 6;
			$n += ord($char[1]) & 0x3f;
			return $n;
		case 3:
			$n = (ord($char[0]) & 0x1f) << 12;
			$n += (ord($char[1]) & 0x3f) << 6;
			$n += ord($char[2]) & 0x3f;
			return $n;
		case 4:
			$n = (ord($char[0]) & 0x0f) << 18;
			$n += (ord($char[1]) & 0x3f) << 12;
			$n += (ord($char[2]) & 0x3f) << 6;
			$n += ord($char[3]) & 0x3f;
			return $n;
	}
}

/**
 * utf8字符串分隔为unicode字符串
 * @param string $str 要转换的字符串
 * @param string $depart 分隔,默认为空格为单字
 * @return string
 */
function str_to_unicode_word($str,$depart=' ')
{
	$arr = array();
	$str_len = mb_strlen($str,'utf-8');
	for($i = 0;$i < $str_len;$i++)
	{
		$s = mb_substr($str,$i,1,'utf-8');
		if($s != ' ' && $s != '　')
		{
			$arr[] = 'ux'.utf8_to_unicode($s);
		}
	}
	return implode($depart,$arr);
}


/**
 * utf8字符串分隔为unicode字符串
 * @param string $str 要转换的字符串
 * @return string
 */
function str_to_unicode_string($str)
{
	$string = str_to_unicode_word($str,'');
	return $string;
}

//分词
function div_str($str)
{
	require_once APP_ROOT_PATH."system/libs/words.php";
	$words = words::segment($str);
	$words[] = $str;
	return $words;
}


/**
 *
 * @param $tag  //要插入的关键词
 * @param $table  //表名
 * @param $id  //数据ID
 * @param $field		// tag_match/name_match/cate_match/locate_match
 */
function insert_match_item($tag,$table,$id,$field)
{
	if ($tag=='') {
        return ;
    }

	$unicode_tag = str_to_unicode_string($tag);
    $sql = "SELECT `{$field}` FROM " . DB_PREFIX . "{$table} WHERE `id` = '{$id}'";
    $rs = $GLOBALS['db']->getOne($sql);
    if (strpos(strval($rs), $unicode_tag) !== false) {
        return ;
    } else {
		$match_row = $GLOBALS['db']->getRow("select * from ".DB_PREFIX.$table." where id = ".$id);
		if($match_row[$field]=="")
		{
				$match_row[$field] = $unicode_tag;
				$match_row[$field."_row"] = $tag;
		}
		else
		{
				$match_row[$field] = $match_row[$field].",".$unicode_tag;
				$match_row[$field."_row"] = $match_row[$field."_row"].",".$tag;
		}
		$GLOBALS['db']->autoExecute(DB_PREFIX.$table, $match_row, $mode = 'UPDATE', "id=".$id, $querymode = 'SILENT');

	}
}

function get_all_parent_id($id,$table,&$arr = array())
{
	if(intval($id)>0)
	{
		$arr[] = $id;
		$pid = $GLOBALS['db']->getOne("select pid from ".$table." where id = ".$id);
		if($pid>0)
		{
			get_all_parent_id($pid,$table,$arr);
		}
	}
}

function syn_deal_match($deal_id)
{
	$deal = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$deal_id);
	if($deal)
	{
		$deal['name_match'] = "";
		$deal['name_match_row'] = "";
		$deal['deal_cate_match'] = "";
		$deal['deal_cate_match_row'] = "";
		$deal['type_match'] = "";
		$deal['type_match_row'] = "";
		$deal['tag_match'] = "";
		$deal['tag_match_row'] = "";
		$GLOBALS['db']->autoExecute(DB_PREFIX."deal", $deal, $mode = 'UPDATE', "id=".$deal_id, $querymode = 'SILENT');

		//同步名称
		$name_arr = div_str(trim($deal['name']));
		foreach($name_arr as $name_item)
		{
			insert_match_item($name_item,"deal",$deal_id,"name_match");
		}

		//分类类别
		$deal_cate =array();
		get_all_parent_id(intval($deal['cate_id']),DB_PREFIX."deal_cate",$deal_cate);
		if(count($deal_cate)>0)
		{
			$deal_cates = $GLOBALS['db']->getAll("select name from ".DB_PREFIX."deal_cate where id in (".implode(",",$deal_cate).")");
			foreach ($deal_cates as $row)
			{
				insert_match_item(trim($row['name']),"deal",$deal_id,"deal_cate_match");
			}
		}
		$goods_cate =array();
		get_all_parent_id(intval($deal['type_id']),DB_PREFIX."deal_loan_type",$goods_cate);
		if(count($goods_cate)>0)
		{
			$goods_cates = $GLOBALS['db']->getAll("select name from ".DB_PREFIX."deal_loan_type where id in (".implode(",",$goods_cate).")");
			foreach ($goods_cates as $row)
			{
				insert_match_item(trim($row['name']),"deal",$deal_id,"type_match");
			}
		}


	}
}


//封装url

function url($app_index,$route="index",$param=array())
{
	$key = md5("URL_KEY_".$app_index.$route.serialize($param));
	if(isset($GLOBALS[$key]))
	{
		$url = $GLOBALS[$key];
		return $url;
	}

	$url = load_dynamic_cache($key);
	if($url!==false)
	{
		$GLOBALS[$key] = $url;
		return $url;
	}

	$show_city = intval($GLOBALS['city_count'])>1?true:false;  //有多个城市时显示城市名称到url
	$route_array = explode("#",$route);

	if(isset($param)&&$param!=''&&!is_array($param))
	{
		$param['id'] = $param;
	}

	$module = strtolower(trim($route_array[0]));
	$action = strtolower(trim($route_array[1]));

	if(!$module||$module=='index')$module="";
	if(!$action||$action=='index')$action="";

	if(app_conf("URL_MODEL")==0)
	{
		//过滤主要的应用url
		if($app_index==app_conf("MAIN_APP"))
		$app_index = "index";

		//原始模式
		$url = APP_ROOT."/".$app_index.".php";
		if($module!=''||$action!=''||count($param)>0||$show_city) //有后缀参数
		{
			$url.="?";
		}

		if(isset($param['city']))
		{
			$url .= "city=".$param['city']."&";
			unset($param['city']);
		}
		if($module&&$module!='')
		$url .= "ctl=".$module."&";
		if($action&&$action!='')
		$url .= "act=".$action."&";
		if(count($param)>0)
		{
			foreach($param as $k=>$v)
			{
				if($k&&$v)
				$url =$url.$k."=".urlencode($v)."&";
			}
		}
		if(substr($url,-1,1)=='&'||substr($url,-1,1)=='?') $url = substr($url,0,-1);
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}
	else
	{
		//重写的默认
		$url = APP_ROOT;

		if($app_index!='index')
		$url .= "/".$app_index;

		if($module&&$module!='')
		$url .= "/".$module;
		if($action&&$action!='')
		$url .= "-".$action;

		if(count($param)>0)
		{
			$url.="/";
			foreach($param as $k=>$v)
			{
				if($k!='city')
				$url =$url.$k."-".urlencode($v)."-";
			}
		}

		//过滤主要的应用url
		if($app_index==app_conf("MAIN_APP"))
		$url = str_replace("/".app_conf("MAIN_APP"),"",$url);

		$route = $module."#".$action;
		switch ($route)
		{
				case "xxx":
					break;
				default:
					break;
		}

		if(substr($url,-1,1)=='/'||substr($url,-1,1)=='-') $url = substr($url,0,-1);



		if(isset($param['city']))
		{
			$city_uname = $param['city'];
			if($city_uname=="all")
			{
				return "http://www.".app_conf("DOMAIN_ROOT").$url."/city-all";
			}
			else
				{
				$domain = "http://".$city_uname.".".app_conf("DOMAIN_ROOT");
				return $domain.$url;
			}
		}
		if($url=='')$url="/";
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}


}


function unicode_encode($name) {//to Unicode
    $name = iconv('UTF-8', 'UCS-2', $name);
    $len = strlen($name);
    $str = '';
    for($i = 0; $i < $len - 1; $i = $i + 2) {
        $c = $name[$i];
        $c2 = $name[$i + 1];
        if (ord($c) > 0) {// 两个字节的字
            $cn_word = '\\'.base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16);
            $str .= strtoupper($cn_word);
        } else {
            $str .= $c2;
        }
    }
    return $str;
}

function unicode_decode($name) {//Unicode to
    $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
    preg_match_all($pattern, $name, $matches);
    if (!empty($matches)) {
        $name = '';
        for ($j = 0; $j < count($matches[0]); $j++) {
            $str = $matches[0][$j];
            if (strpos($str, '\\u') === 0) {
                $code = base_convert(substr($str, 2, 2), 16, 10);
                $code2 = base_convert(substr($str, 4), 16, 10);
                $c = chr($code).chr($code2);
                $c = iconv('UCS-2', 'UTF-8', $c);
                $name .= $c;
            } else {
                $name .= $str;
            }
        }
    }
    return $name;
}

//生成短信发送的优惠券
/**
 *
 * @param $youhui_id 优惠券ID
 * @param $mobile 手机号
 * @param $user_id 会员ID
 * 以下参数仅供 send_type = 2 预订验证券使用
 * @param $order_count 预订的人数
 * @param $is_private_room  预订是否包间
 * @param $date_time  预订时间
 */
function gen_verify_youhui($youhui_id,$mobile,$user_id,$order_count=0,$is_private_room=0,$date_time=0)
{

	$data = array();
	$data['youhui_id'] = intval($youhui_id);
	$data['user_id'] = intval($user_id);
	$data['user_id'] = intval($user_id);
	$data['mobile'] = $mobile;
	$data['order_count'] = intval($order_count);
	$data['order_count'] = intval($order_count);
	$data['is_private_room'] = intval($is_private_room);
	$data['date_time'] = intval($date_time);
	$data['create_time'] = get_gmtime();
	$data['youhui_sn'] = rand(10000000,99999999);
	do{
		$GLOBALS['db']->autoExecute(DB_PREFIX."youhui_log", $data, $mode = 'INSERT', "", $querymode = 'SILENT');
		$rs = $GLOBALS['db']->insert_id();
	}while(intval($rs)==0);
	return $rs;
}


//发送优惠券短信(直接下载无验证类型), 函数不验证发送次数是否超限，前台发送时验证
function send_youhui_sms($youhui_id,$user_id,$mobile)
{
	if(app_conf("SMS_ON")==1&&$mobile!='')
	{

		$youhui_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."youhui where id = ".$youhui_id);
		if($youhui_data)
		{
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
			if($user_info)
			{
				$msg_data['dest'] = $mobile;
				$msg_data['send_type'] = 0;
				$msg_data['content'] = $youhui_data['sms_content'];
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = 0;
				$msg_data['is_youhui'] = 1;
				$msg_data['youhui_id'] = $youhui_id;
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
				$id = $GLOBALS['db']->insert_id();
				if($id)
				{
					$GLOBALS['db']->query("update ".DB_PREFIX."youhui set sms_count = sms_count +1,view_count = view_count +1 where id = ".$youhui_id);
					return $id;
				}
				else
				return false;

			}
			else
			return false;
		}
		else
		return false;
	}
	else
	{
		return false;
	}
}
//发送优惠券短信(验证类型), 函数不验证发送次数是否超限，前台发送时验证
function send_youhui_log_sms($log_id)
{
	if(app_conf("SMS_ON")==1)
	{
		$log_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."youhui_log where id = ".$log_id);
		$youhui_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."youhui where id = ".$log_data['youhui_id']);
		if($youhui_data)
		{
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$log_data['user_id']);
			if($user_info)
			{
				$msg_data['dest'] = $log_data['mobile'];
				$msg_data['send_type'] = 0;
				$msg_data['content'] = $youhui_data['sms_content']." - 验证码:".$log_data['youhui_sn'];
				$msg_data['send_time'] = 0;
				$msg_data['is_send'] = 0;
				$msg_data['create_time'] = get_gmtime();
				$msg_data['user_id'] = $user_info['id'];
				$msg_data['is_html'] = 0;
				$msg_data['is_youhui'] = 1;
				$msg_data['youhui_id'] = $youhui_data['id'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
				$id = $GLOBALS['db']->insert_id();
				if($id)
				{
					$GLOBALS['db']->query("update ".DB_PREFIX."youhui set sms_count = sms_count +1,view_count = view_count +1 where id = ".$youhui_data['id']);
					return $id;
				}
				else
				return false;

			}
			else
			return false;
		}
		else
		return false;
	}
	else
	{
		return false;
	}
}

//载入动态缓存数据
function load_dynamic_cache($name)
{
	if(isset($GLOBALS['dynamic_cache'][$name]))
	{
		return $GLOBALS['dynamic_cache'][$name];
	}
	else
	{
		return false;
	}
}

function set_dynamic_cache($name,$value)
{
	if(!isset($GLOBALS['dynamic_cache'][$name]))
	{
		if(count($GLOBALS['dynamic_cache'])>MAX_DYNAMIC_CACHE_SIZE)
		{
			array_shift($GLOBALS['dynamic_cache']);
		}
		$GLOBALS['dynamic_cache'][$name] = $value;
	}
}


function load_auto_cache($key,$param=array())
{
	require_once APP_ROOT_PATH."system/libs/auto_cache.php";
	$file =  APP_ROOT_PATH."system/auto_cache/".$key.".auto_cache.php";
	if(file_exists($file))
	{
		require_once $file;
		$class = $key."_auto_cache";
		$obj = new $class;
		$result = $obj->load($param);
	}
	else
	$result = false;
	return $result;
}

function rm_auto_cache($key,$param=array())
{
	require_once APP_ROOT_PATH."system/libs/auto_cache.php";
	$file =  APP_ROOT_PATH."system/auto_cache/".$key.".auto_cache.php";
	if(file_exists($file))
	{
		require_once $file;
		$class = $key."_auto_cache";
		$obj = new $class;
		$obj->rm($param);
	}
}


function clear_auto_cache($key)
{
	require_once APP_ROOT_PATH."system/libs/auto_cache.php";
	$file =  APP_ROOT_PATH."system/auto_cache/".$key.".auto_cache.php";
	if(file_exists($file))
	{
		require_once $file;
		$class = $key."_auto_cache";
		$obj = new $class;
		$obj->clear_all();
	}
}

//获取随机会员提供关注
function get_rand_user($count,$is_daren=0,$uid=0)
{
	/*//第0阶梯达人，10个会员
	$danren_result_0 = $GLOBALS['cache']->get("RAND_USER_CACHE_DAREN_0");
	if($danren_result_0===false)
	{
		$sql = "select id,user_name,province_id,city_id from ".DB_PREFIX."user where is_daren = 1 order by is_merchant desc,is_daren desc,topic_count desc limit 10";
		$danren_result_0 = $GLOBALS['db']->getAll($sql);
		if($danren_result_0)
		$GLOBALS['cache']->set("RAND_USER_CACHE_DAREN_0",$danren_result_0,3600);
		else
		$GLOBALS['cache']->set("RAND_USER_CACHE_DAREN_0",array(),3600);
	}

	//第1阶梯达人，50个会员
	$danren_result_1 = $GLOBALS['cache']->get("RAND_USER_CACHE_DAREN_1");
	if($danren_result_1===false)
	{
		$sql = "select id,user_name,province_id,city_id from ".DB_PREFIX."user where is_daren = 1 order by is_merchant desc,is_daren desc,topic_count desc limit 10,50";
		$danren_result_1 = $GLOBALS['db']->getAll($sql);
		if($danren_result_1)
		$GLOBALS['cache']->set("RAND_USER_CACHE_DAREN_1",$danren_result_1,3600);
		else
		$GLOBALS['cache']->set("RAND_USER_CACHE_DAREN_1",array(),3600);
	}

	//第2阶梯达人，2000个会员
	$danren_result_2 = $GLOBALS['cache']->get("RAND_USER_CACHE_DAREN_2");
	if($danren_result_2===false)
	{
		$sql = "select id,user_name,province_id,city_id from ".DB_PREFIX."user where is_daren = 1 order by is_merchant desc,is_daren desc,topic_count desc limit 50,2000";
		$danren_result_2 = $GLOBALS['db']->getAll($sql);
		if($danren_result_2)
		$GLOBALS['cache']->set("RAND_USER_CACHE_DAREN_2",$danren_result_2,3600);
		else
		$GLOBALS['cache']->set("RAND_USER_CACHE_DAREN_2",array(),3600);
	}

	$danren_list[] = $danren_result_0;
	$danren_list[] = $danren_result_1;
	$danren_list[] = $danren_result_2;

	//非达人 , 2000个活跃会员
	$nodanren_result = $GLOBALS['cache']->get("RAND_USER_CACHE_NODAREN");
	if($nodanren_result===false)
	{
		$sql = "select id,user_name,province_id,city_id from ".DB_PREFIX."user where is_daren = 0 order by is_merchant desc,is_daren desc,topic_count desc limit 2000";
		$nodanren_result = $GLOBALS['db']->getAll($sql);
		if($nodanren_result)
		$GLOBALS['cache']->set("RAND_USER_CACHE_NODAREN",$nodanren_result,3600);
		else
		$GLOBALS['cache']->set("RAND_USER_CACHE_NODAREN",array(),3600);
	}

	$user_list = array();
	if($uid==0)
	{
		$user_group = 0; //阶梯数
		while(count($user_list)<$count&&$user_group<3)
		{
			$current_count = count($user_list);
			for($loop=0;$loop<$count-$current_count;$loop++)
			{
				$i = rand(0,count($danren_list[$user_group])-1);
				$user_item = $danren_list[$user_group][$i];
				unset($danren_list[$user_group][$i]);
				sort($danren_list[$user_group]);
				if($user_item)
				$user_list[] = $user_item;
			}
			$user_group++;
		}

		if(count($user_list)<$count&&$is_daren==0)
		{
			//人数还不足，并允许非达人
			$current_count = count($user_list);
			for($loop=0;$loop<$count-$current_count;$loop++)
			{
				$i = rand(0,count($nodanren_result)-1);
				$user_item = $nodanren_result[$i];
				unset($nodanren_result[$i]);
				sort($nodanren_result);
				if($user_item)
				$user_list[] = $user_item;
			}
		}

	}
	else
	{


		$user_group = 0; //阶梯数
		while(count($user_list)<$count&&$user_group<3)
		{
			$current_count = count($user_list);
			//$loop_count 用于限制循环上限, $c用于计算个数, $i标识当前位置
			for($loop_count=0,$c=0;$c<$count-$current_count&&$loop_count<100;$loop_count++,$c++)
			{
				$i = rand(0,count($danren_list[$user_group])-1);
				$user_item = $danren_list[$user_group][$i];
				unset($danren_list[$user_group][$i]);
				sort($danren_list[$user_group]);
				if($user_item)
				{
					if($user_item['id']!=$uid&&$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user_focus where focus_user_id=".$uid." and focused_user_id = ".intval($user_item['id']))==0)
					$user_list[] = $user_item;
					else
					$c--;
				}

			}
			$user_group++;
		}

		if(count($user_list)<$count&&$is_daren==0)
		{
			//人数还不足，并允许非达人

			$current_count = count($user_list);
			for($loop_count=0,$c=0;$c<$count-$current_count&&$loop_count<100;$loop_count++,$c++)
			{
				$i = rand(0,count($nodanren_result)-1);
				$user_item = $nodanren_result[$i];
				unset($nodanren_result[$i]);
				sort($nodanren_result);
				if($user_item)
				{
					if($user_item['id']!=$uid&&$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user_focus where focus_user_id=".$uid." and focused_user_id = ".intval($user_item['id']))==0)
					$user_list[] = $user_item;
					else
					$c--;
				}
			}
		}

	}
	return $user_list;
	*/
}

/*ajax返回*/
function ajax_return($data)
{
		header("Content-Type:text/html; charset=utf-8");
        echo(json_encode($data));
        return;
}


//增加会员活跃度
function increase_user_active($user_id,$log)
{
	$t_begin_time = to_timespan(to_date(get_gmtime(),"Y-m-d"));  //今天开始
	$t_end_time = to_timespan(to_date(get_gmtime(),"Y-m-d"))+ (24*3600 - 1);  //今天结束
	$y_begin_time = $t_begin_time - (24*3600); //昨天开始
	$y_end_time = $t_end_time - (24*3600);  //昨天结束

	$point = intval(app_conf("USER_ACTIVE_POINT"));
	$score = intval(app_conf("USER_ACTIVE_SCORE"));
	$money = doubleval(app_conf("USER_ACTIVE_MONEY"));
	$point_max = intval(app_conf("USER_ACTIVE_POINT_MAX"));
	$score_max = intval(app_conf("USER_ACTIVE_SCORE_MAX"));
	$money_max = doubleval(app_conf("USER_ACTIVE_MONEY_MAX"));

	$sum_money = doubleval($GLOBALS['db']->getOne("select sum(money) from ".DB_PREFIX."user_active_log where user_id = ".$user_id." and create_time between ".$t_begin_time." and ".$t_end_time));
	$sum_score = intval($GLOBALS['db']->getOne("select sum(score) from ".DB_PREFIX."user_active_log where user_id = ".$user_id." and create_time between ".$t_begin_time." and ".$t_end_time));
	$sum_point = intval($GLOBALS['db']->getOne("select sum(point) from ".DB_PREFIX."user_active_log where user_id = ".$user_id." and create_time between ".$t_begin_time." and ".$t_end_time));

	if($sum_money>=$money_max)$money = 0;
	if($sum_score>=$score_max)$score = 0;
	if($sum_point>=$point_max)$point = 0;

	if($money>0||$score>0||$point>0)
	{
		require_once  APP_ROOT_PATH."system/libs/user.php";
		// TODO finance 会员活跃度
		modify_account(array("money"=>$money,"score"=>$score,"point"=>$point),$user_id,'会员活跃度',1,$log);
		$data['user_id'] = $user_id;
		$data['create_time'] = get_gmtime();
		$data['money'] = $money;
		$data['score'] = $score;
		$data['point'] = $point;
		$GLOBALS['db']->autoExecute(DB_PREFIX."user_active_log",$data);
	}
}

/**
 *
 * @param $location_id 店铺ID
 * @param $data_type  tuan/event/youhui/daijin
 */
function recount_supplier_data_count($location_id,$data_type)
{
	switch ($data_type)
	{
		case "tuan":
			$sql = " select count(*) from ".DB_PREFIX."deal_location_link as l left join ".DB_PREFIX."deal as d on d.id = l.deal_id where d.is_effect = 1 and d.is_delete = 0 and d.is_shop = 0 and d.time_status <> 2 and l.location_id = ".$location_id;
			$count = intval($GLOBALS['db']->getOne($sql));
			$GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set tuan_count = ".$count." where id = ".$location_id);
			break;

		case "daijin":
			$sql = " select count(*) from ".DB_PREFIX."deal_location_link as l left join ".DB_PREFIX."deal as d on d.id = l.deal_id where d.is_effect = 1 and d.is_delete = 0 and d.is_shop = 2 and d.time_status <> 2 and l.location_id = ".$location_id;
			$count = intval($GLOBALS['db']->getOne($sql));
			$GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set daijin_count = ".$count." where id = ".$location_id);
			break;

		case "shop":
			$sql = " select count(*) from ".DB_PREFIX."deal_location_link as l left join ".DB_PREFIX."deal as d on d.id = l.deal_id where d.is_effect = 1 and d.is_delete = 0 and d.is_shop = 1 and d.time_status <> 2 and l.location_id = ".$location_id;
			$count = intval($GLOBALS['db']->getOne($sql));
			$GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set shop_count = ".$count." where id = ".$location_id);
			break;

		case "event":
			$time = get_gmtime();
			$time_condition = '  and (e.event_end_time = 0 or e.event_end_time > '.$time.' ) ';
			$sql = " select count(*) from ".DB_PREFIX."event_location_link as l left join ".DB_PREFIX."event as e on e.id = l.event_id where e.is_effect = 1  $time_condition and l.location_id = ".$location_id;
			$count = intval($GLOBALS['db']->getOne($sql));
			$GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set event_count = ".$count." where id = ".$location_id);
			break;

		case "youhui":
			$time = get_gmtime();
			$time_condition = '  and (y.end_time = 0 or y.end_time > '.$time.' ) ';
			$sql = " select count(*) from ".DB_PREFIX."youhui_location_link as l left join ".DB_PREFIX."youhui as y on y.id = l.youhui_id where y.is_effect = 1  $time_condition and l.location_id = ".$location_id;
			$count = intval($GLOBALS['db']->getOne($sql));
			$GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set youhui_count = ".$count." where id = ".$location_id);
			break;

	}

}

function build_deal_filter_condition($param,$is_store=false)
{
	$area_id = intval($param['aid']);
	$quan_id = intval($param['qid']);
	$cate_id = intval($param['cid']);
	$deal_type_id = intval($param['tid']);
	$purpose_id = intval($param['pid']);
	$purpose_type_id = intval($param['sid']);
	$avg_price = intval($param['a']);
	$city_id = intval($GLOBALS['deal_city']['id']);
	if($is_store){
		$deal_type = intval($param['deal_type']);
		$condition = " and deal_type = $deal_type ";
	}
	else{
		$condition="";
	}
	if($city_id>0)
	{
		$ids = load_auto_cache("deal_city_belone_ids",array("city_id"=>$city_id));
		if($ids)
		$condition .= " and city_id in (".implode(",",$ids).")";
	}
	if($area_id>0)
	{
			if($quan_id>0)
			{

					$area_name = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."area where id = ".$quan_id);
					$kw_unicodes[] = str_to_unicode_string($area_name);

					$kw_unicode = implode(" ",$kw_unicodes);
					//有筛选
					$condition .=" and (match(locate_match) against('".$kw_unicode."' IN BOOLEAN MODE)) ";
			}
			else
			{
				$ids = load_auto_cache("deal_quan_ids",array("quan_id"=>$area_id));
				$quan_list = $GLOBALS['db']->getAll("select `name` from ".DB_PREFIX."area where id in (".implode(",",$ids).")");
				$unicode_quans = array();
				foreach($quan_list as $k=>$v){
					$unicode_quans[] = str_to_unicode_string($v['name']);
				}
				$kw_unicode = implode(" ", $unicode_quans);
				$condition .= " and (match(locate_match) against('".$kw_unicode."' IN BOOLEAN MODE))";
			}
	}

	if($cate_id>0)
	{
			$cate_name = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_cate where id = ".$cate_id);
			$cate_name_unicode = str_to_unicode_string($cate_name);

			if($deal_type_id>0)
			{
				$deal_type_name = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_cate_type where id = ".$deal_type_id);
				$deal_type_name_unicode = str_to_unicode_string($deal_type_name);
				$condition .= " and (match(deal_cate_match) against('+".$cate_name_unicode." +".$deal_type_name_unicode."' IN BOOLEAN MODE)) ";
			}
			else
			{
				$condition .= " and (match(deal_cate_match) against('".$cate_name_unicode."' IN BOOLEAN MODE)) ";
			}
	}

	if($purpose_id>0)
	{
		$unicode_purpose = array();
		if($purpose_type_id > 0){
			$purpose_type_name = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."purpose_cate_type where id = ".$purpose_type_id);
			$unicode_purpose[] = str_to_unicode_string(str_replace("，","",$purpose_type_name));
		}
		else{
			$purpose_name= $GLOBALS['db']->getOne("select name from ".DB_PREFIX."purpose_cate where id = ".$purpose_id);
			$unicode_purpose[] = str_to_unicode_string(str_replace("，","",$purpose_name));
		}
		$kw_unicode = implode(" ", $unicode_purpose);
		$condition .= " and (match(purpose_match) against('".$kw_unicode."' IN BOOLEAN MODE))";
	}

	if($avg_price > 0){
		$condition .= " and avg_price = $avg_price ";
	}

	return $condition;
}

function is_animated_gif($filename){
 $fp=fopen($filename, 'rb');
 $filecontent=fread($fp, filesize($filename));
 fclose($fp);
 return strpos($filecontent,chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0')===FALSE?0:1;
}


function make_deal_cate_js()
{
	$js_file = APP_ROOT_PATH."public/runtime/app/deal_cate_conf.js";
	if(!file_exists($js_file))
	{
		$js_str = "var deal_cate_conf = [";
		$deal_cates = $GLOBALS['db']->getAll("select id,name from ".DB_PREFIX."deal_cate where is_delete = 0 and is_effect = 1 order by sort desc");
		foreach($deal_cates as $k=>$v)
		{
			$js_str.='{"n":"'.$v['name'].'","i":"'.$v['id'].'","s":[';
			$js_str .= ']},';
		}
		if($deal_cates)
		$js_str = substr($js_str,0,-1);
		$js_str.="];";
		@file_put_contents($js_file,$js_str);
	}
}

function make_deal_region_js()
{
	$dir = APP_ROOT_PATH."public/runtime/app/deal_region_conf/";
	if (!is_dir($dir))
    {
             @mkdir($dir);
             @chmod($dir, 0777);
    }
	$js_file = $dir.intval($GLOBALS['deal_city']['id']).".js";
	if(!file_exists($js_file))
	{
		$js_str = "var deal_region_conf = [";
		$js_str.="];";
		@file_put_contents($js_file,$js_str);
	}
}


function make_delivery_region_js()
{
	$path = APP_ROOT_PATH."public/runtime/app/region.js";
	if(!file_exists($path))
	{
		$jsStr = "var regionConf = ".get_delivery_region_js();
		@file_put_contents($path,$jsStr);
	}
}
function get_delivery_region_js($pid = 0)
{

		$jsStr = "";
		$childRegionList = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."delivery_region where pid = ".$pid." order by id asc");
		foreach($childRegionList as $childRegion)
		{
			if(empty($jsStr))
				$jsStr .= "{";
			else
				$jsStr .= ",";

			$childStr = get_delivery_region_js($childRegion['id']);
			$jsStr .= "\"r$childRegion[id]\":{\"i\":$childRegion[id],\"n\":\"$childRegion[name]\",\"c\":$childStr}";
		}

		if(!empty($jsStr))
			$jsStr .= "}";
		else
			$jsStr .= "\"\"";

		return $jsStr;

}

function update_sys_config()
{
	$filename = APP_ROOT_PATH."public/sys_config.php";
	if(!file_exists($filename))
	{
		//定义DB
		require APP_ROOT_PATH.'system/db/db.php';
		$dbcfg = require APP_ROOT_PATH."public/db_config.php";
		define('DB_PREFIX', $dbcfg['DB_PREFIX']);
		if(!file_exists(APP_ROOT_PATH.'public/runtime/app/db_caches/'))
			mkdir(APP_ROOT_PATH.'public/runtime/app/db_caches/',0777);
		$pconnect = false;
		$db = new \libs\db\MysqlDb($dbcfg['DB_HOST'].":".$dbcfg['DB_PORT'], $dbcfg['DB_USER'],$dbcfg['DB_PWD'],$dbcfg['DB_NAME'],'utf8',$pconnect);
		//end 定义DB

		$sys_configs = $db->getAll("select * from ".DB_PREFIX."conf");
		$config_str = "<?php\n";
		$config_str .= "return array(\n";
		foreach($sys_configs as $k=>$v)
		{
			$config_str.="'".$v['name']."'=>'".addslashes($v['value'])."',\n";
		}
		$config_str.=");\n ?>";
		file_put_contents($filename,$config_str);
		$url = APP_ROOT."/";
		return app_redirect($url);
	}
}

/**
 * 等额本息还款计算方式
 * $money 贷款金额
 * $rate 月利率
 * $remoth 还几个月
 * 返回  每月还款额
 */
function pl_it_formula($money,$rate,$remoth){
	return $money * ($rate*pow(1+$rate,$remoth)/(pow(1+$rate,$remoth)-1));
}

/**
 * 按月还款计算方式
 * $total_money 贷款金额
 * $rate 年利率
 * 返回月应该还多少利息
 */
function av_it_formula($total_money,$rate){
	return $total_money * $rate;
}


function is_has_empty_strings($param_array) {
	foreach($param_array as $param) {
		if (empty($param)) {
			return true;
		}
	}
	return false;
}





/**
 * 验证输入的邮件地址是否合法
 *
 * @access  public
 * @param   string      $email      需要验证的邮件地址
 *
 * @return bool
 */
function is_email($user_email)
{
	$chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
	if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false)
	{
		if (preg_match($chars, $user_email))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}

/**
 * 判断是否为手机号
 *
 * @Title: is_mobile
 * @Description: 判断是否为手机号
 * @param @param unknown_type $mobile
 * @return return_type
 * @author Liwei
 * @throws
 *
 */
function is_mobile($mobile){
	$chars = '/^1[3456789]\d{9}$/';
	return preg_match($chars, $mobile) ? true : false;
}

function get_wordnum($str = ''){
	return mb_strlen($str,'UTF-8');
}

/**
* 字符串截取(两个英文或者数字当成一个中文处理)，保证截取的整齐
* @author wenyanlei  2013-8-20
* @param $string string 要截取的字符串
* @param $length int 截取的长度
* @param $encoding string 字符编码
* @return string
*/
function cutstr($string, $length = 10, $end = '...', $encoding = 'utf-8') {
	$string = trim ( $string );

	if ($length && strlen ( $string ) <= $length) return $string;

	// 截断字符
	$wordscut = '';
	if (strtolower ( $encoding ) == 'utf-8') {
		// utf8编码
		$n = $tn = $noc = 0;
		while ( $n < strlen ( $string ) ) {
			$t = ord ( $string [$n] );
			if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1;
				$n ++;
				$noc ++;
			} elseif (194 <= $t && $t <= 223) {
				$tn = 2;
				$n += 2;
				$noc += 2;
			} elseif (224 <= $t && $t < 239) {
				$tn = 3;
				$n += 3;
				$noc += 2;
			} elseif (240 <= $t && $t <= 247) {
				$tn = 4;
				$n += 4;
				$noc += 2;
			} elseif (248 <= $t && $t <= 251) {
				$tn = 5;
				$n += 5;
				$noc += 2;
			} elseif ($t == 252 || $t == 253) {
				$tn = 6;
				$n += 6;
				$noc += 2;
			} else {
				$n ++;
			}
			if ($noc >= $length) break;
		}
		if ($noc > $length) $n -= $tn;
		$wordscut = substr ( $string, 0, $n );
	} else {
		for($i = 0; $i < $length - 1; $i ++) {
			if (ord ( $string [$i] ) > 127) {
				$wordscut .= $string [$i] . $string [$i + 1];
				$i ++;
			} else {
				$wordscut .= $string [$i];
			}
		}
	}

	if(strLength($string) > $length/2)	$wordscut .= $end;
	return trim($wordscut);
}

/**
 * 根据配置，获取借款标题
 * @author wenyanlei  2013-8-20
 * @param $title string 借款说明
 * @param $name string  借款用途的名称
 * @param $deal_id int 借款id
 * @return string
 */
function get_deal_title($title, $name = '', $deal_id = 0){
	$type = app_conf('DEAL_TITLE_TYPE');
	if($type == 1){
		return $title;
	}

	if($name != ''){
		return $name;
	}

	if($name == '' && $deal_id == 0){
		return $title;
	}

	if($deal_id > 0){
		return $GLOBALS['db']->getOne("SELECT b.name FROM ".DB_PREFIX."deal a left join ".DB_PREFIX."deal_loan_type b on a.type_id = b.id where a.id = ".$deal_id);
	}
}

/**
 * 生成合同编号
 */
function get_contract_number($deal, $user_id, $load_id, $type=NULL){
	$load_id = str_replace(",", "", $load_id);
	//判断子母单和普通单的情况
	if ($deal['parent_id'] == -1){
        return str_pad($deal['id'],6,"0",STR_PAD_LEFT).'01'.str_pad($type,2,"0",STR_PAD_LEFT).str_pad($user_id,8,"0",STR_PAD_LEFT).str_pad($load_id,10,"0",STR_PAD_LEFT);
	}elseif ($deal['parent_id'] > 0){
		return str_pad($deal['id'].'02'.$deal['parent_id'].$type.$user_id.$load_id,16,"0",STR_PAD_LEFT);
	}else {
		return str_pad($deal['id'].'03'.$type.$user_id.$load_id,16,"0",STR_PAD_LEFT);
	}
}

/**
 * 下载word文件
 * @author wenyanlei  2013-8-22
 * @param $msg string 文件内容
 * @param $filename string 文件名
 * @return file
 */
function export_word_doc($msg, $filename = ''){

	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$wordStr = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">';

	$wordStr .= $msg;

	$wordStr .= '</html>';

	if($filename == ''){
		$filename = format_date(time(), 'YmdHi');
	}
	$file = iconv("utf-8", "GBK", $filename);

	header("Content-Type: application/doc");
	header("Content-Disposition: attachment; filename=" . $file . ".doc");
	echo $wordStr;
}

function get_deal_status($deal_status)
{
    $status = array(
        0 => '等待确认',
        1 => '进行中',
        2 => '满标',
        3 => '流标',
        4 => '还款中',
        5 => '已还清',
    );
    $text = $status[$deal_status];
    return $text ? $text : '未知';
}
