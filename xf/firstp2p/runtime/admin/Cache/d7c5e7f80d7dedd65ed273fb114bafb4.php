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
<div class="main_title">放款提现列表</div>
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

function f_get_realname($userId) {
    return DI('User')->where(" id = '$userId' ")->getField('real_name');
}

function showLoanMoneyTypeName($loan_money_type_name)
{
    return $loan_money_type_name == '非实际放款' ? '放款' : ($loan_money_type_name == '实际放款' ? '放款提现' : $loan_money_type_name);
}

function get_action_list($canRedoWithdraw, $row)
{
    $links = '<a href="javascript:modify_carry_new('. $row['id'] .',1)">查看</a>';
    if ($canRedoWithdraw) {
        $links.= ' <a href="javascript:redo_withdraw('.$row['id'].')">重新提现</a>';
    }
    return $links;
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
            <option value="update_time" <?php if($_REQUEST['timeType'] == 'update_time'): ?>selected="selected"<?php endif; ?>><?php if($is_cn == 1): ?>银行<?php else: ?>支付<?php endif; ?>处理时间</option>
            <option value="create_time" <?php if($_REQUEST['timeType'] == 'create_time'): ?>selected="selected"<?php endif; ?>>申请时间</option>
        </select>
        时间：<input type="text" class="textbox search_export" id="withdraw_time_start" name="withdraw_time_start" value="<?php echo trim($_REQUEST['withdraw_time_start']);?>" style="width:150px;" onfocus="return showCalendar('withdraw_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('withdraw_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
        <input type="text" class="textbox search_export" name="withdraw_time_end" id="withdraw_time_end" value="<?php echo trim($_REQUEST['withdraw_time_end']);?>" style="width:150px;" onfocus="return showCalendar('withdraw_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('withdraw_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" /><br />
        <?php echo L("USER_NAME");?>：<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" />
        会员编号：<input type="text" class="textbox search_export" name="user_num" value="<?php echo trim($_REQUEST['user_num']);?>" />
        <?php if($is_cn == 1): ?>银行<?php else: ?>支付<?php endif; ?>状态：<select id="withdraw_status" name="withdraw_status">
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($withdraw_status)): foreach($withdraw_status as $key=>$withdraw): ?><option value="<?php echo ($key); ?>" <?php if(isset($_REQUEST['withdraw_status']) and $_REQUEST['withdraw_status'] != '' and intval($_REQUEST['withdraw_status']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($withdraw); ?></option><?php endforeach; endif; ?>
        </select>

        借款标题：<input type="text" value="<?php echo ($_REQUEST['deal_name']); ?>" name="deal_name" />
        项目名称：<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" />
        产品类别：
        <select name="deal_type_id" id='deal_type_id' >
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($deal_type_tree)): foreach($deal_type_tree as $key=>$type_item): ?><option value="<?php echo ($type_item["id"]); ?>" <?php if($type_item['id'] == $_REQUEST['deal_type_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item["name"]); ?></option><?php endforeach; endif; ?>
        </select>
        放款方式：
        <select id="loanway" name="loanway">
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($loan_money_type)): foreach($loan_money_type as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if(isset($_REQUEST['loanway']) and $_REQUEST['loanway'] != '' and intval($_REQUEST['loanway']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        放款类型：
        <select id="loantype" name="loantype">
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($loantype)): foreach($loantype as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if(isset($_REQUEST['loantype']) and $_REQUEST['loantype'] != '' and intval($_REQUEST['loantype']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>

        <input type="hidden" value="SupervisionDealWithdraw" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="导出" onclick='javascript:export_csv()'/>
    </form>
</div>
<div class="blank5"></div>


<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="18" class="topTd" >&nbsp; </td></tr><tr class="row" ><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照编号<?php echo ($sortType); ?> ">编号<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('out_order_id','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照外部订单号<?php echo ($sortType); ?> ">外部订单号<?php if(($order)  ==  "out_order_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照用户Id<?php echo ($sortType); ?> ">用户Id<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照会员名称<?php echo ($sortType); ?> ">会员名称<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照会员编号<?php echo ($sortType); ?> ">会员编号<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照真实姓名<?php echo ($sortType); ?> ">真实姓名<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('bankcard_name','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照开户名<?php echo ($sortType); ?> ">开户名<?php if(($order)  ==  "bankcard_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('deal_name','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照借款标题<?php echo ($sortType); ?> ">借款标题<?php if(($order)  ==  "deal_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('old_deal_name','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照旧版借款标题<?php echo ($sortType); ?> ">旧版借款标题<?php if(($order)  ==  "old_deal_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('deal_loan_type','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照借款类别<?php echo ($sortType); ?> ">借款类别<?php if(($order)  ==  "deal_loan_type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('loan_money_type_name','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照放款方式<?php echo ($sortType); ?> ">放款方式<?php if(($order)  ==  "loan_money_type_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('loan_type','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照放款类型<?php echo ($sortType); ?> ">放款类型<?php if(($order)  ==  "loan_type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('amount','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照放款金额<?php echo ($sortType); ?> ">放款金额<?php if(($order)  ==  "amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('svBalanceFormat','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照会员余额<?php echo ($sortType); ?> ">会员余额<?php if(($order)  ==  "svBalanceFormat"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照申请时间<?php echo ($sortType); ?> ">申请时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('update_time_finance','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照财务处理时间<?php echo ($sortType); ?> ">财务处理时间<?php if(($order)  ==  "update_time_finance"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('withdraw_status','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照银行状态<?php echo ($sortType); ?> ">银行状态<?php if(($order)  ==  "withdraw_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('can_redo_withdraw','<?php echo ($sort); ?>','SupervisionDealWithdraw','index')" title="按照操作<?php echo ($sortType); ?> ">操作<?php if(($order)  ==  "can_redo_withdraw"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?><tr class="row" ><td>&nbsp;<?php echo ($user["id"]); ?></td><td>&nbsp;<?php echo ($user["out_order_id"]); ?></td><td>&nbsp;<?php echo ($user["user_id"]); ?></td><td>&nbsp;<?php echo (f_get_username($user["user_id"])); ?></td><td>&nbsp;<?php echo (numTo32($user["user_id"])); ?></td><td>&nbsp;<?php echo (f_get_realname($user["user_id"])); ?></td><td>&nbsp;<?php echo ($user["bankcard_name"]); ?></td><td>&nbsp;<?php echo ($user["deal_name"]); ?></td><td>&nbsp;<?php echo ($user["old_deal_name"]); ?></td><td>&nbsp;<?php echo ($user["deal_loan_type"]); ?></td><td>&nbsp;<?php echo (showLoanMoneyTypeName($user["loan_money_type_name"])); ?></td><td>&nbsp;<?php echo ($user["loan_type"]); ?></td><td>&nbsp;<?php echo (f_show_amount($user["amount"],amount)); ?></td><td>&nbsp;<?php echo ($user["svBalanceFormat"]); ?></td><td>&nbsp;<?php echo (f_to_date($user["create_time"])); ?></td><td>&nbsp;<?php echo (f_to_date($user["update_time_finance"])); ?></td><td>&nbsp;<?php echo (f_show_withdraw_status($user["withdraw_status"],$user['update_time'])); ?></td><td>&nbsp;<?php echo (get_action_list($user["can_redo_withdraw"],$user)); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="18" class="bottomTd"> &nbsp;</td></tr></table>
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
        querystring += "&deal_name="+$("input[name='deal_name']").val();
        querystring += "&deal_type_id="+$("#deal_type_id").val();
        querystring += "&withdraw_status="+$("#withdraw_status").val();
        querystring += "&loanway="+$("#loanway").val();
        querystring += "&loantype="+$("#loantype").val();
        querystring += "&backup="+$("#backup").val();
        querystring += "&project_name="+$("input[name='project_name']").val();
        return querystring;
    }

    // 重新提现
    function redo_withdraw(id) {
        if (!window.confirm('确认把编号为 ' + id + ' 的提现重新提交申请 吗？')) {
            return false;
        }
        window.location.href = ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=redoWithdraw&id=" + id;
    }

    function modify_carry_new(id, view) {
        querystring = "&isView="+view+"&id="+id+get_query_string('search_id');
        $.weeboxs.open(ROOT+'?m=SupervisionDealWithdraw&a=edit'+querystring, {contentType:'ajax',showButton:false,title:"提现申请处理",width:600,height:400});
    }

    /**
     * 导出
     */
    function export_csv() {
        window.location.href = ROOT+'?m=SupervisionDealWithdraw&a=get_carry_cvs'+get_query_string();
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