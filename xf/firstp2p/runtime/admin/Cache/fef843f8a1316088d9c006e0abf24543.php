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
<style>
    table .warn-cell {color:#F00;}
</style>
<div class="main">
<div class="main_title">任务#<?php echo ($id); ?>详情</div>
<div class="blank5"></div>
<table id="dataTable" class="dataTable" style="table-layout:fixed">
<tr><th width="200">任务状态</th><td><?php echo ($job["status"]); ?></td></tr>
<tr><th>待执行函数</th><td><?php echo ($job["function"]); ?></td></tr>
<tr><th>参数</th><td style="word-break:break-all; word-wrap:break-word"><?php echo ($job["params"]); ?></td></tr>
<tr><th>创建时间</th><td><?php echo ($job["created"]); ?>(<?php echo ($job["create_time"]); ?>)</td></tr>
<tr><th>指定启动时间</th><td><?php echo ($job["started"]); ?>(<?php echo ($job["start_time"]); ?>)</td></tr>
<tr><th>启动时间</th><td><?php echo ($job["begined"]); ?>(<?php echo ($job["begin_time"]); ?>)</td></tr>
<tr><th>结束时间</th><td><?php echo ($job["finished"]); ?>(<?php echo ($job["finish_time"]); ?>)</td></tr>
<tr><th>执行时长</th><td><?php echo ($job["job_cost"]); ?>(秒)</td></tr>
<tr><th>剩余重试次数</th><td><?php echo ($job["retry_cnt"]); ?></td></tr>
<tr><th>最后一次错误信息</th><td><?php echo ($job["err_msg"]); ?></td></tr>
<tr class=""><td colspan="2" align="center"><a href="javascript:redo(<?php echo ($job["id"]); ?>);">放入队列</a> <a href="javascript:window.location.href='/m.php?m=Jobs&status=<?php echo ($status); ?>&p=<?php echo ($p); ?>';">返回列表页</a></td></tr>
</table>
<div class="blank5"></div>
</div>
<script type="text/javascript" charset="utf-8">
    function redo(id) {
        window.location.href = "/m.php?m=Jobs&a=redo&id="+id;
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