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
<div class="main_title">智多鑫邀请码结算</div>

<div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
        <table>
	        <tr>
	        <td>投资人检索：</td>
	        <td>会员id：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['consume_user_id']); ?>" name="consume_user_id"></td>
	        <td>姓名：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['consume_real_name']); ?>" name="consume_real_name"></td>
	        <td>手机号：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['mobile']); ?>" name="mobile"></td>
	        <td></td>
	        </tr>
	        <tr>
            <td>服务人检索：</td>
            <td>会员id：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['refer_user_id']); ?>" name="refer_user_id"></td>
            <td>会员名称：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['refer_real_name']); ?>" name="refer_real_name"></td>
            <td>机构会员名称：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['agency_user_name']); ?>" name="agency_user_name"></td>
            <td>服务人邀请码：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['short_alias']); ?>" name="short_alias"></td>
            </tr>
            <td>其他条件：</td>
            <td>投资id：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['deal_load_id']); ?>" name="deal_load_id"></td>
            <td>借款编号：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['deal_id']); ?>" name="deal_id"></td>
            <td>借款标题：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['deal_name']); ?>" name="deal_name"></td>
            <td>结算状态：
             <select name='pay_status'>
                <option value="0" <?php if(intval($_REQUEST['pay_status']) == 0): ?>selected="selected"<?php endif; ?>>运营待审核</option>
                <option value="3" <?php if(intval($_REQUEST['pay_status']) == 3): ?>selected="selected"<?php endif; ?>>财务待审核</option>
                <option value="5" <?php if(intval($_REQUEST['pay_status']) == 5): ?>selected="selected"<?php endif; ?>>结算中</option>
                <option value="2" <?php if(intval($_REQUEST['pay_status']) == 2): ?>selected="selected"<?php endif; ?>>已结算</option>
                <option value="" <?php if($_REQUEST['pay_status'] == ''): ?>selected="selected"<?php endif; ?>>全部</option>
            </select>
            </td>
            </tr>
            <td></td>
            <td>投资时间：<input type="text" class="textbox" style="width:140px;" name="create_time_begin" id="create_time_begin" value="<?php echo ($_REQUEST['create_time_begin']); ?>" onfocus="this.blur(); return showCalendar('create_time_begin', '%Y-%m-%d 00:00:00', false, false, 'btn_create_time_begin');" title="<?php echo L("COUPON_TIPS_LEVEL_REBATE_VALID_BEGIN");?>" />
            <input type="button" class="button" id="btn_create_time_begin" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('create_time_begin', '%Y-%m-%d %H:%M:00', false, false, 'btn_create_time_begin');" />
            </td>
            <td><input type="text" class="textbox" style="width:140px;" name="create_time_end" id="create_time_end" value="<?php echo ($_REQUEST['create_time_end']); ?>" onfocus="this.blur(); return showCalendar('create_time_end', '%Y-%m-%d 00:00:00', false, false, 'btn_create_time_end');" title="<?php echo L("COUPON_TIPS_LEVEL_REBATE_VALID_BEGIN");?>" />
            <input type="button" class="button" id="btn_create_time_end" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('create_time_end', '%Y-%m-%d %H:%M:00', false, false, 'btn_create_time_end');" /></td>
            <td>结算时间：<input type="text" class="textbox" style="width:140px;" name="pay_time_begin" id="pay_time_begin" value="<?php echo ($_REQUEST['pay_time_begin']); ?>" onfocus="this.blur(); return showCalendar('pay_time_begin', '%Y-%m-%d 00:00:00', false, false, 'btn_pay_time_begin');" title="<?php echo L("COUPON_TIPS_LEVEL_REBATE_VALID_BEGIN");?>" />
            <input type="button" class="button" id="btn_pay_time_begin" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('pay_time_begin', '%Y-%m-%d %H:%M:00', false, false, 'btn_pay_time_begin');" /></td>
            <td><input type="text" class="textbox" style="width:140px;" name="pay_time_end" id="pay_time_end" value="<?php echo ($_REQUEST['pay_time_end']); ?>" onfocus="this.blur(); return showCalendar('pay_time_end', '%Y-%m-%d 00:00:00', false, false, 'btn_pay_time_end');" title="<?php echo L("COUPON_TIPS_LEVEL_REBATE_VALID_BEGIN");?>" />
            <input type="button" class="button" id="btn_pay_time_end" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('pay_time_end', '%Y-%m-%d %H:%M:00', false, false, 'btn_pay_time_end');" /></td>
            </tr>
            <tr><td></td><td cols="4"><input type="hidden" value="CouponLog" name="m" />
            <input type="hidden" value="duoTouList" name="a" />
             <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
             </td></tr>
        </table>
          </form>
    </div>


