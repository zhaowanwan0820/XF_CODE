<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
/**
 * 获取指定的投标
 */
FP::import("libs.libs.msgcenter");
FP::import("libs.utils.logger");
FP::import("libs.common.app");
use app\models\service\Finance;
use app\models\service\Earning;
function get_deal($id=0,$cate_id=0)
{
    $time = get_gmtime();

    if($id==0)  //有ID时不自动获取
    {
        return false;
    }
    else{
        $deal = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".intval($id)." and (is_effect = 1 or is_update = 1) and is_delete = 0 ");
    }
    if($deal)
    {
        if($deal['deal_status']!=3 && $deal['deal_status']!=5)
        {
            //syn_deal_status($deal['id']);
            $deal = $GLOBALS['db']->getRow("select *,(start_time + enddate*24*3600 - ".$time.") as remain_time from ".DB_PREFIX."deal where id = ".$deal['id']." and (is_effect = 1 or is_update = 1) and is_delete = 0");
        }

        if($deal['cate_id'] > 0){
            $deal['cate_info'] = $GLOBALS['db']->getRowCached("select name,brief,uname,icon from ".DB_PREFIX."deal_cate where id = ".$deal['cate_id']." and is_effect = 1 and is_delete = 0");
        }
        if($deal['type_id'] > 0){
            $deal['type_info'] = $GLOBALS['db']->getRowCached("select name,brief,uname,icon from ".DB_PREFIX."deal_loan_type where id = ".$deal['type_id']." and is_effect = 1 and is_delete = 0");
        }
        if($deal['agency_id'] > 0){
            $deal['agency_info'] = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."deal_agency where id = ".$deal['agency_id']." and is_effect = 1");
        }
        $deal['borrow_amount_format'] = format_price($deal['borrow_amount']);
        $deal['borrow_amount_format_detail'] = format_price($deal['borrow_amount'] / 10000,false);
		$deal['min_loan_money_format'] = format_price($deal['min_loan_money'] / 10000, false);
        $deal['rate_foramt'] = number_format($deal['rate'],2);
        //本息还款金额

        $month_interest = 0;
        $month_loan_amount = 0;

        if($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $deal['month_repay_money'] = get_deal_repay_money_month_interest($deal['loantype'], $deal['repay_time'], $deal['borrow_amount'], $deal['rate'], false, $month_interest, $month_loan_amount);
        } else if($deal['id'] > $GLOBALS['dict']['OLD_DEAL_ID']){
            $deal['month_repay_money'] = get_deal_repay_money_from_pmt($deal['id'], false, $month_interest, $month_loan_amount);
        } else {
            $deal['month_repay_money'] = get_deal_repay_money($deal['loantype'], $deal['repay_time'], $deal['borrow_amount'], $deal['rate'], false, $month_interest, $month_loan_amount);
        }
        $deal['month_interest']  = $month_interest;
        $deal['month_loan_amount']  = $month_loan_amount;

        $deal['month_repay_money_format'] = format_price($deal['month_repay_money']);

        $deal['month_manage_money'] = 0;
        $deal['month_manage_money_format'] = format_price($deal['month_manage_money']);
        $deal['all_manage_money'] = 0;
        $deal['true_month_repay_money'] = ceilfix($deal['month_repay_money']);

        //还需多少钱
        $deal['need_money_decimal'] = round($deal['borrow_amount'] - $deal['load_money'], 2);
        $deal['need_money'] = format_price($deal['need_money_decimal']);
        $deal['need_money_detail'] = format_price($deal['need_money_decimal'] / 10000,false);
        //百分比
        $deal['progress_point'] = $deal['point_percent'] * 100;

        //投标剩余时间
        if($deal['deal_status'] <> 1 || $deal['remain_time'] <= 0){
            $deal['remain_time_format'] = "0".$GLOBALS['lang']['DAY']."0".$GLOBALS['lang']['HOUR']."0".$GLOBALS['lang']['MIN'];
        }
        else{
            $deal['remain_time_format'] = remain_time($deal['remain_time']);
        }

		// 流标时间 2014-2-17 9:49
		if (!empty($deal['bad_time'])){
			$deal['flow_standard_time'] =  to_date($deal['bad_time'],"Y年m月d日");
		}
		// 满标时间
		if (!empty($deal['success_time'])){
			$deal['full_scale_time'] = to_date($deal['success_time'],"Y年m月d日");
		}

        if($deal['deal_status']==4){
            // 处于“还款中”的状态
            $delta_month_time = get_delta_month_time($deal['loantype'], $deal['repay_time']);
            if($deal['last_repay_time'] > 0){

                // 如果是按天一次性
                if($deal['loantype'] == 5)
                    $deal["next_repay_time"] = next_replay_day_with_delta($deal['last_repay_time'], $delta_month_time);
                else
                    $deal["next_repay_time"] = next_replay_month_with_delta($deal['last_repay_time'], $delta_month_time);
            }
            else{
                if($deal['loantype'] == 5)
                    $deal["next_repay_time"] = next_replay_day_with_delta($deal['repay_start_time'], $delta_month_time);
                else
                    $deal["next_repay_time"] = next_replay_month_with_delta($deal['repay_start_time'], $delta_month_time);
            }

            //总的必须还多少本息
            if ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
                $deal['remain_repay_money'] = get_deal_total_repay_money_month_interest($deal['loantype'], $deal['repay_time'], $deal['borrow_amount'], $deal['rate']);
            } else if($deal['id'] > $GLOBALS['dict']['OLD_DEAL_ID']) {
                $deal['remain_repay_money'] = get_deal_total_repay_money_from_pmt($deal['id']);
            } else {
                $deal['remain_repay_money'] = get_deal_total_repay_money($deal['loantype'], $deal['repay_time'], $deal['borrow_amount'], $deal['rate']);
            }

            //还有多少需要还
            $deal['need_remain_repay_money'] = $deal['remain_repay_money'] - $deal['repay_money'];
            //还款进度条
            $deal['repay_progress_point'] =  $deal['repay_money']/$deal['remain_repay_money']*100;

            //最后一期还款
            $deal['last_month_repay_money'] = $deal['month_repay_money'];

            //最后的还款日期
            $y=to_date($deal['repay_start_time'],"Y");
            $m=to_date($deal['repay_start_time'],"m");
            $d=to_date($deal['repay_start_time'],"d");
            $y = $y + intval(($m+$deal['repay_time'])/12);
            $m = ($m+$deal['repay_time'])%12;

            $deal["end_repay_time"] = to_timespan($y."-".$m."-".$d,"Y-m-d");

            //罚息
            if($deal["next_repay_time"] - $time <0){
                $deal['impose_money'] = 0;
            }
        }

        $durl = url("index","deal",array("id"=>$deal['id']));
        $deal['share_url'] = get_domain().$durl;
        if($GLOBALS['user_info'])
        {
            if(app_conf("URL_MODEL")==0)
            {
                $deal['share_url'] .= "&r=".base64_encode(intval($GLOBALS['user_info']['id']));
            }
            else
            {
                $deal['share_url'] .= "?r=".base64_encode(intval($GLOBALS['user_info']['id']));
            }
        }
        $deal['url'] = $durl;
        // 2013/06/30 添加还款方式  Liwei  ADD
        $deal['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        $deal['loan_period_name'] = get_loan_period($deal['loantype'],$deal['repay_time']);
        //修改此处为借款用途的图标  2013/07/02 Liwei Add
        $deal['icon'] = str_replace("./public/images/dealtype/","./static/img/dealtype/",$deal['type_info']['icon']);
        $deal['user_deal_name'] = get_deal_username($deal['user_id']);

        ///////////////////////利率不从配置取，改取数据库  edit by wenyanlei 20130816//////////////

        //后台填的年利率
        $deal['int_rate'] = $deal['rate'];


        if($deal['loantype'] == 4){
            $period_income_rate = (1 + $deal['int_rate']/12/100 * $deal['repay_time']) * (1 - $deal['manage_fee_rate'] /12/100 * $deal['repay_time']) -1;
            $deal['rate'] = round($period_income_rate * 12 / $deal['repay_time']*100, 2)."%";
        } else {
        //出借人年化收益率
            $deal['rate'] = ($deal['income_fee_rate'] > 0) ? $deal['income_fee_rate']: get_invest_rate_data($deal['loantype'], $deal['repay_time']);
            $deal['rate'] = number_format($deal['rate'], 2) . "%"; // 把后台各项费率小数位数位数放开到5位，前端显示放2位，四舍五入 --20140102
        }
        $deal['rate_show'] = number_format($deal['rate'],2);

        //后台修改的借款年利率
        $deal['deal_rate'] = number_format($deal['int_rate'], 2) .'%';

        $deal['name'] = get_deal_title($deal['name'], $deal['type_info']['name']);
        $deal['show_focus'] = 1;
        //获取此标的担保人状态
        $deal['guarantor_status'] = check_deal_guarantor_status(intval($id));
        //获取此标的投资人群
        $deal['crowd_str'] = $GLOBALS['dict']['DEAL_CROWD'][$deal['deal_crowd']];

        // 订单附加信息
        $deal_ext = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "deal_ext WHERE deal_id=" . $deal['id']);
        if (!empty($deal_ext)) {
            $deal = array_merge($deal, $deal_ext);
        }

        $deal['income_ext_rate'] = number_format($deal['income_float_rate']+$deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_base_rate'] = number_format($deal['income_base_rate'], 2, ".", "");
        $deal['income_float_rate'] = number_format($deal['income_float_rate'], 2, ".", "");
        $deal['income_subsidy_rate'] = number_format($deal['income_subsidy_rate'], 2, ".", "");
    }
    //print_r($deal);
    return $deal;
}
/**
 * 检查订单的担保人同意状态
 *
 * @Title: check_deal_guarantor_status
 * @Description: 检查订单的担保人同意状态
 * @param $deal 订单
 * @return status
 * @author Liwei
 * @throws
 * status 状态（0创建未绑定，1已绑定，2同意担保，3不同意担保）
 *
 */
