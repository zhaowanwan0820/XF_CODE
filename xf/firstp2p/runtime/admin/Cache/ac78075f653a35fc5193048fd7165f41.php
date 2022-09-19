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
<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<style>
    table .warn-cell {color:#F00;}
</style>
<div class="main">
<div class="main_title">网贷P2P账户充值列表</div>
<div class="blank5"></div>

<script>
function multi_redo() {
    idBox = $(".key:checked");

    var param = '';
    if(idBox.length == 0){
        idBox = $(".key");
    }

    idArray = new Array();
    $.each( idBox, function(i, n){
        idArray.push($(n).val());
    });

    if(idArray.length == 0){
        alert('无可导出的数据！');
        return false;
    }

    id = idArray.join(",");

/*
    var inputs = $(".search_row").find("input");

    for(i=0; i<inputs.length; i++){
        if(inputs[i].name != 'm' && inputs[i].name != 'a')
        param += "&"+inputs[i].name+"="+$(inputs[i]).val();
    }
*/

    var url= ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=multi_redo&id="+id;
    window.location.href = url;
}
</script>

<?php function f_to_date($stamp) {
    if (empty($stamp)) {
        return '';
    }
    return date('Y-m-d H:i:s', $stamp);
}
function f_cutstr($string) {
    $subString = $string;
    if (mb_strlen($string) > 15) {
        $subString = '<a href="javascript:;" title="'.str_replace('"',"'", $string).'">' . mb_substr($string, 0, 15) . '...</a>';
    }
    return $subString;
}
function f_show_amount($amount) {
    return format_price(bcdiv($amount, 100, 2));
}
function f_show_charge_status($status) {
    if ($status == 0) {
        return '处理中';
    } else if ($status == 1) {
        return '支付成功';
    } else if ($status == 2) {
        return '支付失败';
    }
}
function f_show_op($id) {
    return '';
}
function f_status($status) {
    return $GLOBALS['statusCn'][$status];
}
function f_get_username($userId) {
    return DI('User')->where(" id = '$userId' ")->getField('user_name');
}
function f_show_pay_status($status) {
    if ($status == 0) {
        return '处理中';
    }
    else if ($status == 1) {
        return '支付成功';
    }
    else if ($status == 2) {
        return '支付失败';
    }

} ?>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        <!-- <?php echo L("ORDER_SN");?>：<input type="text" class="textbox search_export" name="order_sn" value="<?php echo trim($_REQUEST['order_sn']);?>" />
        <?php echo L("PAYMENT_NOTICE_SN");?>：<input type="text" class="textbox search_export" name="notice_sn" value="<?php echo trim($_REQUEST['notice_sn']);?>" /> -->
        充值<?php echo L("ORDER_SN");?>：<input type="text" class="textbox search_export" name="out_order_id" value="<?php echo trim($_REQUEST['out_order_id']);?>" />
        支付时间：<input type="text" class="textbox search_export" id="pay_time_start" name="pay_time_start" value="<?php echo trim($_REQUEST['pay_time_start']);?>" style="width:150px;" onfocus="return showCalendar('pay_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('pay_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
        <input type="text" class="textbox search_export" name="pay_time_end" id="pay_time_end" value="<?php echo trim($_REQUEST['pay_time_end']);?>" style="width:150px;" onfocus="return showCalendar('pay_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('pay_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" /><br />
        <?php echo L("USER_NAME");?>：<input type="text" class="textbox search_export" name="user_name" value="<?php echo ($user_name); ?>" />
               会员编号：<input type="text" class="textbox search_export" name="user_num" value="<?php echo trim($_REQUEST['user_num']);?>" />
        <input type="hidden" value="SupervisionCharge" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="导出" onclick='javascript:export_contract()'/>
    </form>
</div>
<div class="blank5"></div>
<?php
// 充值来源
foreach ($list as $key => $item) {
    $list[$key]['platform_name'] = !empty($charge_map[$item['platform']]) ? $charge_map[$item['platform']] : '未知来源';
}
?>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="10" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照编号<?php echo ($sortType); ?> ">编号<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('out_order_id','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照充值单号<?php echo ($sortType); ?> ">充值单号<?php if(($order)  ==  "out_order_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照创建时间<?php echo ($sortType); ?> ">创建时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('update_time','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照支付时间<?php echo ($sortType); ?> ">支付时间<?php if(($order)  ==  "update_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('pay_status','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照支付状态<?php echo ($sortType); ?> ">支付状态<?php if(($order)  ==  "pay_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照会员名称<?php echo ($sortType); ?> ">会员名称<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照会员编号<?php echo ($sortType); ?> ">会员编号<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('amount','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照充值金额<?php echo ($sortType); ?> ">充值金额<?php if(($order)  ==  "amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('platform_name','<?php echo ($sort); ?>','SupervisionCharge','index')" title="按照充值来源<?php echo ($sortType); ?> ">充值来源<?php if(($order)  ==  "platform_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($user["id"]); ?>"></td><td>&nbsp;<?php echo ($user["id"]); ?></td><td>&nbsp;<?php echo ($user["out_order_id"]); ?></td><td>&nbsp;<?php echo (f_to_date($user["create_time"])); ?></td><td>&nbsp;<?php echo (f_to_date($user["update_time"])); ?></td><td>&nbsp;<?php echo (f_show_pay_status($user["pay_status"])); ?></td><td>&nbsp;<?php echo (f_get_username($user["user_id"])); ?></td><td>&nbsp;<?php echo (numTo32($user["user_id"])); ?></td><td>&nbsp;<?php echo (f_show_amount($user["amount"],amount)); ?></td><td>&nbsp;<?php echo ($user["platform_name"]); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="10" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>

<div class="blank5"></div>
</div>
<script type="text/javascript" charset="utf-8">
    var status = '<?php echo ($status); ?>';
    var p = '<?php echo ($p); ?>';
    function view(id) {
        if (parseInt(p) > 0) {
            window.location.href = "/m.php?m=Jobs&a=view&status="+status+"&p="+p+"&id="+id;
            return ;
        }
        window.location.href = "/m.php?m=Jobs&a=view&status="+status+"&id="+id;
    }
    function redo(id) {
        window.location.href = "/m.php?m=Jobs&a=redo&id="+id;
    }
    function auditRefuse(id) {
        $.getJSON('/m.php?m=Supervision&a=doAudit', {id:id, audit_status:2}, function(data){
            if (data.status == 0) {
                alert('操作成功');
                window.location.reload();
            } else {
                alert(data.msg);
            }
        });
    }

    function auditPass(id) {
        $.getJSON('/m.php?m=Supervision&a=doAudit', {id:id, audit_status:1}, function(data){
            if (data.status == 0) {
                alert('操作成功');
                window.location.reload();
            } else {
                alert(data.msg);
            }
        });
    }
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