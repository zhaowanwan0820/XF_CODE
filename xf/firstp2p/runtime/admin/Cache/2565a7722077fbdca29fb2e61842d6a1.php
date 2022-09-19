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
<div class="main">
<div class="main_title"><?php echo ($user["user_name"]); ?> 资产总额</div>
<div class="blank5"></div>
<div>以下统计如前台显示(资产总额=待还本金+待还利息+余额+冻结+红包)</div>
<div class="blank5"></div>

<table class="form" cellpadding=0 cellspacing=0>
	<tr>
		<td colspan=2 class="topTd"></td>
	</tr>
    <tr>
        <td class="item_title">持有资产:</td>
        <td class="item_input"><?php echo ($summary["u_stay"]["principal"]); ?></td>
    </tr>
	<tr>
        <td class="item_title">待还本金:</td>
        <td class="item_input"><?php echo ($summary["principal"]); ?></td>
    </tr>
	<tr>
        <td class="item_title">待还利息(含通知贷已还利息):</td>
        <td class="item_input"><?php echo ($summary["u_stay"]["interest"]); ?></td>
    </tr>
    <tr>
        <td class="item_title">待还利息:</td>
        <td class="item_input"><?php echo ($summary["interest"]); ?></td>
    </tr>
    <tr>
        <td class="item_title">待还总额:</td>
        <td class="item_input"><?php echo ($summary["stay"]); ?></td>
    </tr>
    <tr>
        <td class="item_title">用户余额:</td>
        <td class="item_input"><?php echo ($user["money"]); ?></td>
    </tr>
    <tr>
        <td class="item_title">用户冻结余额:</td>
        <td class="item_input"><?php echo ($user["lock_money"]); ?></td>
    </tr>
    <tr>
        <td class="item_title">资产总额:</td>
        <td class="item_input"><?php echo ($money_all); ?></td>
    </tr>

	<tr>
		<td colspan=2 class="bottomTd"></td>
	</tr>
</table>
<div class="blank5"></div>
<!--<div>以下为分类型回款统计(上表待还总额=下表总和)</div>-->
<!--<div class="blank5"></div>-->
<!--<table class="form" cellpadding=0 cellspacing=0>-->
	<!--<tr>-->
		<!--<td colspan=2 class="topTd"></td>-->
	<!--</tr>-->
    <!--<tr>-->
        <!--<td class="item_title">普通标待还本金:</td>-->
        <!--<td class="item_input"><?php echo ($principal); ?></td>-->
    <!--</tr>-->
	<!--<tr>-->
        <!--<td class="item_title">通知贷已赎回待还本金:</td>-->
        <!--<td class="item_input"><?php echo ($principal_compound); ?></td>-->
    <!--</tr>-->
	<!--<tr>-->
        <!--<td class="item_title">普通标待还利息:</td>-->
        <!--<td class="item_input"><?php echo ($interest); ?></td>-->
    <!--</tr>-->
    <!--<tr>-->
        <!--<td class="item_title">通知贷待还利息:</td>-->
        <!--<td class="item_input"><?php echo ($interest_compound); ?></td>-->
    <!--</tr>-->
    <!--<tr>-->
        <!--<td class="item_title">通知贷未赎回本金:</td>-->
        <!--<td class="item_input"><?php echo ($compound_processing); ?></td>-->
    <!--</tr>-->
    <!--<tr>-->
        <!--<td class="item_title">资产总额:</td>-->
        <!--<td class="item_input"><?php echo ($total); ?></td>-->
    <!--</tr>-->
	<!--<tr>-->
		<!--<td colspan=2 class="bottomTd"></td>-->
	<!--</tr>-->
<!--</table>-->
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