function check_deal_guarantor_status($deal_id){
    if(empty($deal_id)) return false;
    $guarantor_list = $GLOBALS['db']->getAll("select status from ".DB_PREFIX."deal_guarantor where deal_id=".$deal_id);
    $status = 2;
    if(!empty($guarantor_list)){
        foreach ($guarantor_list as $guarantor){
            if($guarantor['status'] == 0){
                return $status = 0;
            }elseif ($guarantor['status'] == 1){
                return $status = 1;
            }elseif ($guarantor['status'] == 3){
                return $status = 3;
            }
        }
    }
    return $status;
}


function get_deal_rate($lantype, $repay_time){
    if(empty($lantype) || empty($repay_time)) return false;
    FP::import("module.ajax","","Module.class.php");
    $ajaxmodule = new ajaxModule();
    $ajaxresult = $ajaxmodule->getRate($lantype, $repay_time,NULL,true);
    $ajaxresult = json_decode($ajaxresult,true);
    return $ajaxresult;
}

/**
 * 计算还款周期
 */
function get_loan_period($loadtype_id,$repay_time=NULL){
    if (empty($loadtype_id)) return false;
    if (empty($repay_time)) return false;
    switch ($loadtype_id){
    case 1:
        $repay_time = $GLOBALS['dict']['REPAY_TIME'][3];
        break;
    case 2:
    case 4:
        $repay_time = "1个月";
        break;
    case 3:
        $repay_time = $repay_time."个月";
        break;
    case 5:
        $repay_time = $repay_time."天";
        break;
    default:
        $repay_time = "";
        break;
    }
    return $repay_time;
}

/**
 * 获取正在进行的投标列表
 * 2013/06/28 添加 $display 默认为TURE，如果为FALSE则不进行is_visible过滤
 */
function get_deal_list($limit,$cate_id=0, $where='',$orderby = '',$display = TRUE, $is_all_sites = 0,$is_count = false)
{
    //edit by zhangruoshi, about deal muti-site
	/* 2013/06/28 By Liwei 修改where逻辑，加入不可见过滤*/
    if($display){
        $where = empty($where) ? " deal.is_visible = 1" : $where." AND deal.is_visible = 1";
    }

	$time = get_gmtime();
    $site_id = app_conf('TEMPLATE_ID');
	$count_sql = "select count(*) from ".DB_PREFIX."deal as deal ,".DB_PREFIX."deal_site as site where (deal.is_effect = 1 or deal.is_update = 1) and deal.is_delete = 0 and deal.id=site.deal_id";
	if ($is_all_sites == 0) {
		$count_sql .= " and site.site_id=".$site_id;
	}
	if(es_cookie::get("shop_sort_field")=="ulevel"){
		$extfield = ",(SELECT u.level_id FROM ".DB_PREFIX."user u WHERE u.id=user_id ) as ulevel";
	}

	$sql = "select deal.*,deal.start_time as last_time, deal.point_percent * 100 as progress_point,(deal.start_time + deal.enddate*24*3600 - ".$time.") as remain_time $extfield from ".DB_PREFIX."deal as deal , ".DB_PREFIX."deal_site as site where (deal.is_effect = 1 or deal.is_update = 1) and deal.is_delete = 0 and deal.id=site.deal_id";

	if ($is_all_sites == 0) {
		$sql .= " and site.site_id=".$site_id." ";
	}
	if ($cate_id>0) {
		$ids =load_auto_cache("deal_sub_parent_cate_ids",array("cate_id"=>$cate_id));
		$sql .= " and deal.cate_id in (".implode(",",$ids).")";
		$count_sql .= " and deal.cate_id in (".implode(",",$ids).")";
	}

    if($where != '')
    {
        $sql.=" and ".$where;
        $count_sql.=" and ".$where;
    }

    if($orderby=='')
        $sql.=" order by deal.sort desc limit ".$limit;
    else
        $sql.=" order by ".$orderby." limit ".$limit;
    //edit by zhangruoshi, about deal muti-site, end

    if($is_count){//只算数
    	$deals_count = $GLOBALS['db']->getOne($count_sql);
    	return array('count'=>$deals_count);
    }
    $deals = $GLOBALS['db']->getAll($sql);
    $deals_count = $GLOBALS['db']->getOne($count_sql);
    //print_r($deals);
    //echo $sql;
    if($deals)
    {
        foreach($deals as $k=>$deal)
        {
            //格式化数据
            $deals[$k] = get_deal($deal['id'],$deal['cate_id']);
        }
    }
    return array('list'=>$deals,'count'=>$deals_count);
}

/**
 * 格式化 deal list 用于首页和 投资列表页面
 * @param unknown $deal_list
 * @return unknown|string
 */
function format_deals_list($deal_list){
	if(!$deal_list['list']){
		return $deal_list;
	}
	foreach($deal_list['list'] as $key => $dv){ // 重新格式化一些数据
		$deal_list['list'][$key]['name'] = msubstr($dv['name'],0,20);
		$deal_list['list'][$key]['old_name'] = $dv['name'];
		if($dv['deal_status'] <> 1 || $dv['remain_time'] <= 0){
			$deal_list['list'][$key]['remain_time_format'] = "0".$GLOBALS['lang']['DAY']."0".$GLOBALS['lang']['HOUR']."0".$GLOBALS['lang']['MIN'];
		}
		else{
			$d = intval($dv['remain_time']/86400);
			$h = floor($dv['remain_time']%86400/3600);
			$m = floor($dv['remain_time']%3600/60);
			$deal_list['list'][$key]['remain_time_format'] = $d.$GLOBALS['lang']['DAY'].$h.$GLOBALS['lang']['HOUR'].$m.$GLOBALS['lang']['MIN'];
		}
		$deal_list['list'][$key]['need_money_detail'] = format_price($dv['need_money_decimal'],false);
		if (!empty($dv['bad_time'])){
			$curr_year = date("Y",get_gmtime());
			$database_year = date("Y",$dv['bad_time']);
			if ($database_year != $curr_year){
				$bad_time_format = 'Y年m月d日';
			}else{
				$bad_time_format = 'm月d日';
			}

			$deal_list['list'][$key]['flow_standard_time'] = to_date($dv['bad_time'],$bad_time_format);
		}
		if (!empty($dv['success_time'])){
			$su_curr_year = date("Y",get_gmtime());
			$su_database_year = date("Y",$dv['success_time']);
			if ($su_database_year != $su_curr_year){
				$su_time_format = 'Y年m月d日';
			}else{
				$su_time_format = 'm月d日';
			}

			$deal_list['list'][$key]['full_scale_time'] = to_date($dv['success_time'],$su_time_format);
		}
	}
	return $deal_list;
}

