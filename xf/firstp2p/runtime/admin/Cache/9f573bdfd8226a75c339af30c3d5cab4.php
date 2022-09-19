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
<div class="main_title">网贷P2P账户提现申请列表</div>
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
function f_show_op($id) {
    return '';
}
function f_status($status) {
    return $GLOBALS['statusCn'][$status];
}
function f_get_username($userId) {
    $user_name = DI('User')->where(" id = '$userId' ")->getField('user_name');
    return "<a href='/m.php?m=User&a=index&user_id=$userId' target='_blank'>$user_name</a>";
}
function f_show_withdraw_status($status, $update_time) {
    if ($status == 0) {
        return '未处理';
    }
    else if ($status == 1) {
        return '提现成功<br>'.format_date($update_time);
    }
    else if ($status == 2) {
        return '提现失败<br>'.format_date($update_time);
    }
    else if ($status == 3) {
        return '处理中';
    }
    else if ($status == '4') {
        return '自动处理队列';
    }
}
function f_get_realname($userId) {
    return DI('User')->where(" id = '$userId' ")->getField('real_name');
} ?>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        <!-- <?php echo L("ORDER_SN");?>：<input type="text" class="textbox search_export" name="order_sn" value="<?php echo trim($_REQUEST['order_sn']);?>" />
        <?php echo L("PAYMENT_NOTICE_SN");?>：<input type="text" class="textbox search_export" name="notice_sn" value="<?php echo trim($_REQUEST['notice_sn']);?>" /> -->
        <select name="backup" id="backup">
            <option value="0" <?php if(intval($_REQUEST['backup']) == 0): ?>selected="selected"<?php endif; ?>>近3个月</option>
            <option value="1" <?php if($_REQUEST['backup'] == 1): ?>selected="selected"<?php endif; ?>>3个月前</option>
        </select>

        提现单号：<input type="text" class="textbox search_export" name="out_order_id" value="<?php echo trim($_REQUEST['out_order_id']);?>" />
        筛选时间类型：
        <select name="timeType" id="timeType">
            <option value="update_time_finance" <?php if($_REQUEST['timeType'] == 'update_time_finance'): ?>selected="selected"<?php endif; ?>>财务处理时间</option>
            <option value="update_time" <?php if($_REQUEST['timeType'] == 'update_time'): ?>selected="selected"<?php endif; ?>>支付处理时间</option>
            <option value="create_time" <?php if($_REQUEST['timeType'] == 'create_time'): ?>selected="selected"<?php endif; ?>>申请时间</option>
        </select>
        时间：<input type="text" class="textbox search_export" id="withdraw_time_start" name="withdraw_time_start" value="<?php echo trim($_REQUEST['withdraw_time_start']);?>" style="width:150px;" onfocus="return showCalendar('withdraw_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('withdraw_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
        <input type="text" class="textbox search_export" name="withdraw_time_end" id="withdraw_time_end" value="<?php echo trim($_REQUEST['withdraw_time_end']);?>" style="width:150px;" onfocus="return showCalendar('withdraw_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('withdraw_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" /><br />
        <?php echo L("USER_NAME");?>：<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" />
        会员编号：<input type="text" class="textbox" name="user_num" value="<?php echo trim($_REQUEST['user_num']);?>" />
        支付状态：<select id="withdraw_status" name="withdraw_status">
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($withdraw_status)): foreach($withdraw_status as $key=>$withdraw): ?><option value="<?php echo ($key); ?>" <?php if(isset($_REQUEST['withdraw_status']) and $_REQUEST['withdraw_status'] != '' and intval($_REQUEST['withdraw_status']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($withdraw); ?></option><?php endforeach; endif; ?>
        </select>

        <input type="hidden" value="SupervisionWithdraw" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="导出" onclick='javascript:export_csv()'/>
    </form>
</div>
<div class="blank5"></div>

<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="12" class="topTd" >&nbsp; </td></tr><tr class="row" ><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照编号<?php echo ($sortType); ?> ">编号<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('out_order_id','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照外部订单号<?php echo ($sortType); ?> ">外部订单号<?php if(($order)  ==  "out_order_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照用户Id<?php echo ($sortType); ?> ">用户Id<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照会员名称<?php echo ($sortType); ?> ">会员名称<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照会员编号<?php echo ($sortType); ?> ">会员编号<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照用户姓名<?php echo ($sortType); ?> ">用户姓名<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('cardName','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照开户名<?php echo ($sortType); ?> ">开户名<?php if(($order)  ==  "cardName"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('amount','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照提现金额<?php echo ($sortType); ?> ">提现金额<?php if(($order)  ==  "amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照申请时间<?php echo ($sortType); ?> ">申请时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('update_time_finance','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照财务处理时间<?php echo ($sortType); ?> ">财务处理时间<?php if(($order)  ==  "update_time_finance"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('memo','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照备注<?php echo ($sortType); ?> ">备注<?php if(($order)  ==  "memo"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('withdraw_status','<?php echo ($sort); ?>','SupervisionWithdraw','index')" title="按照支付状态<?php echo ($sortType); ?> ">支付状态<?php if(($order)  ==  "withdraw_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?><tr class="row" ><td>&nbsp;<?php echo ($user["id"]); ?></td><td>&nbsp;<?php echo ($user["out_order_id"]); ?></td><td>&nbsp;<?php echo ($user["user_id"]); ?></td><td>&nbsp;<?php echo (f_get_username($user["user_id"])); ?></td><td>&nbsp;<?php echo (numTo32($user["user_id"])); ?></td><td>&nbsp;<?php echo (f_get_realname($user["user_id"])); ?></td><td>&nbsp;<?php echo ($user["cardName"]); ?></td><td>&nbsp;<?php echo (f_show_amount($user["amount"],amount)); ?></td><td>&nbsp;<?php echo (f_to_date($user["create_time"])); ?></td><td>&nbsp;<?php echo (f_to_date($user["update_time_finance"])); ?></td><td>&nbsp;<?php echo ($user["memo"]); ?></td><td>&nbsp;<?php echo (f_show_withdraw_status($user["withdraw_status"],$user['update_time'])); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="12" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>

<div class="blank5"></div>
</div>
<script type="text/javascript" charset="utf-8">
    function get_query_string() {
        querystring = '';
        querystring += '&out_order_id=' +$("input[name='out_order_id']").val();
        querystring += "&timeType="+$("#timeType").val();
        querystring += "&withdraw_time_start="+$("input[name='withdraw_time_start']").val();
        querystring += "&withdraw_time_end="+$("input[name='withdraw_time_end']").val();
        querystring += "&user_name="+$("input[name='user_name']").val();
        querystring += "&user_num="+$("input[name='user_num']").val();
        querystring += "&withdraw_status="+$("#withdraw_status").val();
        return querystring;
    }


    /**
     * 导出
     */
    function export_csv() {
        window.location.href = ROOT+'?m=SupervisionWithdraw&a=get_carry_cvs'+get_query_string();
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