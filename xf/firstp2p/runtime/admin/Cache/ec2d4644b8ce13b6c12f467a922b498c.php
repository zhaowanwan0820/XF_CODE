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
<div class="main_title"><?php echo ($vo["name"]); ?><?php echo L("EDIT");?> <a href="<?php echo u("Role/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form name="edit" action="__APP__" method="post" enctype="multipart/form-data">
<table class="form" cellpadding=0 cellspacing=0>
	<tr>
		<td colspan=2 class="topTd"></td>
	</tr>
	<tr>
		<td class="item_title"><?php echo L("NAME");?>:</td>
		<td class="item_input"><input type="text" class="textbox require" name="name" value="<?php echo ($vo["name"]); ?>" /></td>
	</tr>
	<tr>
		<td class="item_title"><?php echo L("IS_EFFECT");?>:</td>
		<td class="item_input">
			<lable><?php echo L("IS_EFFECT_1");?><input type="radio" name="is_effect" value="1" <?php if($vo['is_effect'] == 1 ): ?>checked="checked"<?php endif; ?> /></lable>
			<lable><?php echo L("IS_EFFECT_0");?><input type="radio" name="is_effect" value="0" <?php if($vo['is_effect'] == 0 ): ?>checked="checked"<?php endif; ?> /></lable>
		</td>
	</tr>
	<tr>
		<td class="item_title"><?php echo L("ROLE_ACCESS");?>:</td>
		<td class="item_input">
			<table class="access_list" cellpadding=0 cellspacing=0>
			<?php if(is_array($access_list)): foreach($access_list as $key=>$access_item): ?><tr>
					<td class="access_left" style="width:180px;">
					<span title="<?php echo ($access_item["module"]); ?>"><?php echo ($access_item["name"]); ?></span>&nbsp;&nbsp;<?php echo L("CHECK_ALL");?><input type="checkbox" value="<?php echo ($access_item["id"]); ?>_0" name="role_access[]" class="check_all" onclick="check_module(this);" <?php if($access_item['module_auth'] == 1 or $access_item['check_all'] == 1): ?>checked="checked"<?php endif; ?> />
					
					</td>
					<td>

						<?php if(is_array($access_item["node_list"])): foreach($access_item["node_list"] as $key=>$node_item): ?><label style="padding:5px;"><span title="<?php echo ($node_item["action"]); ?>"><?php echo ($node_item["name"]); ?></span><input type="checkbox" value="<?php echo ($access_item["id"]); ?>_<?php echo ($node_item["id"]); ?>" name="role_access[]" class="node_item" onclick="check_is_all(this);" <?php if($node_item['node_auth'] == 1): ?>checked="checked"<?php endif; ?> <?php if($access_item['module_auth'] == 1): ?>checked<?php endif; ?> /></label><?php endforeach; endif; ?>

					</td>
				</tr><?php endforeach; endif; ?>
			</table>
		</td>
	</tr>
	<tr>
		<td class="item_title"></td>
		<td class="item_input">
			<!--隐藏元素-->
			<input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>" />
			<input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="Role" />
			<input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="update" />
			<!--隐藏元素-->
			<input type="submit" class="button" value="<?php echo L("EDIT");?>" />
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