/**
 * 根据贷款类型，获得每两次还款的间隔时间，单位为“月”
 */
function get_delta_month_time($loantype, $repay_time) {
    if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
        $delta_month_time = 3;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
        $delta_month_time = 1;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
        $delta_month_time = $repay_time;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
        $delta_month_time = 1;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
        $delta_month_time = 3;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
        $delta_month_time = 1;
    } else if($loantype == 5) {
        $delta_month_time = $repay_time;
    }

    return $delta_month_time;
}

function get_delta_month_time_ext($deal, &$deal_load_count, &$delta_month_time) {
    if ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
        $deal_load_count = $deal['repay_time'] / 3;
        $delta_month_time = 3;
    } else if ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
        $deal_load_count = $deal['repay_time'];
        $delta_month_time = 1;
    } else if ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
        $deal_load_count = 1;
        $delta_month_time = $deal['repay_time'];
    } else if ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
        $deal_load_count = $deal['repay_time'];
        $delta_month_time = 1;
    } else if ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
        $deal_load_count = $deal['repay_time'] / 3;
        $delta_month_time = 3;
    } else if ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
        $deal_load_count = $deal['repay_time'];
        $delta_month_time = 1;
    } else if ($deal['loantype'] == 5) {
        $deal_load_count = 1;
        $delta_month_time = $deal['repay_time'];
    }
}

/**
 * 计算是否能够进行还款
 * 能够还款的条件是：
 *  1、本期尚未还款
 *  2、当前日期正是本期还款时间
 * @return bool
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
function allow_repay($repay_day){
    if($repay_day <= get_gmtime()){
        return true;
    }
    return false;
}

/**
 * 计算deal的最后一次还款日期
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
function get_deal_end_day($deal){
    $repay_day = $deal['repay_start_time'];
    $deal_load_count = 0; // 需要拆分为多少期来还款
    $delta_month_time = 1;
    get_delta_month_time_ext($deal, $deal_load_count, $delta_month_time);
    if($deal['loantype'] == 5){
        $repay_day = next_replay_day_with_delta($repay_day, $delta_month_time * $deal_load_count);
    } else{
        $repay_day = next_replay_month_with_delta($repay_day, $delta_month_time * $deal_load_count);
    }
    return $repay_day;
}

/**
 * 提前还款实际还款(回款)总额计算
 *
 * @param $remain_principal 剩余本金
 * @param $remain_days 剩余还款天数
 * @param $compensation_days 利息补偿天数
 * @param $rate 借款年利率(or收益率)
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
function prepay_money($remain_principal, $remain_days, $compensation_days, $rate) {
    return $remain_principal * (1 + (($remain_days + $compensation_days) / 360) * ($rate / 100));
}

/**
 * 提前还款利息部分计算
 *
 * @return void
 **/
function prepay_money_intrest($remain_principal, $remain_days, $rate) {
    return $remain_principal * ((($remain_days) / 360) * ($rate / 100));
}

function prepay_status($deal_id){
    $sql = "select status from ".DB_PREFIX."deal_prepay where deal_id=".$deal_id." order by id desc";
    return $GLOBALS['db']->getOne($sql);
}

/**
 * 剩余未还本金
 * 按月等额类型为剩余本金，其他均为借款总额
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

function get_remain_principal($deal){
    if(in_array($deal['loantype'],array(
        $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'],
        $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'],
        $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'],
    ))) {
        $deal_loan_repay = new \app\models\dao\DealLoanRepay();
        $has_repay = $deal_loan_repay->getTotalPrincipalMoney($deal['id']);
        return $deal['borrow_amount'] - $has_repay;
    }else{
        return $deal['borrow_amount'];
    }
}

function get_last_repay_time($deal){
    $last_day = $deal['last_repay_time'];
    if($last_day == 0){
        $last_day = $deal['repay_start_time'];
    }
    return $last_day;
}

/**
 * 提前还款时的计息天数
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
function get_remain_day($deal, $prepay_time){
    $last_day = get_last_repay_time($deal);
    $days = ($prepay_time - $last_day)/(24*60*60);

    return intval($days);
}

/**
 * 取消冻结并添加余额
 *
 * @param $money 取消冻结金额
 * @param $user_id 用户id
 * @param $message 资金变动消息内容
 * @param $admin_id 管理员id
 *
 * @return void
 **/
function unlock_money($money, $user_id, $message = "" , $admin_id = 0,$note=''){
    //冻结资金
    $sql = "update ".DB_PREFIX."user set lock_money = lock_money - $money,money = money + $money where id =".$user_id;
    $GLOBALS['db']->query($sql);

	$user_dao = new \app\models\dao\User();
	$user = $user_dao->find($user_id);

    //记录日志
    $log_info = array(
        'money'=>  floatval($money),
        'lock_money'=>  -floatval($money),
        'log_info' => $message,
        'log_time' => get_gmtime(),
        'user_id' => $user_id,
        'log_admin_id'=>$admin_id,
    	'note'=>$note,
	    'remaining_money' => $user->money,
	    'remaining_total_moeny' => $user->money + $user->lock_money,
    );
    $GLOBALS['db']->autoExecute(DB_PREFIX."user_log",$log_info,"INSERT");
}

/**
 * 扣除余额并冻结
 *
 * @param $money 冻结金额
 * @param $user_id 用户id
 * @param $message 资金变动消息内容
 * @param $admin_id 管理员id
 *
 * @return void
 **/
function lock_money($money, $user_id, $message = "" , $admin_id = 0,$note= ''){
    //冻结资金
    $sql = "update ".DB_PREFIX."user set lock_money = lock_money + $money,money = money - $money where id =".$user_id;
    $GLOBALS['db']->query($sql);

	$user_dao = new \app\models\dao\User();
	$user = $user_dao->find($user_id);

    //记录日志
    $log_info = array(
        'money'=>  -floatval($money),
        'lock_money'=>  floatval($money),
        'log_info' => $message,
        'log_time' => get_gmtime(),
        'user_id' => $user_id,
        'log_admin_id'=>$admin_id,
        'note'=>$note,
	    'remaining_money' => $user->money,
	    'remaining_total_money' => $user->money + $user->lock_money,
    );
    $GLOBALS['db']->autoExecute(DB_PREFIX."user_log",$log_info,"INSERT");
}

/**
 * 获取提前还款的数据
 **/
function get_inrepay_repay($deal_id, $user_id, $principal){
    $sql = "select * from firstp2p_deal_inrepay_repay where deal_id =$deal_id  and user_id=$user_id and principal=$principal";
    return $GLOBALS['db']->getRow($sql);
}

/**
 * 还款列表
 */
