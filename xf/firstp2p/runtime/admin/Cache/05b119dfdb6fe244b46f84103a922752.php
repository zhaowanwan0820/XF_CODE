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
<script type="text/javascript" src="__TMPL__Common/js/userfreezemoney.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<?php function get_type_name($id){
    return $GLOBALS['dict']['MONEY_APPLY_TYPE'][$id];
}
function get_status_name($status){
    $status_list = array(
            '0'=>'待审核',
            '1'=>'审核通过',
            '2'=>'审核未通过',
            );
    return $status_list[$status].'<br/>';
}

function todate($timestamp)
{
    return date('Y-m-d H:i:s', $timestamp);
}



function get_oplist($id)
{
    $data = M("UserFreezeMoney")->where("id=".$id)->find();
    if ($data['status'] != 0)
    {
        return '';
    }
    else
    {
        return '<a href="javascript:doverify('.$id.');">批准</a> &nbsp; <a href="javascript:noverify('.$id.');">拒绝</a>';
    }
}

function get_real_name($user_id){
    return get_user_name($user_id, 'real_name');
}

function get_mobile($user_id){
    return get_user_name($user_id, 'mobile');
} ?>
<div class="main">
<div class="main_title">冻结/解冻申请列表</div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
    <input type="button" class="button" value="导入批量申请" onclick="importCsv();" />
    <input type="button" class="button" value="批准" onclick="confirm('确定要批准?') && multiProcess('pass');" />
    <input type="button" class="button" value="拒绝" onclick="confirm('确定要拒绝?') && multiProcess('refuse');" />
</div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
    申请人：<input type="text" class="textbox" id="apply_admin_name" name="apply_admin_name" value="<?php echo trim($_REQUEST['apply_admin_name']);?>"/>
    会员名称：<input type="text" class="textbox" id="username" name="username" value="<?php echo trim($_REQUEST['username']);?>"/>
    状态：<select name="status" >
       <option value="-1" <?php if($_REQUEST['status'] == -1): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($statusCn)): foreach($statusCn as $key=>$item): ?><option value="<?php echo ($item["status"]); ?>" <?php if($_REQUEST['status'] == $item['status']): ?>selected="selected"<?php endif; ?>><?php echo ($item["statusCn"]); ?></option><?php endforeach; endif; ?>

    </select>
    <br />
    申请时间：<input type="text" class="textbox" id="apply_start" name="apply_start" value="<?php echo trim($_REQUEST['apply_start']);?>" onfocus="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
                  <input type="text" class="textbox" name="apply_end" id="apply_end" value="<?php echo trim($_REQUEST['apply_end']);?>"  onfocus="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
        <input type="hidden" value="UserFreezeMoney" name="m" />
        <input type="hidden" value="index" name="a" />

        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    <input type="button" class="button" value="导出" onclick="export_csv();" />
    </form>
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="10" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th><a href="javascript:sortBy('apply_admin_name','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照申请人<?php echo ($sortType); ?> ">申请人<?php if(($order)  ==  "apply_admin_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照金额<?php echo ($sortType); ?> ">金额<?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照会员名称<?php echo ($sortType); ?> ">会员名称<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照姓名<?php echo ($sortType); ?> ">姓名<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照状态<?php echo ($sortType); ?> ">状态<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('memo','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照审批记录<?php echo ($sortType); ?> ">审批记录<?php if(($order)  ==  "memo"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照申请时间<?php echo ($sortType); ?> ">申请时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('note','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照备注<?php echo ($sortType); ?> ">备注<?php if(($order)  ==  "note"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','UserFreezeMoney','index')" title="按照操作<?php echo ($sortType); ?> ">操作<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$userfreezemoney): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($userfreezemoney["id"]); ?>"></td><td>&nbsp;<?php echo ($userfreezemoney["apply_admin_name"]); ?></td><td>&nbsp;<?php echo (format_price($userfreezemoney["money"])); ?></td><td>&nbsp;<?php echo (get_user_name($userfreezemoney["user_id"])); ?></td><td>&nbsp;<?php echo (get_real_name($userfreezemoney["user_id"])); ?></td><td>&nbsp;<?php echo (get_status_name($userfreezemoney["status"])); ?></td><td>&nbsp;<?php echo ($userfreezemoney["memo"]); ?></td><td>&nbsp;<?php echo (todate($userfreezemoney["create_time"])); ?></td><td>&nbsp;<?php echo ($userfreezemoney["note"]); ?></td><td>&nbsp;<?php echo (get_oplist($userfreezemoney["id"])); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="10" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->

<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>
function add(){
    location.href = ROOT+"?"+VAR_MODULE+"=UserFreezeMoney&"+VAR_ACTION+"=add";
}

//批量处理
function multiProcess(type) {
    idBox = $(".key:checked");
    if (idBox.length === 0) {
        alert('没有选择任何记录');
        return;
    }
    idArray = new Array();
    $.each(idBox, function (i, n) {
        idArray.push($(n).val());
    });
    ids = idArray.join(',');

    //通过
    if (type === 'pass') {
        location.href =  '?m=UserFreezeMoney&a=doverify&type=1&id=' + ids;
        return;
    }

    //拒绝
    if (type === 'refuse') {
        location.href =  '?m=UserFreezeMoney&a=doverify&type=2&id=' + ids;
        return;
    }
}
function get_query_string() {
        var id_str = arguments[0] || 'id';
        querystring = '';
        querystring += "&apply_start="+$("input[name='apply_start']").val();
        querystring += "&apply_end="+$("input[name='apply_end']").val();
        querystring += "&status="+$("select[name='status']").val();
        querystring += "&apply_admin_name="+$("input[name='apply_admin_name']").val();
        querystring += "&username="+$("input[name='username']").val();
        return querystring;
}

function importCsv(){
    location.href = ROOT+"?"+VAR_MODULE+"=UserFreezeMoney&"+VAR_ACTION+"=import";
}

function export_csv() {
    window.location.href = ROOT+'?m=UserFreezeMoney&a=export_csv'+get_query_string();
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