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

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<script type="text/javascript" src="__TMPL__widget/leanModal.min.js"></script>
<style type="text/css">
.strnormal {
    word-wrap: break-word;
    width: 350px;
    margin-top: -1em;
}
.str2long
{
/*	display: -webkit-box;
    word-wrap: break-word;
    width: 400px;
    text-overflow: ellipsis;
    overflow: hidden;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    margin-bottom: 15px;
*/
/*兼容ie方案*/
	width: 350px;
	position: relative;
	line-height: 1.4em;
	height: 4.2em;
	overflow: hidden;
	word-wrap: break-word;
	margin: 2px 12px;
	padding: 0px;
}
.str2long::after {
	content: "…";
    position: absolute;
    bottom: 0;
    right: 0;
    padding: 0 10px 0px 5px;
}
.str2longwhite::after {
	background: white;
}
.str2longgreen::after {
	background: #D5F7E2;
}
#lean_overlay {
    position: fixed;
    z-index:100;
    top: 0px;
    left: 0px;
    height:100%;
    width:100%;
    background: #000;
    display: none;
}
#showDetail {
	width: 600px;
	padding: 30px;
	display:none;
	background: white;
	border-radius: 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px;
	box-shadow: 0px 0px 4px rgba(0,0,0,0.7); -webkit-box-shadow: 0 0 4px rgba(0,0,0,0.7); -moz-box-shadow: 0 0px 4px rgba(0,0,0,0.7);
}
#showDetail p {
	color: #666;
	text-shadow: none;
	display: block;
	word-wrap: break-word;
	max-height: 400px;
	overflow-y: auto;
}
</style>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="search_row">
	<form name="search" action="__APP__" method="get">
		<?php echo L("KEYWORD");?>：<input type="text" class="textbox" name="log_info" value="<?php echo trim($_REQUEST['key']);?>" />
		<?php echo L("LOG_TIME");?>：
		<input type="text" class="textbox" name="log_begin_time" id="log_begin_time" value="<?php echo trim($_REQUEST['log_begin_time']);?>" onfocus="return showCalendar('log_begin_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_log_begin_time');" />
		<input type="button" class="button" id="btn_log_begin_time" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('log_begin_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_log_begin_time');" />
		-
		<input type="text" class="textbox" name="log_end_time" id="log_end_time" value="<?php echo trim($_REQUEST['log_end_time']);?>" onfocus="return showCalendar('log_end_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_log_end_time');" />
		<input type="button" class="button" id="btn_log_end_time" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('log_end_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_log_end_time');" />


		<input type="hidden" value="Log" name="m" />
		<input type="hidden" value="index" name="a" />
		<input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
	</form>
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="11" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','Log','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="350px"><a href="javascript:sortBy('log_info','<?php echo ($sort); ?>','Log','index')" title="按照<?php echo L("LOG_INFO");?><?php echo ($sortType); ?> "><?php echo L("LOG_INFO");?><?php if(($order)  ==  "log_info"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('log_time','<?php echo ($sort); ?>','Log','index')" title="按照<?php echo L("LOG_TIME");?><?php echo ($sortType); ?> "><?php echo L("LOG_TIME");?><?php if(($order)  ==  "log_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('log_ip','<?php echo ($sort); ?>','Log','index')" title="按照<?php echo L("LOG_IP");?><?php echo ($sortType); ?> "><?php echo L("LOG_IP");?><?php if(($order)  ==  "log_ip"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('log_admin','<?php echo ($sort); ?>','Log','index')" title="按照<?php echo L("LOG_ADMIN");?><?php echo ($sortType); ?> "><?php echo L("LOG_ADMIN");?><?php if(($order)  ==  "log_admin"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('log_status','<?php echo ($sort); ?>','Log','index')" title="按照<?php echo L("LOG_STATUS");?><?php echo ($sortType); ?> "><?php echo L("LOG_STATUS");?><?php if(($order)  ==  "log_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('module','<?php echo ($sort); ?>','Log','index')" title="按照<?php echo L("MODULE");?><?php echo ($sortType); ?> "><?php echo L("MODULE");?><?php if(($order)  ==  "module"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('action','<?php echo ($sort); ?>','Log','index')" title="按照<?php echo L("ACTION");?><?php echo ($sortType); ?> "><?php echo L("ACTION");?><?php if(($order)  ==  "action"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('extra_info','<?php echo ($sort); ?>','Log','index')" title="按照旧值<?php echo ($sortType); ?> ">旧值<?php if(($order)  ==  "extra_info"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('new_info','<?php echo ($sort); ?>','Log','index')" title="按照新值<?php echo ($sortType); ?> ">新值<?php if(($order)  ==  "new_info"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$log): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($log["id"]); ?>"></td><td>&nbsp;<?php echo ($log["id"]); ?></td><td>&nbsp;<?php echo (short($log["log_info"])); ?></td><td>&nbsp;<?php echo (to_date($log["log_time"])); ?></td><td>&nbsp;<?php echo ($log["log_ip"]); ?></td><td>&nbsp;<?php echo (get_admin_name($log["log_admin"])); ?></td><td>&nbsp;<?php echo (get_log_status($log["log_status"])); ?></td><td>&nbsp;<?php echo ($log["module"]); ?></td><td>&nbsp;<?php echo ($log["action"]); ?></td><td>&nbsp;<?php echo ($log["extra_info"]); ?></td><td>&nbsp;<?php echo ($log["new_info"]); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="11" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
<div id="showDetail"><p></p></div>
</div>
<script>
// 初始化
$(function()
{
	$('a[rel*=leanModal]').leanModal();
	$('.str2long').each(function(_, div) {
		var trColor = $($(div).parents('tr').get(0)).css('background-color');
		if (trColor == 'rgba(0, 0, 0, 0)') $(div).addClass('str2longwhite');
		else $(div).addClass('str2longgreen');
	});
})
var showAll = function (obj)
{
	$("#showDetail p").text($(obj).next('div').text());
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