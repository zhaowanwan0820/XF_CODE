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

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<?php function get_is_paid($status){
    if($status == 0){
       return '未支付';
    }else if ($status == 1){
       return '支付成功';
    } else if ($status == 2){
        return '待支付';
    } else if ($status == 3) {
        return '支付失败';
    }
}
function get_is_platform_fee_charged($status){
    if($status == 0){
       return l("NO");
    }else{
       return l("YES");
    }
}
function get_bank_by_orderid($orderid){
    return '';
    $order=M("DealOrder")->where("id=".$orderid)->field("order_sn,payment_id,bank_id")->find();
        if($order)
        {
            if($order['payment_id']==4)
            {
                $bank = M("bankCharge")->where("short_name='".$order["bank_id"]."'")->getField('name');
                return (empty($bank))?$order["bank_id"]:$bank;
            }
            else
            {
                $bank = M("bankCharge")->where("value like '".$order["bank_id"]."-%'")->getField('name');
                return (empty($bank))?$order["bank_id"]:$bank;
            }
        }
        else
        {
            return '未知';
        }
}
function get_charge_resource_name($platform, $payment_id)
{
    return \core\dao\PaymentNoticeModel::$chargeResourceNameConfig[$payment_id][$platform];
} ?>
<script>
function export_contract(){
    var inputs = $(".search_export");

    var param = '';
    for(i=0; i<inputs.length; i++){
        param += "&"+inputs[i].name+"="+$(inputs[i]).val();
    }

    var url= ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=export_payment"+param;
    window.location.href = url + param;
}
</script>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        <!-- <?php echo L("ORDER_SN");?>：<input type="text" class="textbox search_export" name="order_sn" value="<?php echo trim($_REQUEST['order_sn']);?>" />
        <?php echo L("PAYMENT_NOTICE_SN");?>：<input type="text" class="textbox search_export" name="notice_sn" value="<?php echo trim($_REQUEST['notice_sn']);?>" /> -->
        充值<?php echo L("ORDER_SN");?>：<input type="text" class="textbox search_export" name="notice_sn" value="<?php echo trim($_REQUEST['notice_sn']);?>" />
        支付时间：<input type="text" class="textbox search_export" id="pay_time_start" name="pay_time_start" value="<?php echo trim($_REQUEST['pay_time_start']);?>" style="width:150px;" onfocus="return showCalendar('pay_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('pay_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
        <input type="text" class="textbox search_export" name="pay_time_end" id="pay_time_end" value="<?php echo trim($_REQUEST['pay_time_end']);?>" style="width:150px;" onfocus="return showCalendar('pay_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('pay_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" /><br />
        <?php echo L("USER_NAME");?>：<input type="text" class="textbox" name="user_name" value="<?php echo ($user_name); ?>" />
               会员编号：<input type="text" class="textbox" name="user_num" value="<?php echo trim($_REQUEST['user_num']);?>" />
        <?php echo L("PAYMENT_METHOD");?>：
        <select name="payment_id" class='search_export'>
            <option value="0" <?php if(intval($_REQUEST['payment_id']) == 0): ?>selected="selected"<?php endif; ?>><?php echo L("ALL");?></option>
            <?php if(is_array($payment_list)): foreach($payment_list as $key=>$payment_item): ?><option value="<?php echo ($payment_item["id"]); ?>" <?php if(intval($_REQUEST['payment_id']) == $payment_item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($payment_item["name"]); ?></option><?php endforeach; endif; ?>
        </select>
        充值来源:
        <select name="charge_source_id" id="charge_source_id" class='search_export'>
                <option value="0" <?php if(intval($_REQUEST['charge_source_id']) == 0): ?>selected="selected"<?php endif; ?>>==全部==</option>
                <?php if(is_array($charge_resource_list)): foreach($charge_resource_list as $id=>$name): ?><option value="<?php echo ($id); ?>" <?php if(intval($_REQUEST['charge_source_id']) == $id): ?>selected="selected"<?php endif; ?>><?php echo ($name); ?></option><?php endforeach; endif; ?>
        </select>
        <input type="hidden" value="PaymentNotice" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="导出" onclick='javascript:export_contract()'/>
    </form>
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="16" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('notice_sn','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("PAYMENT_NOTICE_SN");?><?php echo ($sortType); ?> "><?php echo L("PAYMENT_NOTICE_SN");?><?php if(($order)  ==  "notice_sn"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("CREATE_TIME");?>     <?php echo ($sortType); ?> "><?php echo L("CREATE_TIME");?>     <?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('pay_time','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("PAY_TIME");?>     <?php echo ($sortType); ?> "><?php echo L("PAY_TIME");?>     <?php if(($order)  ==  "pay_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_paid','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照支付状态     <?php echo ($sortType); ?> ">支付状态     <?php if(($order)  ==  "is_paid"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('notice_sn','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("ORDER_SN");?>     <?php echo ($sortType); ?> "><?php echo L("ORDER_SN");?>     <?php if(($order)  ==  "notice_sn"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("USER_NAME");?>     <?php echo ($sortType); ?> "><?php echo L("USER_NAME");?>     <?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照会员编号     <?php echo ($sortType); ?> ">会员编号     <?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('payment_id','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("PAYMENT_METHOD");?>     <?php echo ($sortType); ?> "><?php echo L("PAYMENT_METHOD");?>     <?php if(($order)  ==  "payment_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('platform','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照充值来源     <?php echo ($sortType); ?> ">充值来源     <?php if(($order)  ==  "platform"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('order_id','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照银行卡     <?php echo ($sortType); ?> ">银行卡     <?php if(($order)  ==  "order_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("PAYMENT_MONEY");?>     <?php echo ($sortType); ?> "><?php echo L("PAYMENT_MONEY");?>     <?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('outer_notice_sn','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("OUTER_NOTICE_SN");?>     <?php echo ($sortType); ?> "><?php echo L("OUTER_NOTICE_SN");?>     <?php if(($order)  ==  "outer_notice_sn"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('memo','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照<?php echo L("PAYMENT_MEMO");?>     <?php echo ($sortType); ?> "><?php echo L("PAYMENT_MEMO");?>     <?php if(($order)  ==  "memo"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_platform_fee_charged','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照扣除平台账户手续费     <?php echo ($sortType); ?> ">扣除平台账户手续费     <?php if(($order)  ==  "is_platform_fee_charged"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('fee','<?php echo ($sort); ?>','PaymentNotice','index')" title="按照手续费<?php echo ($sortType); ?> ">手续费<?php if(($order)  ==  "fee"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$payment_notice): ++$i;$mod = ($i % 2 )?><tr class="row" ><td>&nbsp;<?php echo ($payment_notice["id"]); ?></td><td>&nbsp;<?php echo ($payment_notice["notice_sn"]); ?></td><td>&nbsp;<?php echo (to_date($payment_notice["create_time"])); ?></td><td>&nbsp;<?php echo (to_date($payment_notice["pay_time"])); ?></td><td>&nbsp;<?php echo (get_is_paid($payment_notice["is_paid"])); ?></td><td>&nbsp;<?php echo (get_order_sn_with_link($payment_notice["notice_sn"])); ?></td><td>&nbsp;<?php echo (get_user_name($payment_notice["user_id"])); ?></td><td>&nbsp;<?php echo (numTo32($payment_notice["user_id"])); ?></td><td>&nbsp;<?php echo (get_payment_name($payment_notice["payment_id"])); ?></td><td>&nbsp;<?php echo (get_charge_resource_name($payment_notice["platform"],$payment_notice['payment_id'])); ?></td><td>&nbsp;<?php echo (get_bank_by_orderid($payment_notice["order_id"])); ?></td><td>&nbsp;<?php echo (format_price($payment_notice["money"])); ?></td><td>&nbsp;<?php echo ($payment_notice["outer_notice_sn"]); ?></td><td>&nbsp;<?php echo ($payment_notice["memo"]); ?></td><td>&nbsp;<?php echo (get_is_platform_fee_charged($payment_notice["is_platform_fee_charged"])); ?></td><td>&nbsp;<?php echo (format_price($payment_notice["fee"])); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="16" class="bottomTd"> &nbsp;</td></tr></table>
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