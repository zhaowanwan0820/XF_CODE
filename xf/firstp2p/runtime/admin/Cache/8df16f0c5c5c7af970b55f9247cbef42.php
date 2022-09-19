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
<div class="main_title">任务管理 - <?php echo ($statusCn); ?></div>
<div class="blank5"></div>

<?php if($status == 1 || $status == 3): ?><div class="button_row">
    <input type="button" class="button" value="重试" onclick="multi_redo();" />
</div><?php endif; ?>
<div class="blank5"></div>
<div class="search_row">
  <form name="search" action="__APP__" method="get">
    优先级：<input type="text" class="textbox" name="priority" value="<?php echo trim($_REQUEST['priority']);?>" size="8"/>
    <input type="hidden" value="Jobs" name="m" />    
    <?php if($status == 0): ?><input type="hidden" value="wait" name="a" />
    <?php elseif($status == 1): ?>
    <input type="hidden" value="process" name="a" />
    <?php elseif($status == 2): ?>
    <input type="hidden" value="succ" name="a" />
    <?php elseif($status == 3): ?>
    <input type="hidden" value="fail" name="a" /><?php endif; ?>
    <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
  </form>
</div>
<div class="blank5"></div>

<script>
function multi_redo() {
    idBox = $(".key:checked");

    var param = '';
    if(idBox.length == 0){
        idBox = $(".key");
    }

    idArray = new Array();
    $.each( idBox, function(i, n){
        idArray.push($(n).val());
    });

    if(idArray.length == 0){
        alert('无可导出的数据！');
        return false;
    }

    id = idArray.join(",");

/*
    var inputs = $(".search_row").find("input");

    for(i=0; i<inputs.length; i++){
        if(inputs[i].name != 'm' && inputs[i].name != 'a')
        param += "&"+inputs[i].name+"="+$(inputs[i]).val();
    }
*/

    var url= ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=multi_redo&id="+id;
    window.location.href = url;
}
</script>

<?php function f_to_date($stamp) {
    if (empty($stamp)) {
        return '-';
    }
    return date('Y-m-d H:i:s', $stamp + 28800);
}
function f_cutstr($string) {
    $subString = $string;
    if (mb_strlen($string) > 15) {
        $subString = '<a href="javascript:;" title="'.str_replace('"',"'", $string).'">' . mb_substr($string, 0, 15) . '...</a>';
    }
    return $subString;
}
function f_status($status) {
    return $GLOBALS['statusCn'][$status];
} ?>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="12" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','Jobs','wait')" title="按照编号<?php echo ($sortType); ?> ">编号<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('function','<?php echo ($sort); ?>','Jobs','wait')" title="按照待执行脚本<?php echo ($sortType); ?> ">待执行脚本<?php if(($order)  ==  "function"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('params','<?php echo ($sort); ?>','Jobs','wait')" title="按照参数<?php echo ($sortType); ?> ">参数<?php if(($order)  ==  "params"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','Jobs','wait')" title="按照状态<?php echo ($sortType); ?> ">状态<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','Jobs','wait')" title="按照创建时间<?php echo ($sortType); ?> ">创建时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('start_time','<?php echo ($sort); ?>','Jobs','wait')" title="按照指定启动时间<?php echo ($sortType); ?> ">指定启动时间<?php if(($order)  ==  "start_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('begin_time','<?php echo ($sort); ?>','Jobs','wait')" title="按照启动时间<?php echo ($sortType); ?> ">启动时间<?php if(($order)  ==  "begin_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('finish_time','<?php echo ($sort); ?>','Jobs','wait')" title="按照结束时间<?php echo ($sortType); ?> ">结束时间<?php if(($order)  ==  "finish_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('job_cost','<?php echo ($sort); ?>','Jobs','wait')" title="按照执行时长<?php echo ($sortType); ?> ">执行时长<?php if(($order)  ==  "job_cost"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('priority','<?php echo ($sort); ?>','Jobs','wait')" title="按照优先级<?php echo ($sortType); ?> ">优先级<?php if(($order)  ==  "priority"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($user["id"]); ?>"></td><td>&nbsp;<?php echo ($user["id"]); ?></td><td>&nbsp;<?php echo ($user["function"]); ?></td><td>&nbsp;<?php echo (f_cutstr($user["params"])); ?></td><td>&nbsp;<?php echo (f_status($user["status"])); ?></td><td>&nbsp;<?php echo (f_to_date($user["create_time"])); ?></td><td>&nbsp;<?php echo (f_to_date($user["start_time"])); ?></td><td>&nbsp;<?php echo (f_to_date($user["begin_time"])); ?></td><td>&nbsp;<?php echo (f_to_date($user["finish_time"])); ?></td><td>&nbsp;<?php echo ($user["job_cost"]); ?></td><td>&nbsp;<?php echo ($user["priority"]); ?></td><td><a href="javascript:view('<?php echo ($user["id"]); ?>')">查看详情</a>&nbsp;<a href="javascript:redo('<?php echo ($user["id"]); ?>')">手动执行</a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="12" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>

<div class="blank5"></div>
</div>
<script type="text/javascript" charset="utf-8">
    var status = '<?php echo ($status); ?>';
    var p = '<?php echo ($p); ?>';
    function view(id) {
        if (parseInt(p) > 0) {
            window.location.href = "/m.php?m=Jobs&a=view&status="+status+"&p="+p+"&id="+id;
            return ;
        }
        window.location.href = "/m.php?m=Jobs&a=view&status="+status+"&id="+id;
    }
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