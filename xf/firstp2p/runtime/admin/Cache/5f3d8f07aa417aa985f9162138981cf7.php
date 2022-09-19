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
    <div class="main_title">编辑 <a href="<?php echo u("ApiConf/index",array('conf_type'=>$vo['conf_type'],'site_id'=>$vo['site_id']));?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
    <div class="blank5"></div>
    <form method='post' id="form" name="form" action="__APP__">
        <table cellpadding="4" cellspacing="0" border="0" class="form">
            <tr>
                <td colspan="2" class="topTd"></td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("API_CONF_ID");?></td>
                <td class="item_input"><?php echo ($vo["id"]); ?></td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("API_CONF_TITLE");?></td>
                <td class="item_input">
                    <input type="text" class="textbox require" name="title" value="<?php echo (htmlentities($vo["title"])); ?>"/>
                    &emsp;
                    <span class="tip_span">配置项文字说明</span>
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("API_CONF_NAME");?></td>
                <td class="item_input">
                    <input type="text" class="textbox" name="name" value="<?php echo (htmlentities($vo["name"])); ?>"readonly="true">
                    &emsp;
                    <span class="tip_span">KEY, 由大写字母,数字,下划线组成</span>
                </td>
            </tr>
            <td class="item_title"><?php echo L("API_CONF_VALUE");?></td>
            <td class="item_input">
                <input type="text" class="textbox" name="value" value="<?php echo (htmlentities($vo["value"])); ?>"/>
                &emsp;
                <span class="tip_span">VALUE</span>
            </td>
            </tr>

            <tr>
                <td class="item_title"><?php echo L("API_CONF_IS_EFFECT");?></td>
                <td class="item_input">
                    <input type="radio" name="is_effect" value="1" <?php if($vo['is_effect'] == 1) echo 'checked';?>>有效
                           <input type="radio" name="is_effect" value="0" <?php if($vo['is_effect'] == 0) echo 'checked';?>>无效
                </td>
            </tr>

            <tr>
                <td class="item_title"><?php echo L("API_CONF_TIP");?></td>
                <td class="item_input">
                    <input type="text" class="textbox" name="tip" value="<?php echo (htmlentities($vo["tip"])); ?>"/>
                    &emsp;
                    <span class="tip_span">可空</span>
                </td>
            </tr>
            <input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>" />
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="ApiConf" />
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
            if ($(this).val() == '1') {
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