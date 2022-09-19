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
<?php function get_type_name($id, $money_adjust_type){
    return $money_adjust_type[$id];
}
function get_status_name($status, $money_adjust_status){
    return $money_adjust_status[$status].'<br/>';
}
function get_username($user){
    $str = '<a target="_blank" href="/m.php?m=User&a=index&user_name='.$user.'">'.$user.'</a>';
    return $str;
}
function get_action_list($status,$row){
    if($status == 1){
        return '<a href="javascript:money_adjust_action(' . $row['id'] . ",'verify1'" . ');">运营批准</a>  <a href="javascript:money_adjust_action(' . $row['id'] . ",'refuse1'" . ');">拒绝</a>';
    }elseif($status == 2){
        return '<a href="javascript:money_adjust_action(' . $row['id'] . ",'verify2'" . ');">财务批准</a>  <a href="javascript:money_adjust_action(' . $row['id'] . ",'refuse2'" . ');">拒绝</a>';
    }elseif($status == 3){
        return "审核通过";
    }elseif($status == -1){
        return "审核未通过";
}

} ?>
<div class="main">
<div class="main_title">调账管理</div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="addm();" />
    <input type="button" class="button" value="批量导入" onclick="importCsv();" />
    <input type="button" class="button" value="批准" onclick="batch_edit('<?php echo ($auth_action["p"]); ?>',this);" />
    <input type="button" class="button" value="拒绝" onclick="batch_edit('<?php echo ($auth_action["r"]); ?>',this);" />
    <input type="button" class="button" value="<?php echo L("DEL");?>" onclick="del();" />
