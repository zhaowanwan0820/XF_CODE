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
<script type="text/javascript" src="__TMPL__Common/js/carry.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />

<script>
function import_rdm(id) {
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=import_rdm&id="+id,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){
            if(obj.status==1) {
                alert("操作成功！");
            } else {
                alert("操作失败！");
            }
            return true;
        }
    });
}
function down_csv()
{
    var id = '';

    $(".key").each(function(){
        if($(this).attr("checked") == true)
        	id += $(this).val() + ",";
    });

    var url= ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=getYbCsv&id=" + id;
    location.href = url;
}
function cfm($type,btn){
	$(btn).css("color","grey").attr("disabled", "disabled");
	if($type == 'del')
	    str = "确认拒绝？";
	else
		str = "确认批准？";

    if(confirm(str)){
		window.location.href = $(btn).attr('data-href');
	}else{
		$(btn).css("color","#4e6a81").removeAttr("disabled");
	}
}
function wdel(id)
{
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
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=delwait&id="+id,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                $("#info").html(obj.info);
                if(obj.status==1)
                location.href=location.href;
            }
    });
}
</script>

<?php function get_carry_status($status, $id){
        $str = l("CARRY_STATUS_".$status);
        return $str;
    }
    function get_opt($id, $status)
    {
        if($status == 0)
            return "<input class='ts-input' type='button' data-href='m.php?m=UserCarry&a=waitPass&id=$id&status=1' onClick=\"return cfm('',this);\" value='批准'/> <input class='ts-input' type='button' data-href='m.php?m=UserCarry&a=waitPass&id=$id&status=2' onClick=\"return cfm('del',this);\" value='拒绝'/>";
        else
            return '';
    }

    function get_print_user($user){
     //  if($user['status'] == 0)
            return "<a target='_blank' href='m.php?m=UserCarry&a=print_user&create_time=".$user['create_time']."&user_id=".$user['user_id']."&id=".$user['id']."' >打印</a>";
      //  else
       //     return '';
    }
    //读取用户姓名
    function get_user_real_name($user_id){
	    $user_name =  M("User")->where("id=".$user_id." and is_delete = 0")->getField("real_name");
	    if(!$user_name)
	        return l("NO_USER");
	    else
	        return $user_name;
	} ?>

<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="button_row">
	<input type="button" class="button" value="<?php echo L("DEL");?>" onclick="wdel();" />
	<input type="button" class="button" value="导出" onclick="export_csv();" />
</div>


<div class="blank5"></div>
<div class="search_row">
	<form name="search" action="__APP__" method="get">
		<?php echo L("USER_NAME");?>：<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" style="width:100px;" />
		用户姓名：<input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" style="width:100px;" />
		状态：<select name="status">
			<option value=""><?php echo L("ALL");?></option>
			<option value="0" <?php if($_REQUEST['status']!='' && intval($_REQUEST['status']) == 0): ?>selected="selected"<?php endif; ?>><?php echo L("CARRY_STATUS_0");?></option>
			<option value="1" <?php if(intval($_REQUEST['status']) == 1): ?>selected="selected"<?php endif; ?>><?php echo L("CARRY_STATUS_1");?></option>
			<option value="2" <?php if(intval($_REQUEST['status']) == 2): ?>selected="selected"<?php endif; ?>><?php echo L("CARRY_STATUS_2");?></option>
		</select>

		<input type="hidden" value="UserCarry" name="m" />
		<input type="hidden" value="waitList" name="a" />
		<input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
	</form>
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
	<tr><td colspan="11" class="topTd" >&nbsp; </td></tr>
	<tr class="row" >
		<th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th>
		<th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th>
		<th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照用户ID<?php echo ($sortType); ?> ">用户ID<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照<?php echo L("USER_NAME");?><?php echo ($sortType); ?> "><?php echo L("USER_NAME");?><?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th>
		<th>用户姓名</th>
		<th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照提现金额<?php echo ($sortType); ?> ">提现金额<?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('fee','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照手续费<?php echo ($sortType); ?> ">手续费<?php if(($order)  ==  "fee"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th>
		<th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照申请时间<?php echo ($sortType); ?> ">申请时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th>
		<th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照审核状态<?php echo ($sortType); ?> ">审核状态<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th>
		<th><a href="javascript:sortBy('desc','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照备注<?php echo ($sortType); ?> ">备注<?php if(($order)  ==  "desc"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th>
		<th><a href="javascript:sortBy('update_time','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照处理时间<?php echo ($sortType); ?> ">处理时间<?php if(($order)  ==  "update_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th>
		<th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','UserCarry','waitList')" title="按照操作<?php echo ($sortType); ?> ">操作<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th>
	</tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?>
	<tr class="row" >
		<td><input type="checkbox" name="key" class="key" value="<?php echo ($user["id"]); ?>"></td>
		<td>&nbsp;<?php echo ($user["id"]); ?></td><td>&nbsp;<?php echo ($user["user_id"]); ?></td>
		<td>&nbsp;<?php echo (get_user_name($user["user_id"])); ?></td>
		<td>&nbsp;<?php echo (get_user_real_name($user["user_id"])); ?></td>
		<td>&nbsp;<?php echo (format_price($user["money"])); ?></td>
		<td>&nbsp;<?php echo (format_price($user["fee"])); ?></td>
		<td>&nbsp;<?php echo (to_date($user["create_time"])); ?></td>
		<td>&nbsp;<?php echo (get_carry_status($user["status"],$user['id'])); ?></td>
		<td>&nbsp;<?php echo ($user["desc"]); ?></td>
		<td>&nbsp;<?php echo (to_date($user["update_time"])); ?></td>
		<td>&nbsp;<?php echo (get_opt($user["id"],$user['status'])); ?>&nbsp;&nbsp;<?php echo (get_print_user($user)); ?>&nbsp;<a href="javascript:import_rdm('<?php echo ($user["id"]); ?>')">导入RDM</a>&nbsp;</td>
	</tr><?php endforeach; endif; else: echo "" ;endif; ?>
	<tr>
		<td colspan="11" class="bottomTd">&nbsp; </td>
	</tr>
</table>
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