function get_deal_load_list($deal){
    $time = get_gmtime();
    $repay_day = $deal['repay_start_time'];
    $deal_load_count = 0; // 需要拆分为多少期来还款
    $delta_month_time = 1;

    get_delta_month_time_ext($deal, $deal_load_count, $delta_month_time);

    for($i = 0; $i < $deal_load_count; $i++) {
        $loan_list[$i]['status'] = 0;

        // 如果是按天一次性
        if($deal['loantype'] == 5)
            $repay_day = $loan_list[$i]['repay_day'] = next_replay_day_with_delta($repay_day, $delta_month_time);
        else
            $repay_day = $loan_list[$i]['repay_day'] = next_replay_month_with_delta($repay_day, $delta_month_time);
        $loan_list[$i]['allow_repay'] = allow_repay($repay_day);

        $is_last =  ($i + 1== $deal_load_count); //是否最后一次还款

        if($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            //按月付息单独处理
            $loan_list[$i]['month_repay_money'] = get_deal_repay_money_month_interest($deal['loantype'], $deal['repay_time'], $deal['borrow_amount'], $deal['int_rate'], $is_last);
        }else if($deal['id'] > $GLOBALS['dict']['OLD_DEAL_ID']) {
            $loan_list[$i]['month_repay_money'] = get_deal_repay_money_from_pmt($deal['id'], $is_last);
        } else {
            $loan_list[$i]['month_repay_money'] = get_deal_repay_money($deal['loantype'], $deal['repay_time'], $deal['borrow_amount'], $deal['int_rate'], $is_last);
        }

        //判断是否已经还完
        $repay_item = $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."deal_repay WHERE deal_id=".$deal['id']." AND repay_time=".$repay_day."");
        if ($repay_item) {
            $loan_list[$i]['true_repay_time'] = $repay_item['true_repay_time'];
            $loan_list[$i]['month_has_repay_money'] = $repay_item['repay_money'];
            $loan_list[$i]['month_manage_money'] = 0;
            $loan_list[$i]['has_repay'] = 1;
            $loan_list[$i]['status'] = $repay_item['status']+1;
            $loan_list[$i]['month_repay_money'] -= $loan_list[$i]['repay_money'];
            $loan_list[$i]['impose_money'] = $repay_item['impose_money'];
            $loan_list[$i]['month_has_repay_money_all'] = $loan_list[$i]['month_has_repay_money'] + $deal['month_manage_money']+$loan_list[$i]['impose_money'];
        } else {
            $loan_list[$i]['month_manage_money'] = 0; // 管理费因为一次性已经交完，故此处管理费为0
            $loan_list[$i]['has_repay'] = 0;
            //判断是否罚息
            if($time > $repay_day) {
                //晚多少天
                $time_span = to_timespan(to_date($time,"Y-m-d"),"Y-m-d");
                $next_time_span = to_timespan(to_date($repay_day,"Y-m-d"),"Y-m-d");
                $day  = ceil(($time_span-$next_time_span)/24/3600);
                $loan_list[$i]['impose_money'] = 0; // 暂时设定罚息为0
            }
        }
        if ($loan_list[$i]['status']== 0) {
            $loan_list[$i]['month_need_all_repay_money'] =  $loan_list[$i]['month_repay_money'] + $loan_list[$i]['month_manage_money'] + $loan_list[$i]['impose_money'];
        } else{
            $loan_list[$i]['month_need_all_repay_money'] = 0;
        }
    }

    return $loan_list;
}


/**
 * 用户还款列表
 */
function get_deal_user_load_list($user_load_info,$deal){
    $time = get_gmtime();
    $repay_day = $user_load_info['repay_start_time'];
    $deal_load_count = 0;
    $loantype = $deal['loantype'];
    // 获得开始还款的日期
    if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
        // 如果是按季等额还款
        $deal_load_count = $user_load_info['repay_time'] / 3;
        $delta_month_time = 3;
        $manage_fee_count = 3;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
        // 如果是按月等额还款
        $deal_load_count = $user_load_info['repay_time'];
        $delta_month_time = 1;
        $manage_fee_count = 1;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
        // 如果是到期支付本金收益
        $deal_load_count = 1;
        $delta_month_time = $user_load_info['repay_time'];
        $manage_fee_count = $user_load_info['repay_time'];
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
        $deal_load_count = $user_load_info['repay_time'];
        $delta_month_time = 1;
        $manage_fee_count = 1;
    }
    else if ($loantype == 5) {
        // 如果是按天一次性
        $deal_load_count = 1;
        $delta_month_time = $user_load_info['repay_time'];
        $manage_fee_count = $user_load_info['repay_time'];
    }

    $deal_loan_repay_model = new \app\models\dao\DealLoanRepay();
    for($i = 0; $i < $deal_load_count; $i++) {
        $loan_list[$i]['status'] = 0;
        $loan_list[$i]['user_id'] = $user_load_info['user_id'];
        // 如果是按天一次性
        if($deal['loantype'] == 5)
            $repay_day = $loan_list[$i]['repay_day'] = next_replay_day_with_delta($repay_day, $delta_month_time);
        else
            $repay_day = $loan_list[$i]['repay_day'] = next_replay_month_with_delta($repay_day, $delta_month_time);

        $is_last =  ($i + 1== $deal_load_count); //是否最后一次还款

        if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $month_manage_money= 0;
            $loan_list[$i]['month_repay_money'] = get_deal_repay_money_loan_month_interest($deal, $user_load_info, $is_last, $month_manage_money, $month_loan_amount, $month_intrerest);
            $loan_list[$i]['month_manage_money'] = $month_manage_money; //管理费
            $loan_list[$i]['month_loan_amount'] = $month_loan_amount; //本金部分
            $loan_list[$i]['month_intrerest'] = $month_intrerest; //利息部分
        } else if($deal['id'] > $GLOBALS['dict']['OLD_DEAL_ID']){
            $loan_list[$i]['month_repay_money'] = get_deal_repay_money_loan_from_pmt($deal, $user_load_info, $month_loan_amount, $month_intrerest);
            $loan_list[$i]['month_loan_amount'] = $month_loan_amount; //本金部分
            $loan_list[$i]['month_intrerest'] = $month_intrerest; //利息部分
            // 如果是按天一次性
            if($deal['loantype'] == 5)
            {
                $loan_list[$i]['month_manage_money'] = ceilfix($loan_list[$i]['month_repay_money'] - get_deal_repay_money_day($deal['repay_time'], $user_load_info['money'], $deal['rate'] / 100));
            }
            else
                $loan_list[$i]['month_manage_money'] = ceilfix($loan_list[$i]['month_repay_money'] * $user_load_info['manage_fee_rate'] * $manage_fee_count / 12 / 100);
        } else {
            $loan_list[$i]['month_repay_money'] = get_deal_repay_money_loan($deal, $user_load_info, $month_loan_amount, $month_intrerest);
            $loan_list[$i]['month_manage_money'] = 0; //无管理费
            $loan_list[$i]['month_loan_amount'] = $month_loan_amount; //本金部分
            $loan_list[$i]['month_intrerest'] = $month_intrerest; //利息部分
        }

        //判断是否已经还完
        $repay_item = $deal_loan_repay_model->getDealLoanRepayByTime($user_load_info['deal_id'], $repay_day);

        $loan_list[$i]['month_has_repay_money'] = 0;
        $loan_list[$i]['real_month_repay_money'] = $loan_list[$i]['month_repay_money'] - $loan_list[$i]['month_manage_money'];
        $loan_list[$i]['deal_load_id'] = $user_load_info['id'];

        if($repay_item) {
            $loan_list[$i]['month_has_repay_money'] = $repay_item['repay_money'];

            $loan_list[$i]['has_repay'] = 1;
            $loan_list[$i]['status'] = 1;
            $loan_list[$i]['month_repay_money'] -= $loan_list[$i]['repay_money'];
            $loan_list[$i]['impose_money'] = $repay_item['impose_money'];

            //真实还多少?这个计算可能有问题。
            $loan_list[$i]['month_has_repay_money_all'] = $loan_list[$i]['month_has_repay_money'] + $user_load_info['month_manage_money'] + $loan_list[$i]['impose_money'];
        } else {
            $loan_list[$i]['has_repay'] = 0;
            //判断是否罚息
            if($time > $repay_day) {
                //晚多少天
                $time_span = to_timespan(to_date($time,"Y-m-d"),"Y-m-d");
                $next_time_span = to_timespan(to_date($repay_day,"Y-m-d"),"Y-m-d");
                $day  = ceil(($time_span-$next_time_span)/24/3600);
                $loan_list[$i]['impose_money'] = 0; // 暂时设定罚息为0，没有罚息
            }
        }
    }
    return $loan_list;
}

