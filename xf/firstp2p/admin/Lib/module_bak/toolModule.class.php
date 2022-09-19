<?php
// +----------------------------------------------------------------------
// | Fanwe 方维订餐小秘书商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
FP::import("app.deal");

class toolModule extends SiteBaseModule
{
    function index() {
    	toolModule::calculate();
    }
    function calculate(){
		return app_redirect(url("index"));
    	$get_data['amount'] = empty($_GET['amount']) ? "" : intval($_GET['amount']);
    	$get_data['interest'] = empty($_GET['interest']) ? "" : intval($_GET['interest']);
    	$get_data['month'] = empty($_GET['month']) ? "" : intval($_GET['month']);
    	$get_data['repayType'] = empty($_GET['repayType']) ? "" : intval($_GET['repayType']);
    	$get_data['auto_check'] = empty($_GET['amount']) ? "" : 1;
    	$GLOBALS['tmpl']->assign("get_data",$get_data);
    	$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['CALCULATE'].' - '.$GLOBALS['lang']['TOOLS']);
    	$GLOBALS['tmpl']->assign("repay_time", $GLOBALS['dict']['REPAY_TIME']);
    	$GLOBALS['tmpl']->assign("loan_type", $GLOBALS['dict']['LOAN_TYPE']);
    	$GLOBALS['tmpl']->assign("inc_file","inc/tool/calculate.html");
		$GLOBALS['tmpl']->display("page/tool.html");
    }

    function ajax_calculate(){
		return app_redirect(url("index"));
		$borrow_amount = intval($_REQUEST['borrowamount']);
		$repay_mode = intval($_REQUEST['borrowpay']);
		$repay_time = intval($_REQUEST['repayTime']);

		//修改金额计算方式  edit by wenyanlei 20130816
		$rate = get_deal_rate_data($repay_mode, $repay_time);
		$repay_money = get_deal_repay_money($repay_mode, $repay_time, $borrow_amount, $rate);
		$repay_all_money = get_deal_total_repay_money($repay_mode, $repay_time, $borrow_amount, $rate);

		$GLOBALS['tmpl']->assign("borrowamount",$borrow_amount);
		$GLOBALS['tmpl']->assign("rate", $rate);
    	$GLOBALS['tmpl']->assign("repaytime",$repay_time);
    	$GLOBALS['tmpl']->assign("repayamount",$repay_money);
    	$GLOBALS['tmpl']->assign("repayallamount",$repay_all_money);

		if ($repay_mode == intval($GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'])) {
			$period_name = "每季";
		} else if ($repay_mode == intval($GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'])) {
			$period_name = "每月";
		} else if ($repay_mode == intval($GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME'])) {
			$period_name = "一次性";
		}
		$GLOBALS['tmpl']->assign("period_name",$period_name);

		$GLOBALS['tmpl']->display("inc/tool/calculate_result.html");
    }

    function contact(){
    	$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['T_CONTACT'].' - '.$GLOBALS['lang']['TOOLS']);

    	$GLOBALS['tmpl']->assign("inc_file","inc/tool/contact.html");
		$GLOBALS['tmpl']->display("page/tool.html");
    }

    function mobile(){
    	$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['T_CHECK_MOBILE'].' - '.$GLOBALS['lang']['TOOLS']);

    	$GLOBALS['tmpl']->assign("inc_file","inc/tool/mobile.html");
		$GLOBALS['tmpl']->display("page/tool.html");
    }

    function ajax_mobile(){
    	$url = "http://api.showji.com/Locating/www.showji.com.aspx?m=".trim($_REQUEST['mobile'])."&output=json&callback=querycallback";
		$content = @file_get_contents($url);
		preg_match("/querycallback\((.*?)\)/",$content,$rs);
		echo $rs[1];
    }

    function ip(){
    	$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['T_CHECK_IP'].' - '.$GLOBALS['lang']['TOOLS']);

    	$GLOBALS['tmpl']->assign("inc_file","inc/tool/ip.html");
		$GLOBALS['tmpl']->display("page/tool.html");
    }
}
?>
