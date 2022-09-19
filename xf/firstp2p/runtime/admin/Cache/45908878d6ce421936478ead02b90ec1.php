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
<div class="main_title">编辑 <a href="<?php echo u("Conf/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
	<form method='post' id="form" name="form" action="__APP__">
		<table cellpadding="4" cellspacing="0" border="0" class="form">
			<tr>
				<td colspan="2" class="topTd"></td>
			</tr>
            <tr>
                <td class="item_title"><?php echo L("CONF_ID");?></td>
                <td class="item_input"><?php echo ($vo["id"]); ?></td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("CONF_SITE_ID");?></td>
                <td class="item_input">
                    <!--<select name="site_id">
                        <?php if(is_array($site_list)): foreach($site_list as $site_id=>$site_name): ?><option value="<?php echo ($site_id); ?>" <?php if($vo['site_id'] == $site_id): ?>selected="selected"<?php endif; ?>>
                            <?php echo ($site_name); ?>
                            </option><?php endforeach; endif; ?>
                    </select>-->
                    <?php echo l('CONF_SITE_'.$vo['site_id']);?>
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("CONF_TITLE");?></td>
                <td class="item_input">
                    <input type="text" class="textbox require" name="title" value="<?php echo ($vo["title"]); ?>"/>
                    &emsp;
                    <span class="tip_span">配置项文字说明</span>
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("CONF_NAME");?></td>
                <td class="item_input">
                    <input type="text" class="textbox require" name="name" value="<?php echo ($vo["name"]); ?>"/>
                    &emsp;
                    <span class="tip_span">KEY, 由大写字母,数字,下划线组成</span>
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("CONF_INPUT_TYPE");?></td>
                <td class="item_input">
                    <input type="hidden" name="site_id" value="<?php echo ($vo["site_id"]); ?>" />
                    <select id="input_type" name="input_type">
                        <?php if(is_array($input_type_list)): foreach($input_type_list as $k=>$v): ?><option value="<?php echo ($k); ?>" <?php if($vo['input_type'] == $k): ?>selected="selected"<?php endif; ?>>
                            <?php echo ($v); ?>
                            </option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr id="tr_value_scope">
                <td class="item_title"><?php echo L("CONF_VALUE_SCOPE");?></td>
                <td class="item_input">
                    <input type="text" class="textbox" name="value_scope" value="<?php echo ($vo["value_scope"]); ?>"/>
                    &emsp;
                    <span class="tip_span">下拉框选项的值的集合，如‘1,2,3’，‘0:关闭,1:打开,3:认证显示维护页’,注意使用英文逗号冒号</span>
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("CONF_VALUE");?></td>
                <td class="item_input">
                    <textarea type="text" style="width:500px;height:100px" class="textbox" name="value" /><?php echo ($vo["value"]); ?></textarea>
                    &emsp;
                    <span class="tip_span">VALUE</span>
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("CONF_TIP");?></td>
                <td class="item_input">
                    <input type="text" class="textbox" name="tip" value="<?php echo ($vo["tip"]); ?>"/>
                    &emsp;
                    <span class="tip_span">可空</span>
                </td>
            </tr>
            <input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>" />
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="Conf" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="update" />
			<tr>
				<td class="item_title"></td>
				<td class="item_input">
				<input type="submit" class="button" value="<?php echo L("EDIT");?>" />
				<input type="reset" class="button" value="<?php echo L("RESET");?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="bottomTd"></td>
			</tr>
		</table>
	</form>
</div>

<script type="text/javascript">

    $(document).ready(function () {
        $("#input_type").bind("change", function () {
            if($(this).val() == '1'){
                $("#tr_value_scope").show();
            } else {
                $("#tr_value_scope").hide();
            }
            });
        $("#input_type").change();

    });

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