<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="21" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th><a href="javascript:sortBy('deal_load_id','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照投资记录ID         <?php echo ($sortType); ?> ">投资记录ID         <?php if(($order)  ==  "deal_load_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照成交时间         <?php echo ($sortType); ?> ">成交时间         <?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('consume_user_id','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照投资人ID         <?php echo ($sortType); ?> ">投资人ID         <?php if(($order)  ==  "consume_user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('consume_user_name','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照投资人会员名称         <?php echo ($sortType); ?> ">投资人会员名称         <?php if(($order)  ==  "consume_user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('consume_real_name','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照投资人姓名         <?php echo ($sortType); ?> ">投资人姓名         <?php if(($order)  ==  "consume_real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('deal_id','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照标ID         <?php echo ($sortType); ?> ">标ID         <?php if(($order)  ==  "deal_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('deal_load_money','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照投资金额         <?php echo ($sortType); ?> ">投资金额         <?php if(($order)  ==  "deal_load_money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_real_name','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照服务人姓名         <?php echo ($sortType); ?> ">服务人姓名         <?php if(($order)  ==  "refer_real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_user_name','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照机构会员名称         <?php echo ($sortType); ?> ">机构会员名称         <?php if(($order)  ==  "agency_user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('short_alias','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照服务人邀请码         <?php echo ($sortType); ?> ">服务人邀请码         <?php if(($order)  ==  "short_alias"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('discount_ratio','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照客户系数         <?php echo ($sortType); ?> ">客户系数         <?php if(($order)  ==  "discount_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('tool_ratio','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照工具系数         <?php echo ($sortType); ?> ">工具系数         <?php if(($order)  ==  "tool_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('rebate_ratio_amount','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照投资人返点金额比例          <?php echo ($sortType); ?> ">投资人返点金额比例          <?php if(($order)  ==  "rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('referer_rebate_ratio','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照服务人返点比例         <?php echo ($sortType); ?> ">服务人返点比例         <?php if(($order)  ==  "referer_rebate_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('referer_rebate_ratio_amount','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照服务人返点比例金额          <?php echo ($sortType); ?> ">服务人返点比例金额          <?php if(($order)  ==  "referer_rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_rebate_ratio','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照机构返点比例         <?php echo ($sortType); ?> ">机构返点比例         <?php if(($order)  ==  "agency_rebate_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_rebate_ratio_amount','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照机构返点比例金额         <?php echo ($sortType); ?> ">机构返点比例金额         <?php if(($order)  ==  "agency_rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('pay_status','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照结算状态         <?php echo ($sortType); ?> ">结算状态         <?php if(($order)  ==  "pay_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('pay_time','<?php echo ($sort); ?>','CouponLog','duotoulist')" title="按照结算时间<?php echo ($sortType); ?> ">结算时间<?php if(($order)  ==  "pay_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:120px">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td><td>&nbsp;<?php echo ($item["deal_load_id"]); ?></td><td>&nbsp;<?php echo (to_date($item["create_time"])); ?></td><td>&nbsp;<?php echo ($item["consume_user_id"]); ?></td><td>&nbsp;<?php echo ($item["consume_user_name"]); ?></td><td>&nbsp;<?php echo ($item["consume_real_name"]); ?></td><td>&nbsp;<?php echo ($item["deal_id"]); ?></td><td>&nbsp;<?php echo ($item["deal_load_money"]); ?></td><td>&nbsp;<?php echo ($item["refer_real_name"]); ?></td><td>&nbsp;<?php echo ($item["agency_user_name"]); ?></td><td>&nbsp;<?php echo ($item["short_alias"]); ?></td><td>&nbsp;<?php echo ($item["discount_ratio"]); ?></td><td>&nbsp;<?php echo ($item["tool_ratio"]); ?></td><td>&nbsp;<?php echo ($item["rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo ($item["referer_rebate_ratio"]); ?></td><td>&nbsp;<?php echo ($item["referer_rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo ($item["agency_rebate_ratio"]); ?></td><td>&nbsp;<?php echo ($item["agency_rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo ($item["pay_status"]); ?></td><td>&nbsp;<?php echo (to_date($item["pay_time"])); ?></td><td> <?php echo ($item["opt_edit"]); ?>&nbsp; <?php echo ($item["opt_pay_list"]); ?>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="21" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>

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


<script type="text/javascript">
function weeboxs_edit(id){

    $.get(ROOT+'?m=CouponLog&a=edit&id='+id+'&type=duotou',
    function(data){
        if(data.indexOf('{"status":0')>-1&&data.indexOf("info")>-1&&data.indexOf("data")>-1)
        {
            var jsonobj=eval('('+data+')');
            data = jsonobj.info;}
        $.weeboxs.open(data, {contentType:'none',showButton:false,title:LANG['EDIT'],width:700,height:420});
        }
    );

}
function pay_list(deal_load_id) {

     $.weeboxs.open(ROOT+'?m=CouponPayLog&a=index&model=duotou&deal_load_id='+deal_load_id, {contentType:'ajax',showButton:false,title:'返利明细',width:900,height:600});

}
 </script>