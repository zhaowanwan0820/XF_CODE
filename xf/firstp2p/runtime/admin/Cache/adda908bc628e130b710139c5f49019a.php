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
<script type="text/javascript" src="__TMPL__Common/js/user.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<script type="text/javascript" src="__TMPL__widget/leanModal.min.js"></script>
<?php  ?>
<style type="text/css">
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
</style>
<div class="main">
<div class="main_title">站内信定向发送列表</div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="新增" onclick="window.location.href='/m.php?m=PushTool&a=add';">
</div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        标题:
        <input type="text" class="textbox" name="title" value="<?php echo trim($_REQUEST['title']);?>" style="width:70px;" />
        发送内容:
        <input type="text" class="textbox" name="content" value="<?php echo trim($_REQUEST['content']);?>" style="width:70px;" />
        推送类型：
        <select name="type" id="type">
                <option value="0" <?php if(intval($_REQUEST['type']) == 0): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($typeMap)): foreach($typeMap as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if($_REQUEST['type'] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        发送状态：
        <select name="send_status">
                <option value="0" <?php if(intval($_REQUEST['send_status']) == 0): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($sendStatusMap)): foreach($sendStatusMap as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if(intval($_REQUEST['send_status']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        发送时间段：
        <input type="text" class="textbox" name="time_start" id="time_start" value="<?php echo trim($_REQUEST['time_start']);?>" onfocus="return showCalendar('time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:120px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
        -
        <input type="text" class="textbox" name="time_end" id="time_end" value="<?php echo trim($_REQUEST['time_end']);?>" onfocus="return showCalendar('time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:120px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
        <input type="hidden" value="PushTool" name="m" />
        <input type="hidden" value="index" name="a" />

        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<div class="blank5"></div>
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan="20" class="topTd">&nbsp;</td>
    </tr>
    <tr class="row">
        <!--<th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th>-->
        <th>编号</th>
        <th>发送类型</th>
        <th>标题</th>
        <th>发送内容</th>
        <th>可接收的会员</th>
        <th>链接</th>
        <th>发送开始时间</th>
        <th>发送状态</th>
        <th>操作人</th>
        <th>操作</th>
    </tr>
    <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
        <td><?php echo ($item["id"]); ?></td>
        <td><?php echo $typeMap[$item['type']]; ?></td>
        <td><?php echo ($item["title"]); ?></td>
        <td><?php echo ($item["content"]); ?></td>
        <td>
            <?php if ($item['scope'] == 4) { ?>
            <a href="<?php echo (getParamShow($item)); ?>">csv下载</a>
            <?php } else { ?>
            <?php echo (getParamShow($item)); ?>
            <?php if ($item['scope'] == 2 && mb_strlen($item[param]) > 50) { ?>
            ...<a id="go" rel="leanModal" name="showDetail" href="#showDetail" onclick="showAll('<?php echo ($item["param"]); ?>')">显示全部</a>
            <?php } ?>
            <?php } ?>
        </td>
        <td><?php if (!empty($item['url'])) { ?><a href="<?php echo ($item["url"]); ?>" target="_blank">点击打开</a><?php } ?></td>
        <td><?php echo (format_date($item["send_time"])); ?></td>
        <td><?php echo $sendStatusMap[$item['send_status']]; ?></td>
        <td><?php echo (get_admin_name($item["admin_id"])); ?></td>
        <td><?php if ($item['send_status'] == 1) { ?><a href="javascript:void(0)" onclick="removeItem(<?php echo ($item["id"]); ?>)">删除 </a><?php } ?></td>
    </tr><?php endforeach; endif; ?>
    <tr>
        <td colspan="20" class="bottomTd">&nbsp;</td>
    </tr>
</table>
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
var showAll = function (html)
{
    $("#showDetail p").text(html);
}
var removeItem = function(id)
{
    $.ajax({
        type: "POST",
        url: "/m.php?m=PushTool&a=removePushTask",
        data: {id:id},
        dataType: 'json',
        success: (function(data) {
            alert(data.info);
            if (data.status == 0) {
                return false;
            }
            window.location.reload();
        })
    });
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