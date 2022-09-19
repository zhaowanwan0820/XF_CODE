<?php if (!defined('THINK_PATH')) exit();?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/style.css" />
<script type="text/javascript">
 	var VAR_MODULE = "<?php echo conf("VAR_MODULE");?>";
	var VAR_ACTION = "<?php echo conf("VAR_ACTION");?>";
	var MODULE_NAME	=	'<?php echo MODULE_NAME; ?>';
	var ACTION_NAME	=	'<?php echo ACTION_NAME; ?>';
	var ROOT = '__APP__';
	var ROOT_PATH = '<?php echo APP_ROOT; ?>';
	var CURRENT_URL = '<?php echo trim($_SERVER['REQUEST_URI']);?>';
	var INPUT_KEY_PLEASE = "<?php echo L("INPUT_KEY_PLEASE");?>";
	var TMPL = '__TMPL__';
	var APP_ROOT = '<?php echo APP_ROOT; ?>';
    var IMAGE_SIZE_LIMIT = '1';
</script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.timer.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/script.js"></script>
<script type="text/javascript" src="__ROOT__/static/admin/lang.js"></script>
<script type='text/javascript'  src='__ROOT__/static/admin/kindeditor/kindeditor.js'></script>
</head>
<body>
<div id="info"></div>

<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<div class="main">
<div class="main_title">优惠券结算</div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="运营通过" onclick="operation_passed(undefined, this);" />
    <input type="button" class="button" value="财务通过" onclick="finance_audit(0, 1);" />
    <!--  <input type="button" class="button" value="财务拒绝" onclick="finance_audit(0, 2);" />-->
</div>
<div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            投资人检索：会员ID:<input type="text" class="textbox" style="width:50px;" name="user_id" value="<?php echo ($_REQUEST['user_id']); ?>" />
            会员名称:<input type="text" class="textbox" name="user_name" value="<?php echo ($_REQUEST['user_name']); ?>" />
            会员编号:<input type="text" class="textbox" name="user_num" value="<?php echo ($_REQUEST['user_num']); ?>" />
            手机号:<input type="text" class="textbox" name="mobile" value="<?php echo ($_REQUEST['mobile']); ?>" />
            <br/>
            服务人检索：会员ID:<input type="text" class="textbox" style="width:50px;" name="refer_user_id" value="<?php echo ($_REQUEST['refer_user_id']); ?>" />
            会员名称:<input type="text" class="textbox" name="refer_user_name" value="<?php echo ($_REQUEST['refer_user_name']); ?>" />
            会员编号:<input type="text" class="textbox" name="refer_user_num" value="<?php echo ($_REQUEST['refer_user_num']); ?>" />
            机构会员名称:<input type="text" class="textbox" name="agency_user_name" value="<?php echo ($_REQUEST['agency_user_name']); ?>" />
            服务人邀请码:<input type="text" class="textbox" name="short_alias" value="<?php echo ($_REQUEST['short_alias']); ?>" />
            <br/>
            其它条件：
            投标ID:<input type="text" class="textbox" style="width:50px;" name="deal_load_id" value="<?php echo ($_REQUEST['deal_load_id']); ?>" />
            借款编号:<input type="text" class="textbox" style="width:50px;" name="deal_id" value="<?php echo ($_REQUEST['deal_id']); ?>" />
            借款标题:<input type="text" class="textbox" name="deal_name" value="<?php echo ($_REQUEST['deal_name']); ?>" />
            项目名称：<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" />
            类型:
            <select name='deal_type'>
                <option value="" <?php if($_REQUEST['deal_type'] == ''): ?>selected="selected"<?php endif; ?>>全部</option>
                <option value="0" <?php if($_REQUEST['deal_type'] != '' and intval($_REQUEST['deal_type']) == 0): ?>selected="selected"<?php endif; ?>>普通标</option>
                <option value="1" <?php if(intval($_REQUEST['deal_type']) == 1): ?>selected="selected"<?php endif; ?>>通知贷</option>
                <option value="2" <?php if(intval($_REQUEST['deal_type']) == 2): ?>selected="selected"<?php endif; ?>>交易所</option>
                <option value="3" <?php if(intval($_REQUEST['deal_type']) == 3): ?>selected="selected"<?php endif; ?>>专享</option>
            </select>
            投资时间:
            <input type="text" class="textbox" style="width:140px;" name="deal_load_date_begin" id="deal_load_date_begin" value="<?php echo ($_REQUEST['deal_load_date_begin']); ?>" onfocus="this.blur(); return showCalendar('deal_load_date_begin', '%Y-%m-%d 00:00:00', false, false, 'btn_deal_load_date_begin');" title="<?php echo L("COUPON_TIPS_LEVEL_REBATE_VALID_BEGIN");?>" />
            <input type="button" class="button" id="btn_deal_load_date_begin" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('deal_load_date_begin', '%Y-%m-%d %H:%M:00', false, false, 'btn_deal_load_date_begin');" />
            到
            <input type="text" class="textbox" style="width:140px;" name="deal_load_date_end" id="deal_load_date_end" value="<?php echo ($_REQUEST['deal_load_date_end']); ?>" onfocus="this.blur(); return showCalendar('deal_load_date_end', '%Y-%m-%d 23:59:59', false, false, 'btn_deal_load_date_end');" title="<?php echo L("COUPON_TIPS_LEVEL_REBATE_VALID_END");?>" />
            <input type="button" class="button" id="btn_deal_load_date_end" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('deal_load_date_end', '%Y-%m-%d %H:%M:59', false, false, 'btn_deal_load_date_end');" />

            <input type="hidden" value="CouponLog" name="m" />
            <input type="hidden" value="index" name="a" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
            <input type="button" class="button" value="导出" onclick="export_csv_file('');" />
        </form>
    </div>

    <span> 注："注册时间"和"所属网站/等级”检索效率低，尽量使用"ID、编号、会员名称"等字段搜索</span>
