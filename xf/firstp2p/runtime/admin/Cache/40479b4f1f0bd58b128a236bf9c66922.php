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
<div class="main">
<div class="main_title">资金记录汇总</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        编号：<input type="text" class="textbox" name="uid" value="<?php echo ($user_id); ?>" style="width:100px;" />
        截止时间：
        <input type="text" class="textbox" style="width:140px;" name="date" id="date" value="<?php echo ($_REQUEST['date']); ?>" onfocus="this.blur(); return showCalendar('date', '%Y-%m-%d %H:%M:%S', false, false, 'btn_operation_time');" title="截止时间" />
        <input type="button" class="button" id="btn_operation_time" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('date', '%Y-%m-%d %H:%M:%S', false, false, 'btn_operation_time');" />
        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="user_log_summary" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>
</div>

<div class="blank5"></div>

<table class="form" cellpadding=0 cellspacing=0>
	<tr>
		<td colspan=2 class="topTd"></td>
	</tr>
    <?php if(is_array($summary)): foreach($summary as $key=>$vo): ?><tr>
        <td class="item_title"><?php echo ($key); ?>:</td>
        <td class="item_input"><?php echo ($vo); ?></td>
    </tr><?php endforeach; endif; ?>
	<tr>
        <td class="item_title">资产总额=<br/>充值+收益-提现:</td>
        <td class="item_input"><?php echo ($total); ?></td>
    </tr>

	<tr>
		<td colspan=2 class="bottomTd"></td>
	</tr>
</table>
<div class="blank5"></div>

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