</div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
    申请时间：<input type="text" class="textbox" id="adjust_start" name="adjust_start" value="<?php echo trim($_REQUEST['adjust_start']);?>" onfocus="return showCalendar('adjust_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
        <input type="text" class="textbox" name="adjust_end" id="adjust_end" value="<?php echo trim($_REQUEST['adjust_end']);?>"  onfocus="return showCalendar('adjust_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
        申请人：<input type="text" class="textbox" name="apply_user" value="<?php echo trim($_REQUEST['apply_user']);?>" style="width:100px;" />
        调减账户会员名：<input type="text" class="textbox" name="decr_name" value="<?php echo trim($_REQUEST['decr_name']);?>" style="width:100px;" />
        调增账户会员名：<input type="text" class="textbox" name="incr_name" value="<?php echo trim($_REQUEST['incr_name']);?>" style="width:100px;" />
        批次号：<input type="text" class="textbox" name="batch_number" value="<?php echo trim($_REQUEST['batch_number']);?>" style="width:100px;" />
        类型:
        <select name="type" id="js_type">
            <option value="0" <?php if(intval($_REQUEST['type']) == 0 ): ?>selected="selected"<?php endif; ?>>全部</option>
            <?php if(is_array($money_adjust_type)): foreach($money_adjust_type as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if(intval($_REQUEST['type']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        状态:
        <select name="status">
            <option value="0" <?php if(intval($_REQUEST['status']) == 0 ): ?>selected="selected"<?php endif; ?>>全部</option>
            <?php if(is_array($money_adjust_status)): foreach($money_adjust_status as $key=>$status): ?><option value="<?php echo ($key); ?>" <?php if(intval($_REQUEST['status']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($status); ?></option><?php endforeach; endif; ?>
        </select>
        <input type="hidden" value="MoneyAdjust" name="m" />
        <input type="hidden" value="index" name="a" />

        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    <input type="button" class="button" value="导出" onclick="export_csv();" />
    </form>
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="14" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('batch_number','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照批次号<?php echo ($sortType); ?> ">批次号<?php if(($order)  ==  "batch_number"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="80px"><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照申请时间<?php echo ($sortType); ?> ">申请时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('apply_user','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照申请人<?php echo ($sortType); ?> ">申请人<?php if(($order)  ==  "apply_user"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('type','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照类型<?php echo ($sortType); ?> ">类型<?php if(($order)  ==  "type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照金额<?php echo ($sortType); ?> ">金额<?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('decr_name','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照调减账户会员名<?php echo ($sortType); ?> ">调减账户会员名<?php if(($order)  ==  "decr_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('decr_note','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照调减账户备注<?php echo ($sortType); ?> ">调减账户备注<?php if(($order)  ==  "decr_note"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('incr_name','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照调增账户会员名<?php echo ($sortType); ?> ">调增账户会员名<?php if(($order)  ==  "incr_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('incr_note','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照调增账户备注<?php echo ($sortType); ?> ">调增账户备注<?php if(($order)  ==  "incr_note"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照审核状态<?php echo ($sortType); ?> ">审核状态<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('log','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照审批记录<?php echo ($sortType); ?> ">审批记录<?php if(($order)  ==  "log"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="110px"><a href="javascript:sortBy('status','<?php echo ($sort); ?>','MoneyAdjust','index')" title="按照操作<?php echo ($sortType); ?> ">操作<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$link): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($link["id"]); ?>"></td><td>&nbsp;<?php echo ($link["id"]); ?></td><td>&nbsp;<?php echo ($link["batch_number"]); ?></td><td>&nbsp;<?php echo (to_date($link["create_time"])); ?></td><td>&nbsp;<?php echo ($link["apply_user"]); ?></td><td>&nbsp;<?php echo (get_type_name($link["type"],$money_adjust_type)); ?></td><td>&nbsp;<?php echo (format_price($link["money"])); ?></td><td>&nbsp;<?php echo (get_username($link["decr_name"])); ?></td><td>&nbsp;<?php echo ($link["decr_note"]); ?></td><td>&nbsp;<?php echo (get_username($link["incr_name"])); ?></td><td>&nbsp;<?php echo ($link["incr_note"]); ?></td><td>&nbsp;<?php echo (get_status_name($link["status"],$money_adjust_status)); ?></td><td>&nbsp;<?php echo (nl2br($link["log"])); ?></td><td>&nbsp;<?php echo (get_action_list($link["status"],$link)); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="14" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->

<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>
var bool = false;
function money_adjust_action(id, action, is_ajax) {
    if (is_ajax != 1) {
        if (!confirm("确认要进行操作？")) {
            return;
        }
    }
    if (!arguments[2]) is_ajax = 0;
    if (!bool) {
        bool = true;
        $.post("/m.php?m=MoneyAdjust&a=" + action, { id: id, ajax: is_ajax }, function(rs) {
            var rs = $.parseJSON(rs);
            if (rs.status) {
                alert(rs.data);
                window.location.reload();
            } else {
                alert("操作失败！" + rs.data + rs.info);
            }
        });
        bool = false;
    } else {
        alert("请不要重复点击");
        return false;
    }

}
function addm(){
    location.href = ROOT+"?"+VAR_MODULE+"=MoneyAdjust&"+VAR_ACTION+"=add";
}

//通过拒绝 批量操作
function batch_edit(action,btn) {
    $(btn).css({ "color": "grey",  "background-color":"#CCC" }).attr("disabled", "disabled");
    idBox = $(".key:checked");
    if(idBox.length == 0)
    {
        alert('请选择未处理的调账申请记录！');
        $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
        return;
    }
    idArray = new Array();
    $.each( idBox, function(i, n){
        idArray.push($(n).val());
    });
    id = idArray.join(",");
    str = '确认批量处理您选择的记录？';
    if(confirm(str)){
        money_adjust_action(id,action,1);
    }
    $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
}
function get_query_string() {
    idBox = $(".key:checked");
    idArray = new Array();
    $.each(idBox, function (i, n) {
        idArray.push($(n).val());
    });
    ids = idArray.join(',');
    querystring = '&id=' + ids;
    querystring += "&adjust_start="+$("input[name='adjust_start']").val();
    querystring += "&adjust_end="+$("input[name='adjust_end']").val();
    querystring += "&batch_number="+$("input[name='batch_number']").val();
    querystring += "&apply_user="+$("input[name='apply_user']").val();
    querystring += "&decr_name="+$("input[name='decr_name']").val();
    querystring += "&incr_name="+$("input[name='incr_name']").val();
    querystring += "&type="+$("select[name='type']").val();
    querystring += "&status="+$("select[name='status']").val();
    return querystring;
}

function importCsv(){
    location.href = ROOT+"?"+VAR_MODULE+"=MoneyAdjust&"+VAR_ACTION+"=import";
}

function export_csv() {
    window.location.href = ROOT+'?m=MoneyAdjust&a=export_csv'+get_query_string();
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