//获取该期剩余本金
function get_benjin($idx,$all_idx,$amount_money,$month_repay_money,$rate){
    //计算剩多少本金
    $benjin = $amount_money;
    for($i=1;$i<=$idx+1;$i++){

        $benjin = $benjin - ($month_repay_money - $benjin*$rate/12/100);
    }
    return $benjin;
}

function insert_success_deal_list(){
    //输出成功案例
    $suc_deal_list =  get_deal_list(11,0,"deal_status in(4,5) "," success_time DESC,sort DESC,id DESC");
    $GLOBALS['tmpl']->assign("succuess_deal_list",$suc_deal_list['list']);
    return $GLOBALS['tmpl']->fetch("inc/insert/success_deal_list.html");
}


//更改过期流标状态
function change_deal_status(){
    syn_dealing();
}

/**
 * 计算并更新子母单进度和余额();
 * @author Liwei
 * @date Jun 28, 2013 11:50:36 AM
 * $deal :标 ;
 */
function check_deal($deal,$request,$ajax, $user_id = null, $user_name = null, $source_type = null, $site_id = null) {
    if(empty($deal) || empty($request)){
        return showErr($GLOBALS['lang']['ERROR_TITLE'].'101',$ajax);eixt();
    }

    $user_id = $user_id ?: $GLOBALS['user_info']['id'];
    $user_name = $user_name ?: $GLOBALS['user_info']['user_name'];
    $site_id = $site_id ?: app_conf("TEMPLATE_ID");


    $bid_money = floatval(trim($request["bid_money"]));
    $result_data = array();

    // 如果是普通标
    $left_load_money = $deal['borrow_amount'] - $deal['load_money'];
    if (bccomp($left_load_money, $bid_money) == -1) {
        // 超出金额
        return false;
    }

    ////////////////////// 更新普通单
    $deal['load_money'] += $bid_money;
    $deal['point_percent'] = $deal['load_money'] / $deal['borrow_amount'];
    // Do update
    modify_deal_percent_load_money($deal["id"], $deal['load_money'], $deal['point_percent']);

    ///////////////////// 写进 deal_load的log，记录下来投普通单的情况
    $data['money'] = $bid_money;
    $data['user_id'] = $user_id;
    $data['user_name'] = $user_name;
    $data['user_deal_name'] = get_deal_username($user_id); //添加投标列表显示的用户名
    $data['create_time'] = get_gmtime();
    $data['from_deal_id'] = 0;
    $data['deal_id'] = $deal["id"];
    $data['source_type'] = $source_type;
    $data['deal_parent_id'] = -1;
    $data['site_id'] = $site_id;
    $data['ip'] = get_client_ip();
    $GLOBALS['db']->autoExecute(DB_PREFIX."deal_load",$data,"INSERT");
    $result_data['general'] = $GLOBALS['db']->insert_id();

    return $result_data;
}

/**
 * 直接从数据库中获取基本的deal单的信息，不做任何处理计算
 */
function get_basic_deal_info($deal_id) {
    $sql = "SELECT * FROM " . DB_PREFIX . "deal WHERE id=" . $deal_id;
    return $GLOBALS['db']->getRow($sql);
}

/**
 * 获取母单的子单ID;
 * @author Liwei
 * @date Jun 28, 2013 11:50:36 AM
 */
function get_sub_deal_list_by_parentid($id){
    if(empty($id)) return FALSE;
    $sql = "SELECT * FROM ".DB_PREFIX."deal WHERE parent_id=" . $id . ' ORDER BY load_money DESC';
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 获取已投标总金额
 * @author Liwei
 * @date Jun 29, 2013 9:48:26 AM
 */
function get_deal_load_money($id) {
    if(empty($id)) return FALSE;
    $sql = "SELECT sum(money) FROM ".DB_PREFIX."deal_load WHERE deal_id = ".$id;
    $load_info = $GLOBALS['db']->getOne($sql);
    return $load_info;
}

function modify_deal_percent_load_money($deal_id, $load_money, $point_percent){
    $result = $GLOBALS['db']->query("update ".DB_PREFIX."deal set load_money=".$load_money.",point_percent=".$point_percent.",buy_count=buy_count+1 where id =".$deal_id);
    return $result;
}

function make_sub_deal_loaded($parent_deal_id) {
    $result = $GLOBALS['db']->query("update ".DB_PREFIX."deal set is_sub_deal_loaded=1 where id=".$parent_deal_id);
    return $result;
}

/**
 * 获得母单的剩余投资额度
 */
function get_parent_deal_left_load_money($parent_deal_id) {
    if (empty($parent_deal_id)) {
        return 0;
    }

    $sub_deal_list = get_sub_deal_list_by_parentid($parent_deal_id);
    $count = count($sub_deal_list);
    $min_left_load_money = 9999999999999;

    $sub_deal_percent = get_twelveperiod_percent();
    $is_already_load_money = false;
    $total_amount = 0;

    $is_sub_deal_loaded = is_sub_deal_loaded($parent_deal_id);
    foreach($sub_deal_list as $sub_deal) {
        if ($sub_deal['load_money'] > 0) {
            $is_already_load_money = true;
        }

        $sub_deal_left_load_money = $sub_deal['borrow_amount'] - $sub_deal['load_money'];
        $total_amount += $sub_deal_left_load_money;
        $num = intval($sub_deal['repay_time']);
        switch($num) {
        case 3:
            $result = floor($sub_deal_left_load_money / ($sub_deal_percent['threeperiod'] / $sub_deal_percent['total']));

            break;
        case 6:
            $result = floor($sub_deal_left_load_money / ($sub_deal_percent['sixperiod'] / $sub_deal_percent['total']));

            break;
        case 9:
            $result = floor($sub_deal_left_load_money / ($sub_deal_percent['nineperiod'] / $sub_deal_percent['total']));

            break;
        case 12:
            $result = floor($sub_deal_left_load_money / ($sub_deal_percent['twelveperiod'] / $sub_deal_percent['total']));
            break;
        }
        if ($min_left_load_money > $result) {
            $min_left_load_money = $result;
        }
    }
    if (!$is_already_load_money || !$is_sub_deal_loaded) {
        return $total_amount;
    }
    return $min_left_load_money;
}


function get_parent_deal_point_percent($parent_deal_id) {
    if (empty($parent_deal_id)) {
        return 0;
    }

    $sub_deal_list = get_sub_deal_list_by_parentid($parent_deal_id);
    $count = count($sub_deal_list);
    $max_point_percent = -99999;
    foreach($sub_deal_list as $sub_deal) {
        $sub_deal_max_point_percent = $sub_deal['point_percent'];
        if ($max_point_percent < $sub_deal_max_point_percent) {
            $max_point_percent = $sub_deal_max_point_percent;
        }
    }
    return $max_point_percent;
}

function set_deal_invisible($deal_id) {
    if(empty($deal_id)) return false;
    $sql = "UPDATE " . DB_PREFIX . "deal SET is_visible=0 WHERE id='" . $deal_id . "'" ;
    return $GLOBALS['db']->query($sql);
}

function get_twelveperiod_percent() {
    $sql = "SELECT * FROM " . DB_PREFIX . "deploy WHERE process='SEASON_EQUAL_REPAY_12'";
    return $GLOBALS['db']->getRow($sql);
}

function is_sub_deal_loaded($parent_deal_id) {
    $sql = "SELECT is_sub_deal_loaded FROM " . DB_PREFIX . "deal WHERE id='" . $parent_deal_id . "'";
    return $GLOBALS['db']->getOne($sql);
}

function get_avg_deal_self_money($loantype, $repay_period, $total_loan_amount) {
    if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
        $count = $repay_period / 3;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
        $count = $repay_period;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
        $count = 1;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
        $count = $repay_period;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
        $count = 1;
    }

    return $total_loan_amount / $count;
}


