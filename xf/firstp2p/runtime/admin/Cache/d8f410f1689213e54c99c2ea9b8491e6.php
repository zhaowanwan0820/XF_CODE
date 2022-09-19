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

<div class="main">
<div class="main_title"><?php echo L("ADD");?> <a href="<?php echo u("UserCarry/waitList");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form name="edit" action="__APP__" method="post" enctype="multipart/form-data">
<table class="form" cellpadding=0 cellspacing=0>
	<input type="hidden" name="user_id" value="<?php echo ($uid); ?>" />
	<tr>
		<td colspan=2 class="topTd"></td>
	</tr>
	<tr>
		<td class="item_title">用户名:</td>
		<td class="item_input"><?php echo ($userinfo["user_name"]); ?></td>
	</tr>
	<tr>
		<td class="item_title">用户余额:</td>
		<td class="item_input"><?php echo ($userinfo["money"]); ?></td>
	</tr>
	
	<tr>
		<td class="item_title">提现金额:</td>
		<td class="item_input"><input type="text" class="textbox require" name="money" value="0" /></td>
	</tr>
	<!--去掉会员列表-提现申请面板中的手续费编辑框 20140425-->
	<!--tr>
		<td class="item_title">手续费</td>
		<td class="item_input"><input type="text" class="textbox" name="fee" value="0" /></td>
	</tr-->
	<tr>
		<td class="item_title">备注</td>
		<td class="item_input"><textarea name="desc"></textarea></td>
	</tr>
	<!--
	<tr>
        <td class="item_title">审核状态</td>
        <td class="item_input">
	        <select name="status">
	            <option value="0" ><?php echo L("CARRY_STATUS_0");?></option>
	            <option value="1" selected="selected"><?php echo L("CARRY_STATUS_1");?></option>
	            <option value="2" ><?php echo L("CARRY_STATUS_2");?></option>
	        </select>
        </td>
    </tr>
	-->
	<tr>
		<td class="item_title"></td>
		<td class="item_input">
			<!--隐藏元素-->
			<input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="UserCarry" />
			<input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="insert" />
			<!--隐藏元素-->
			<input type="submit" class="button" value="<?php echo L("ADD");?>" />
			<input type="reset" class="button" value="<?php echo L("RESET");?>" />
		</td>
	</tr>
	<tr>
		<td colspan=2 class="bottomTd"></td>
	</tr>
</table>	 
</form>
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