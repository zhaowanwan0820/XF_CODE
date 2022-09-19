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
<script>

function agree_contract(id, agency_uid) {
	if (!id) {
		alert('操作有误');
		return false;
	}
	$.ajax({ 
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=agree&id="+id+"&agency_uid="+agency_uid, 
        data: "ajax=1",
        dataType: "json",
        success: function(obj){
            if(obj.status==1){
            	location.href=location.href;
            } else {
            	alert(obj.info);
            	return false;
            }
        }
    });
}

function flush() {
	var con = confirm("确认清除缓存？");
	if (con==false) {
		return false;
	} else {
		$.ajax({
			url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=flush", 
			data: "ajax=1",
			dataType: "json",
			success: function(obj) {
				if(obj.status==1){
	            	location.href=location.href;
	            } else {
	            	alert(obj.info);
	            	return false;
	            }
	        }
		});
	}
}
</script>
<div class="main">
	<div class="main_title">数据字典管理    

	</div>
	<div class="blank5"></div>
	<div class="button_row">
    <input type="button" class="button" value="<?php echo L("FOREVERDEL");?>" onclick="foreverdel();" />
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
    <input type="button" class="button" value="清除缓存" onclick="flush();">
    </div>
    <div class="blank5"></div>
    <div class="blank5"></div>
	<!-- Think 系统列表组件开始 -->
	<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
		<tr>

			<td colspan="14" class="topTd">&nbsp;</td>
		</tr>
		<tr class="row">
			<th width="8"><input type="checkbox" id="check"
				onclick="CheckAll('dataTable')"></th>
			<th width="50px">编号</th>
			<th width="150px">字典键</th>
			<th width="250px">描述</th>
			<th width="250px">操作</th>
		</tr>
		<?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
			<td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td>
			<td>&nbsp;<?php echo ($item["id"]); ?></td>
			<td>&nbsp;<a href='/m.php?m=Dictionary&a=edit&id=<?php echo ($item["id"]); ?>'><?php echo ($item["key"]); ?></a></td>
			<td>&nbsp;<?php echo ($item["note"]); ?></td>
			<td><a href="/m.php?m=Dictionary&a=edit&id=<?php echo ($item["id"]); ?>">修改</a> &nbsp;
			<a href="/m.php?m=Dictionary&a=foreverdelete&id=<?php echo ($item["id"]); ?>&ajax=0" onclick='return confirm("确认彻底删除？");'>删除</a></td>
		</tr><?php endforeach; endif; ?>
		<tr>
			<td colspan="5" class="bottomTd">&nbsp;</td>
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