/** 跟据 deal_id 获取pmt计算出来的借款人每期还款额  */
function get_deal_repay_money_from_pmt($deal_id) {
	$finance = new Finance();
	$info = $finance->getPmtByDealId($deal_id);
    return ceilfix($info['pmt']);
}

/**
 * 根据还款类型 以及 还款周期，获得每个周期需要还的本金和利息
 *
 * @author edit by wenyanlei  2013-8-15
 * @param $repay_mode 借款类型
 * @param $repay_period 借款期限
 * @param $total_loan_amount 借款金额
 * @param $rate 借款利率
 * @return float
 */
function get_deal_repay_money($repay_mode, $repay_period, $total_loan_amount, $rate) {
    if($repay_mode == 3){//到期支付本金收益
        return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period)));
    }elseif($repay_mode == 2){//按月等额还款
        return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period))/$repay_period);
    }elseif($repay_mode == 1){//按季等额还款
        return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period))/($repay_period/3));
    }

    return 0;
}

/**
 * 计算按月支付收益到期还本类型的每期还款额
 *
 * @param $repay_mode 借款类型
 * @param $repay_period 借款期限
 * @param $total_loan_amount 借款金额
 * @param $repay_mode 借款类型
 * @param $rate 借款利率
 * @param $is_last 是否最后一次还款
 * @param $month_interest 每月还款利息部分
 * @param $month_loan_amount 每月还款本金部分
 * @return float
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com> 2013年10月10日 11:09:13
 **/
function get_deal_repay_money_month_interest($repay_mode, $repay_period, $total_loan_amount, $rate, $is_last = false, &$month_interest= null, &$month_loan_amount = null) {
    $month_loan_amount = $total_loan_amount / $repay_period; //计算每月应还本金
    $month_amount = $total_loan_amount*(1+($rate/100/12*$repay_period))/$repay_period; //每月应还总额
    $month_interest = $month_amount - $month_loan_amount; //每月应还利息
    if($is_last) {
        return ceilfix($month_interest + $total_loan_amount);
    } else {
        return ceilfix($month_interest);
    }
}

/**
 * 计算按月支付收益到期还本类型的标还款时的实际回款金额(不包括管理费)
 *
 * @param $deal array 借款信息
 * @param $user_load_info array 投标信息
 * @return float
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com> 2013年10月10日 13:35:31
 **/
function get_deal_repay_money_loan_month_interest($deal, $user_load_info, $is_last = false, &$month_manage_money, &$month_loan_amount, &$month_interest) {
    $total_loan_amount = $user_load_info['money'];
    $repay_period = $deal['repay_time'];
    $month_loan_amount = $total_loan_amount / $repay_period; //计算每月应还本金
    $real_month_amount = $total_loan_amount*(1+($deal['income_fee_rate']/100/12*$repay_period))/$repay_period; //每月实际收益
    $month_amount = $total_loan_amount*(1+($deal['int_rate']/100/12*$repay_period))/$repay_period; //每月应还总额
    $month_manage_money = ceilfix($month_amount - $real_month_amount); //平台管理费
    $month_interest = ceilfix($month_amount - $month_loan_amount); //每月应还利息
    if($is_last) {
        $month_loan_amount = ceilfix($total_loan_amount);
        return ceilfix($total_loan_amount + $month_interest);
    } else {
        $month_loan_amount = 0;
        return ceilfix($month_interest);
    }
}

/**
 * 借款人还款时，计算应该给出借人回款钱数
 * 兼容旧的收益率模式
 * @author edit by wenyanlei  2013-8-15
 * @param $deal array 借款信息
 * @param $user_load_info array 投标信息
 * @return float
 */
function get_deal_repay_money_loan($deal, $user_load_info, &$month_loan_amount, &$month_intrerest) {
    //兼容旧的收益率模式
    if($deal['income_fee_rate'] == 0){
        $deal['income_fee_rate'] = get_invest_rate_data($deal['loantype'], $deal['repay_time']);
    }

    $refund_amount = 0;
    if($deal['loantype'] == 3){ //到期支付本金收益
        $refund_amount = ceilfix($user_load_info['money']*(1+$deal['income_fee_rate']/100/12*$deal['repay_time']));
        $month_intrerest = ceilfix($user_load_info['money']*($deal['income_fee_rate']/100/12*$deal['repay_time']));
    }elseif($deal['loantype'] == 2){//按月等额还款
        $refund_amount = ceilfix($user_load_info['money']*(1+$deal['income_fee_rate']/100/12*$deal['repay_time'])/$deal['repay_time']);
        $month_intrerest = ceilfix($user_load_info['money']*($deal['income_fee_rate']/100/12*$deal['repay_time'])/$deal['repay_time']);
    }elseif($deal['loantype'] == 4){//按月支付收益到期还本
        $refund_amount = ceilfix($user_load_info['money']*(1+$deal['income_fee_rate']/100/12*$deal['repay_time'])/$deal['repay_time']);
        $month_intrerest = ceilfix($user_load_info['money']*($deal['income_fee_rate']/100/12*$deal['repay_time'])/$deal['repay_time']);
    }
    $month_loan_amount = ceilfix($refund_amount - $month_intrerest);

    //回款的单子不存在按季等额，因为按季等额的单子拆分成一次性之后，按一次性的收益率还款
    return $refund_amount;
}

/**
 * 计算按天一次性回款还款额
 **/
function get_deal_repay_money_day($repay_period, $loan_demand, $rate)
{
    $month_interest = $loan_demand + $loan_demand * $rate / 360 * $repay_period;
    return round($month_interest,2);
}

/**
 * 借款人还款时，计算应该给出借人回款钱数
 */
function get_deal_repay_money_loan_from_pmt($deal, $user_load_info, &$month_loan_amount, &$month_interest) {
        /* 刘佰魁邮件回公式：
         (1+年化借款利率/12*借款月数)*（1-账户管理费率年化/12*借款月数）-1
        在计算回款钱数的时候也就是： 用户投资金额*（1+理财期间收益率）
        一次性和按月、按季都能使用这个公式
         */
	$finance = new Finance();
	$pmtinfo = $finance->getPmtByDealId($deal['id']);

    // 计算出借人的钱在总借款中的比例，然后按比例计算需要回款的钱，上面公式还是有错，暂时按这个来计算 by qilin
    $money = $user_load_info['money'] / $pmtinfo['borrow_amount'] * $pmtinfo['pmt'];
    $month_loan_amount = ceilfix($user_load_info['money'] / $pmtinfo['repay_num']);
    $month_interest = ceilfix($money - $month_loan_amount);

    return ceilfix($money);
}


/**
 * 根据借款利率、出借人管理费 计算 出借人收益率
 * @author wenyanlei  2013-8-15
 * @param $rate float 借款年利率 %之前的数字
 * @param $manage_rate float 出借人管理费 %之前的数字
 * @return float
 */
function get_income_fee_rate($rate, $manage_rate, $repay_time){
    #$fee_rate = ((1+$rate/100)*(1-$manage_rate/100)-1)*100;

    $rate = $rate / 100;
    $manage_rate = $manage_rate / 100;
    $period_income_rate = (1 + $rate /12 * $repay_time) * (1 - $manage_rate /12 * $repay_time) -1;
    $fee_rate = $period_income_rate * 12 / $repay_time;
    $fee_rate = $fee_rate * 100;

    return number_format($fee_rate, 2);
}