<!---->

<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="40" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th><a href="javascript:sortBy('l_id','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资记录ID         <?php echo ($sortType); ?> ">投资记录ID         <?php if(($order)  ==  "l_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_create_time','<?php echo ($sort); ?>','CouponLog','index')" title="按照成交时间         <?php echo ($sortType); ?> ">成交时间         <?php if(($order)  ==  "l_create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_user_id','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资人ID         <?php echo ($sortType); ?> ">投资人ID         <?php if(($order)  ==  "l_user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_user_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资人会员名称         <?php echo ($sortType); ?> ">投资人会员名称         <?php if(($order)  ==  "l_user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_user_num','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资人会员编号         <?php echo ($sortType); ?> ">投资人会员编号         <?php if(($order)  ==  "l_user_num"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('lu_real_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资人姓名         <?php echo ($sortType); ?> ">投资人姓名         <?php if(($order)  ==  "lu_real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('lu_mobile','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资人手机号         <?php echo ($sortType); ?> ">投资人手机号         <?php if(($order)  ==  "lu_mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('lu_create_time','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资人注册时间         <?php echo ($sortType); ?> ">投资人注册时间         <?php if(($order)  ==  "lu_create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_money','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资金额         <?php echo ($sortType); ?> ">投资金额         <?php if(($order)  ==  "l_money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_money_yearly','<?php echo ($sort); ?>','CouponLog','index')" title="按照年化投资额         <?php echo ($sortType); ?> ">年化投资额         <?php if(($order)  ==  "l_money_yearly"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_deal_type_text','<?php echo ($sort); ?>','CouponLog','index')" title="按照类型         <?php echo ($sortType); ?> ">类型         <?php if(($order)  ==  "l_deal_type_text"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_source_type','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资来源         <?php echo ($sortType); ?> ">投资来源         <?php if(($order)  ==  "l_source_type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_site_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资站点         <?php echo ($sortType); ?> ">投资站点         <?php if(($order)  ==  "l_site_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_deal_id','<?php echo ($sort); ?>','CouponLog','index')" title="按照借款编号         <?php echo ($sortType); ?> ">借款编号         <?php if(($order)  ==  "l_deal_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('d_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照借款标题         <?php echo ($sortType); ?> ">借款标题         <?php if(($order)  ==  "d_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('l_deal_id','<?php echo ($sort); ?>','CouponLog','index')" title="按照旧版借款标题         <?php echo ($sortType); ?> ">旧版借款标题         <?php if(($order)  ==  "l_deal_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('du_user_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照借款会员名称         <?php echo ($sortType); ?> ">借款会员名称         <?php if(($order)  ==  "du_user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('du_real_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照借款人姓名         <?php echo ($sortType); ?> ">借款人姓名         <?php if(($order)  ==  "du_real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('d_repay_time','<?php echo ($sort); ?>','CouponLog','index')" title="按照借款期限         <?php echo ($sortType); ?> ">借款期限         <?php if(($order)  ==  "d_repay_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('d_loantype_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照还款方式         <?php echo ($sortType); ?> ">还款方式         <?php if(($order)  ==  "d_loantype_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('d_deal_status','<?php echo ($sort); ?>','CouponLog','index')" title="按照订单状态         <?php echo ($sortType); ?> ">订单状态         <?php if(($order)  ==  "d_deal_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_user_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照服务人会员名称         <?php echo ($sortType); ?> ">服务人会员名称         <?php if(($order)  ==  "refer_user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_user_num','<?php echo ($sort); ?>','CouponLog','index')" title="按照服务人会员编码         <?php echo ($sortType); ?> ">服务人会员编码         <?php if(($order)  ==  "refer_user_num"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_real_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照服务人姓名         <?php echo ($sortType); ?> ">服务人姓名         <?php if(($order)  ==  "refer_real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_user_name','<?php echo ($sort); ?>','CouponLog','index')" title="按照机构会员名称         <?php echo ($sortType); ?> ">机构会员名称         <?php if(($order)  ==  "agency_user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('short_alias','<?php echo ($sort); ?>','CouponLog','index')" title="按照服务人邀请码         <?php echo ($sortType); ?> ">服务人邀请码         <?php if(($order)  ==  "short_alias"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('referer_rebate_ratio_factor','<?php echo ($sort); ?>','CouponLog','index')" title="按照结算比例系数         <?php echo ($sortType); ?> ">结算比例系数         <?php if(($order)  ==  "referer_rebate_ratio_factor"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('discount_ratio','<?php echo ($sort); ?>','CouponLog','index')" title="按照客户系数         <?php echo ($sortType); ?> ">客户系数         <?php if(($order)  ==  "discount_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('product_ratio','<?php echo ($sort); ?>','CouponLog','index')" title="按照产品系数         <?php echo ($sortType); ?> ">产品系数         <?php if(($order)  ==  "product_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('rebate_amount','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资人返点金额         <?php echo ($sortType); ?> ">投资人返点金额         <?php if(($order)  ==  "rebate_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('rebate_ratio_amount','<?php echo ($sort); ?>','CouponLog','index')" title="按照投资人返点金额比例         <?php echo ($sortType); ?> ">投资人返点金额比例         <?php if(($order)  ==  "rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('referer_rebate_amount','<?php echo ($sort); ?>','CouponLog','index')" title="按照服务人返点金额         <?php echo ($sortType); ?> ">服务人返点金额         <?php if(($order)  ==  "referer_rebate_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('referer_rebate_ratio_amount','<?php echo ($sort); ?>','CouponLog','index')" title="按照服务人返点系数金额         <?php echo ($sortType); ?> ">服务人返点系数金额         <?php if(($order)  ==  "referer_rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_rebate_amount','<?php echo ($sort); ?>','CouponLog','index')" title="按照机构返点金额         <?php echo ($sortType); ?> ">机构返点金额         <?php if(($order)  ==  "agency_rebate_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_rebate_ratio_amount','<?php echo ($sort); ?>','CouponLog','index')" title="按照机构返点比例金额         <?php echo ($sortType); ?> ">机构返点比例金额         <?php if(($order)  ==  "agency_rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('pay_status','<?php echo ($sort); ?>','CouponLog','index')" title="按照结算状态         <?php echo ($sortType); ?> ">结算状态         <?php if(($order)  ==  "pay_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','CouponLog','index')" title="按照使用时间         <?php echo ($sortType); ?> ">使用时间         <?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('pay_time','<?php echo ($sort); ?>','CouponLog','index')" title="按照结算时间         <?php echo ($sortType); ?> ">结算时间         <?php if(($order)  ==  "pay_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:120px">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td><td>&nbsp;<?php echo ($item["l_id"]); ?></td><td>&nbsp;<?php echo (to_date($item["l_create_time"])); ?></td><td>&nbsp;<?php echo ($item["l_user_id"]); ?></td><td>&nbsp;<?php echo ($item["l_user_name"]); ?></td><td>&nbsp;<?php echo ($item["l_user_num"]); ?></td><td>&nbsp;<?php echo ($item["lu_real_name"]); ?></td><td>&nbsp;<?php echo ($item["lu_mobile"]); ?></td><td>&nbsp;<?php echo (to_date($item["lu_create_time"])); ?></td><td>&nbsp;<?php echo ($item["l_money"]); ?></td><td>&nbsp;<?php echo ($item["l_money_yearly"]); ?></td><td>&nbsp;<?php echo ($item["l_deal_type_text"]); ?></td><td>&nbsp;<?php echo ($item["l_source_type"]); ?></td><td>&nbsp;<?php echo ($item["l_site_name"]); ?></td><td>&nbsp;<?php echo ($item["l_deal_id"]); ?></td><td>&nbsp;<?php echo ($item["d_name"]); ?></td><td>&nbsp;<?php echo (getOldDealNameWithPrefix($item["l_deal_id"])); ?></td><td>&nbsp;<?php echo ($item["du_user_name"]); ?></td><td>&nbsp;<?php echo ($item["du_real_name"]); ?></td><td>&nbsp;<?php echo ($item["d_repay_time"]); ?></td><td>&nbsp;<?php echo ($item["d_loantype_name"]); ?></td><td>&nbsp;<?php echo ($item["d_deal_status"]); ?></td><td>&nbsp;<?php echo ($item["refer_user_name"]); ?></td><td>&nbsp;<?php echo ($item["refer_user_num"]); ?></td><td>&nbsp;<?php echo ($item["refer_real_name"]); ?></td><td>&nbsp;<?php echo ($item["agency_user_name"]); ?></td><td>&nbsp;<?php echo ($item["short_alias"]); ?></td><td>&nbsp;<?php echo ($item["referer_rebate_ratio_factor"]); ?></td><td>&nbsp;<?php echo ($item["discount_ratio"]); ?></td><td>&nbsp;<?php echo ($item["product_ratio"]); ?></td><td>&nbsp;<?php echo ($item["rebate_amount"]); ?></td><td>&nbsp;<?php echo ($item["rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo ($item["referer_rebate_amount"]); ?></td><td>&nbsp;<?php echo ($item["referer_rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo ($item["agency_rebate_amount"]); ?></td><td>&nbsp;<?php echo ($item["agency_rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo ($item["pay_status"]); ?></td><td>&nbsp;<?php echo (to_date($item["create_time"])); ?></td><td>&nbsp;<?php echo (to_date($item["pay_time"])); ?></td><td> <?php echo ($item["opt_edit"]); ?>&nbsp; <?php echo ($item["opt_operation"]); ?>&nbsp; <?php echo ($item["opt_finance"]); ?>&nbsp; <?php echo ($item["opt_pay_list"]); ?>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="40" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script type="text/javascript">
//添加优惠券
function weeboxs_add(id){
    $.get(ROOT+'?m=CouponLog&a=add&id='+id,
        function(data){
            if(data.indexOf('{"status":0')>-1&&data.indexOf("info")>-1&&data.indexOf("data")>-1)
            {
                var jsonobj=eval('('+data+')');
                data = jsonobj.info;}
            $.weeboxs.open(data, {contentType:'none',showButton:false,title:LANG['ADD'],width:700,height:420});
            }
        );
}

function weeboxs_edit(id){

    $.get(ROOT+'?m=CouponLog&a=edit&id='+id,
    function(data){
        if(data.indexOf('{"status":0')>-1&&data.indexOf("info")>-1&&data.indexOf("data")>-1)
        {
            var jsonobj=eval('('+data+')');
            data = jsonobj.info;}
        $.weeboxs.open(data, {contentType:'none',showButton:false,title:LANG['EDIT'],width:700,height:420});
        }
    );

}
/**
 * 删除
 */
function coupon_log_del(id){
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert(LANG['DELETE_EMPTY_WARNING']);
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    if(confirm(LANG['CONFIRM_DELETE']))
    $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=delete&id="+id,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){

                if(obj.status==1){
                    location.href=location.href;
                }else{
                    alert(obj.info);
                }
            }
    });
}
function operation_passed(id, el){
    var ele = $(el);
    ele.css("background-color", '#ccc').attr("disabled", "disabled");
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert("请选择需要运营通过的记录");
            ele.css("background-color", '#4e6a81').removeAttr("disabled");
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            //处理 优惠券id有的才 操作
            if($(n).val()) {
                 idArray.push($(n).val());
            }
        });
        id = idArray.join(",");
    }
    if(confirm("要运营通过所选择的记录吗？"))
    {
        location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=operation_passed&id="+id;
    } else {
        ele.css("background-color", '#4e6a81').removeAttr("disabled");
    }

}
function finance_audit(id, is_passed){
    //var ele = $(el);
    //ele.css("background-color", '#ccc').attr("disabled", "disabled");
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert("请选择需要财务操作的记录");
            ele.css("background-color", '#4e6a81').removeAttr("disabled");
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            //处理 优惠券id有的才 操作
            if($(n).val()) {
                 idArray.push($(n).val());
            }
        });
        id = idArray.join(",");
    }
    str = '';
    if (is_passed == 1){
        str = '财务通过';
    }else{
        str = '财务拒绝';
    }
    if(confirm("要"+str+"所选择的记录吗？"))
    {
        location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=finance_audit&id="+id+"&is_passed="+is_passed;
    }

}


function changeLevelSelect(){
    var url = "/m.php?m=CouponLevel&a=get_level_select";
    var current_coupon_level_id = '<?php echo ($_REQUEST["coupon_level_id"]); ?>';
    $.getJSON(url,{group_id:$("#group_id").val()},function(json){
        var coupon_level_id = $("#coupon_level_id");
        $("option",coupon_level_id).remove(); //清空原有的选项
        var option = "<option value=''>==请选择==</option>";
        coupon_level_id.append(option);
        $.each(json,function(index,array){
            var selected_str = '';
            if(array['id'] == current_coupon_level_id){
                selected_str = 'selected="selected"';
            }
            option = "<option value='"+array['id']+"' "+selected_str+">"+array['level']+"</option>";
            coupon_level_id.append(option);
        });
    });
}

changeLevelSelect();
$("#group_id").change(function(){
    $("#group_factor_text").html($(this).find("option:selected").attr("factor"));
    changeLevelSelect();
});

// csv导出
function export_csv_file()
{
    var confirm_msg = "\n\r大数据量请增加筛选条件缩小结果集条数，以免导出失败";
    confirm_msg = "确认要导出csv文件数据吗？" + confirm_msg + "\n\r导出过程中请耐心等待，不要关闭页面。";
    if (!confirm(confirm_msg)) {
        return;
    }
    return export_csv();

}

function pay_list(deal_load_id) {
    $.weeboxs.open(ROOT+'?m=CouponPayLog&a=index&deal_load_id='+deal_load_id, {contentType:'ajax',showButton:false,title:'返利明细',width:900,height:600});
}
</script>
<!--logId:<?php echo \libs\utils\Logger::getLogId(); ?>-->

<script>
jQuery.browser={};
(function(){
    jQuery.browser.msie=false;
    jQuery.browser.version=0;
    if(navigator.userAgent.match(/MSIE ([0-9]+)./)){
        jQuery.browser.msie=true;
        jQuery.browser.version=RegExp.$1;}
})();
</script>

</body>
</html>