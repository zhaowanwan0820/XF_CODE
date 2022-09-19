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

<script type="text/javascript" src="__TMPL__Common/js/user.field.js"></script>
<div class="main">
<div class="main_title">网信账户自动提现</div>
<div class="blank5"></div>
<form name="upload" action="__APP__" method="post">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">用户ID</td>
        <td class="item_input">
            <textarea name="user_ids" cols="40" rows="8" ><?php echo ($userIds); ?></textarea> <span style="color:red">(注: 多个用户id用,隔开)</span>
        </td>
    </tr>
    <tr>
        <td class="item_title">提现时间</td>
        <td class="item_input">
            <input type="text" name="hour" style="width:20px" value="<?php echo ($hour); ?>" /> 时 <input type='text' name='minute' style="width:20px" value="<?php echo ($minute); ?>" /> 分 <span style="color:red">(注: 提现时间可设置的时间段为19点至24点)</span>
        </td>
    </tr>
    <tr>
        <td class="item_title">邮件告警地址</td>
        <td class="item_input">
            <textarea name="emails" cols="40" rows="8" ><?php echo ($emails); ?></textarea> <span style="color:red">(注: 多个邮件地址用,隔开)</span>
        </td>
    </tr>
    <tr>
        <td class="item_title"></td>
        <td class="item_input">
            <!--隐藏元素-->
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="UserCarry" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="autoWithdraw" />
            <!--隐藏元素-->
            <input type="submit" class="button" value="保存" />
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