/**
 * 按月付息总的还款金额
 *
 * @return void
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com> 2013年10月10日 11:28:05
 **/
function get_deal_total_repay_money_month_interest($loantype, $repay_period, $total_loan_amount, $rate) {
    //每期应还利息
    $money_per_period = get_deal_repay_money_month_interest($loantype, $repay_period, $total_loan_amount, $rate);
    //总的还款金额=每期应还利息*还款次数+本金总金额
    return $repay_period * $money_per_period + $total_loan_amount;
}
/**
 * 根据还款类型 以及 还款周期，获得总共需要还的本金和利息
 */
function get_deal_total_repay_money($loantype, $repay_period, $total_loan_amount, $rate) {
    $money_per_period = get_deal_repay_money($loantype, $repay_period, $total_loan_amount, $rate);
    if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
        $count = $repay_period / 3;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
        $count = $repay_period;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
        $count = 1;
    }
    return $count * $money_per_period;
}

/**
 * 根据还款类型 以及 还款周期，获得总共需要还的本金和利息
 */
function get_deal_total_repay_money_from_pmt($deal_id) {
	$finance = new Finance();
	$info = $finance->getPmtByDealId($deal_id);
    return ceilfix($info['pmt']) * $info['repay_num'];
}

/**
 * 获取投资年利率（收益率，面向投资人）
 */
function get_invest_rate_data($repay_mode, $repay_period) {

    if($repay_mode == 5)
    {
        return $GLOBALS['dict']['DAY_ONCE_RATE'];
    }
    else
    {
        $repay_mode = $GLOBALS['dict']['INTEREST_REPAY_MODE'][$repay_mode];
        $repay_period = $GLOBALS['dict']['REPAY_PERIOD'][$repay_period];
        if($repay_mode && $repay_period){
            $sql ="SELECT ". $repay_period . " FROM " . DB_PREFIX . "deploy WHERE process='" . $repay_mode . "'";
            $res = $GLOBALS['db']->get_slave()->getRow($sql);
            return $res[$repay_period];
        }
        return 0;
    }
}


/**
 * 获取年利率（贷款利率，面向借款人）
 */
function get_deal_rate_data($repay_mode, $repay_period) {

    if($repay_mode == 5)
    {
        return $GLOBALS['dict']['DAY_ONCE_RATE'];
    }
    else
    {
        $repay_mode = $GLOBALS['dict']['REPAY_MODE'][$repay_mode];
        $repay_period = $GLOBALS['dict']['REPAY_PERIOD'][$repay_period];
        if($repay_mode && $repay_period){
            $sql ="SELECT ". $repay_period . " FROM " . DB_PREFIX . "deploy WHERE process='" . $repay_mode . "'";
            $res = $GLOBALS['db']->getRow($sql);
            return $res[$repay_period];
        }
        return 0;
    }
}


/**
 * 发送合同相关message
 *
 * @Title: send_contract_email
 * @Description: todo(这里用一句话描述这个方法的作用)
 * @param  $deal_id  订单ID
 * @return return_type
 * @author Liwei
 * @throws
 *
 */
function send_contract_email($deal_id){
    if(empty($deal_id)) return false;
    //获取所有未发送的列表
    $contract_id_list = $GLOBALS['db']->getAll("SELECT c.id,c.number,c.deal_id,group_concat(c.`title`) as title,c.user_id,c.agency_id,group_concat(c.`id`) as att ,d.type_match_row as deal_name,d.type_id,d.name as deal_title,d.contract_tpl_type FROM ".DB_PREFIX."contract as c,".DB_PREFIX."deal as d WHERE is_send = 0 AND c.deal_id = ".$deal_id." AND c.deal_id = d.id GROUP BY c.user_id");

    $Msgcenter  = new Msgcenter();
    foreach ($contract_id_list as $contract){
        $contract['deal_name'] = get_deal_title($contract['deal_title'], '', $deal_id);
        $contract['title'] = '"'.$contract['deal_name'].'"的合同已经下发';
        //}
        //获取用户信息
        if(empty($contract['agency_id'])){
            $user_info = get_user_info($contract['user_id'],true);
            $notice_email = array(
                'user_name' => $user_info['user_name'],
                'deal_url' => get_domain().url("index","deal",array("id"=>$contract['deal_id'])),
                'deal_name' => $contract['deal_name'],
                'help_url' => get_domain().url("index","helpcenter"),
                'site_url' => get_domain().$parent_deal_info['url'],
                'site_name' => app_conf("SHOP_TITLE"),
                'msg_cof_setting_url' => get_domain().url("index","uc_msg#setting"),
                'contract_url' => get_domain().url("index", "account/contract"),
            );
            $notice_phone = array(
                'user_name' => $user_info['user_name'],
                'deal_name' => $contract['deal_name'],
            );
            $content = "<p>融资项目为“<a href='".url("index","account/contract",array())."' target='_blank'>".$contract['deal_name']."</a>”的合同已经下发，请进入 合同中心 查看！</p>";
            send_user_msg("合同已下发",$content,0,$contract['user_id'],get_gmtime(),0,true,1);

            $Msgcenter->setMsg($user_info['email'], $contract['user_id'], $notice_email, 'TPL_SEND_CONTRACT_EMAIL',$contract['title'],$contract['att'],get_deal_domain_title($deal_id));
            $Msgcenter->setMsg($user_info['mobile'], $contract['user_id'], $notice_phone, 'TPL_SEND_CONTRACT_SMS','','',get_deal_domain_title($deal_id));
            $GLOBALS['db']->autoExecute(DB_PREFIX."contract",array("is_send"=>1),"UPDATE","deal_id=".$deal_id);
        }else{
            $user_info = get_agency_info($contract['agency_id']);

            // 如果是汇赢则发送邮件到配置文件邮箱
            if($contract['contract_tpl_type'] == 'HY')
            {
                $user_info['email'] = $GLOBALS['dict']['HY_EMAIL'];
                $user_info['mobile'] = $GLOBALS['dict']['HY_MOBILE'];
            }

            $notice_email = array(
                'user_name' => $user_info['realname'],
                'deal_url' => get_domain().url("index","deal",array("id"=>$contract['deal_id'])),
                'deal_name' => $contract['deal_name'],
                'help_url' => get_domain().url("index","helpcenter"),
                'site_url' => get_domain().$parent_deal_info['url'],
                'site_name' => app_conf("SHOP_TITLE"),
                'msg_cof_setting_url' => get_domain().url("index","uc_msg#setting"),
                'contract_url' => get_domain().url("index", "account/contract"),
            );
            $notice_phone = array(
                'user_name' => $user_info['realname'],
                'deal_name' => $contract['deal_name'],
            );
            $Msgcenter->setMsg($user_info['email'], 1000000, $notice_email, 'TPL_SEND_CONTRACT_EMAIL',$contract['title'],$contract['att'],get_deal_domain_title($deal_id));
            $Msgcenter->setMsg($user_info['mobile'], 1000000, $notice_phone, 'TPL_SEND_CONTRACT_SMS','','',get_deal_domain_title($deal_id));
            $GLOBALS['db']->autoExecute(DB_PREFIX."contract",array("is_send"=>1),"UPDATE","deal_id=".$deal_id);
        }
    }
    $Msgcenter->save();
}


/**
 * 当前所有站点模板列表
 * @return mix
 */
function get_sites_template_list(){
    return $GLOBALS['sys_config']['TEMPLATE_LIST'];
}

/**
 * 返回当前订单所属的网站id
 * @param int $deal_id
 * @return mix array(1=>default,2=>9888)
 */
function get_deal_site($deal_id){

    $sql = "select * from ".DB_PREFIX."deal_site where deal_id=".$deal_id;
    $deal_sites = $GLOBALS['db']->getAll($sql);
    if(!$deal_sites) return array();

    $template_list = get_sites_template_list();
    $template_list_flip = array_flip($template_list);//id做key,name做value

    $deal_sites_data = array();
    foreach($deal_sites as $k=>$v){
        $deal_sites_data[$v['site_id']]= $template_list_flip[$v['site_id']];
    }
    return $deal_sites_data;
}

/**
 * 增加或修改单子的站点信息
 * @param int $deal_id
 * @param mix $deal_site
 */
function update_deal_site($deal_id,$deal_site, $is_api = false){
    if(empty($deal_id)) return false;

    if (count($deal_site) != 1) {
        //以后只支持一个标的对应一个site_id
        return false;
    }

	$site_id = $GLOBALS['db']->getOne("SELECT `site_id` FROM " . DB_PREFIX . "deal_site WHERE `deal_id` = '{$deal_id}'");
	if (in_array($site_id, $deal_site)) {
		return false;
	}

    try {
        $GLOBALS['db']->startTrans();

        //先删除全部子站信息
        if($site_id) {
            $r = $GLOBALS['db']->query("delete from ".DB_PREFIX."deal_site where deal_id=".$deal_id);
            if ($r === false) {
                throw new \Exception('delete deal site error');
            }
        }
 
        //再逐条添加
        foreach($deal_site as $k=>$v){
            $insert = array(
                'deal_id'=>$deal_id,
                'site_id'=>$v,
            );

            $r = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_site",$insert,"INSERT");
            if ($r === false) {
                throw new \Exception('insert deal site error');
            }
        }

        if ($is_api === false) {
            //最后变更deal表的deal_site字段
            $r = $GLOBALS['db']->query("UPDATE " . DB_PREFIX . "deal SET `site_id`='{$v}' WHERE `id` = '{$deal_id}'");
            if ($r === false) {
                throw new \Exception('update deal site error');
            }
        }

        $GLOBALS['db']->commit();
    } catch (Exception $e) {
        $GLOBALS['db']->rollback();
        return false;
    }
    return true;
}

/**
 * 首页 贷款收益概述
 */
function deals_income_view(){
	$data = array();
	//年化收益率
// 	$income_rate_min = $GLOBALS['db']->getOne("SELECT MIN(income_fee_rate) FROM ".DB_PREFIX."deal WHERE income_fee_rate > 0");
	$income_rate_min = 9;
	$income_rate_max = $GLOBALS['db']->getOne("SELECT MAX(income_fee_rate) FROM ".DB_PREFIX."deal WHERE income_fee_rate > 0");
	$data['income_rate_min'] = number_format($income_rate_min,2);
	$data['income_rate_max'] = number_format($income_rate_max,2);
	//投资人已累计投资
	$load = $GLOBALS['db']->getOne("SELECT SUM(l.money) FROM ".DB_PREFIX."deal_load AS l LEFT JOIN  ".DB_PREFIX."deal AS d ON  d.id = l.deal_id WHERE d.deal_status IN (2,4,5) ");
	$data['load'] = format_price($load/10000);
	//已为投资人带来收益
	$income_sum = $GLOBALS['db']->getOne("SELECT SUM(money) FROM ".DB_PREFIX."deal_loan_repay WHERE `status` = 1 AND `type` IN (2,4,5,7)");
	//即将带来收益
	//$income_plan_sum = $GLOBALS['db']->getOne("SELECT SUM(money) FROM ".DB_PREFIX."deal_loan_repay WHERE `status` = 0 AND `type` = 2");
	$earning = new Earning();
	$income_plan_sum = $earning->getFutureEarnMoney();
	$data['income_sum'] = format_price($income_sum/10000);
	$data['income_plan_sum'] = format_price($income_plan_sum/10000);

	return $data;
}

/**
 * 得到 deal 分类列表
 * @param string $others 显示的 types
 */
function deal_types(){
	$types = $GLOBALS['db']->getAll("SELECT id,name,istab FROM ".DB_PREFIX."deal_loan_type WHERE `is_delete` = 0 AND `is_effect` = 1 ORDER BY istab DESC");
// 	$arr = array('0'=>'','16'=>'','11'=>'','13'=>'','-1'=>'');//排序
	$others = array();
	$arr = array('0'=>'');//排序
	$arr[0]['name'] = '全部';
	$arr[0]['where'] = '';
	foreach($types as $k=>$v){
		if($v['istab'] !=0 ){
			$arr[$v['id']] = '';
			$others[] = $v['id'];
		}
		if(count($arr)>=4){
			break;
		}
	}
	$others[] = -1;
	$arr['-1'] = '';
	$others[] = -1;//其他
	if($types){
		foreach($types as $k=>$v){
			if(in_array($v['id'],$others)){
				$arr[$v['id']]['name'] = $v['name'];
				$arr[$v['id']]['where'] = ' type_id = '.$v['id']." AND ";
			}else{
				$arr[-1]['name'] = '其它';
				$arr[-1]['where'] .= $v['id'].',';
			}
		}
		$arr[-1]['where'] = ' type_id IN ('.trim($arr[-1]['where'],",").') AND ';
	}
	return array('data'=>$arr,'others'=>$others);
}

function changeDealSite($siteArr=array(),$isGold=false){
    if(app_conf('ENV_FLAG') !== 'online'){
        return $siteArr;
    }
    if($isGold){
        return array("黄金标专用"=>$siteArr['黄金标专用']);
    }
    $newKeys = array(
        '专享理财' => '大额业务',
        '专享理财(临时停用)' => '大额业务（临时停用）',
        'yijinrong_alone' => '艺金融',
        'ronghua_alone' => '荣信汇',
       // 'mulandaicn' => '木兰贷',
        '中新小贷' => '小贷',
        '智多新底层资产(专享专用)' => '智多新底层（大额）',
        '智多新底层资产(消费贷专用)' => '智多新底层（网贷）',
        '随鑫约特定' => '随鑫约（大额）',
        'quanfeng_alone' => '企业金融',
        //'普通标(3个月及以上)' => '普惠业务',
        '交易所' => '交易所（临时停用）'
    );
    $hideKeys = array(
        'firstp2pcn','firstp2p','shtcapital','yijinrong','ronghua','qianguiyouxi','diandang','chedai','fortest','quanfeng','unitedmoney','caiyitong',
        'jifubao','普通标_汇赢','公益标','新手标','yhp2p','mulandai','代销平台(3个月及以下)','yuegang','wangailicai','shanghaidai','chanrongdai','esp2p','creditzj',
        'daliandai','shandongdai','tianjindai','zsz','diyifangdai','LinYouMoments','zhongxin','qiyelicai','dajinsuo','代销平台(3个月以上)','消费贷特殊',
        '哈哈财神单独上标','黄金标专用','wangailicai_alone','普通标(3个月以下)','all-che-chan','shanghaidai_alone','chanrongdai_alone','通知贷','firstp2p_region',
        '主站和所有分站','esp2p_alone','creditzj_alone','chedai_alone','daliandai_alone','shandongdai_alone','shenyangdai_alone','tianjindai_alone','diandang_alone',
        'diyifangdai_alone','unitedmoney_alone','yhp2p_alone','zsz_alone',
    );
    $keyOrder = array(
        '大额业务' => 1,
        '大额业务（临时停用）' => 2,
        '主站' => 3,
        '商户通' => 4,
        '艺金融' => 5,
        '荣信汇' => 6,
        '木兰贷' => 7,
        '小贷' => 8,
        '智多新底层（大额）' => 9,
        '智多新底层（网贷）' => 10,
        '随鑫约（大额）' => 11,
        '企业金融' => 12,
        '开放平台定制标' => 13,
        '普惠业务' => 14,
        '交易所（临时停用）' => 15,
    );
    $returnData = array();
    foreach($siteArr as $k=>$v){
        if(!in_array($k,$hideKeys)){
            $key = array_key_exists($k,$newKeys) ? $newKeys[$k] : $k;
            $returnData[$key] = $v;
        }
    }
    return $returnData;
}
