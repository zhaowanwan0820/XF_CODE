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
<?php  ?>
<div class="main">
<div class="main_title">红包组查询</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        用户ID:
        <input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" style="width:70px;" />
        手机号:
        <input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>" style="width:70px;" />
        红包组类型：
        <select name="type" id="type">
                <option value=1000 <?php if($bonus_type_id == 1000): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($typeMap)): foreach($typeMap as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if($bonus_type_id == $key): ?>selected="selected"<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        红包任务：
        <select name="task_id">
                <option value="0" <?php if(intval($_REQUEST['task_id']) == 0): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($taskList)): foreach($taskList as $key=>$task_item): ?><option value="<?php echo ($task_item["id"]); ?>" <?php if(intval($_REQUEST['task_id']) == $task_item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($task_item["name"]); ?></option><?php endforeach; endif; ?>
        </select>
        时间段：
        <input type="text" class="textbox" name="time_start" id="time_start" value="<?php echo trim($_REQUEST['time_start']);?>" onfocus="return showCalendar('time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:120px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
        -
        <input type="text" class="textbox" name="time_end" id="time_end" value="<?php echo trim($_REQUEST['time_end']);?>" onfocus="return showCalendar('time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:120px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
        <input type="hidden" value="BonusGroupQuery" name="m" />
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
        <th>姓名</th>
        <th>注册手机号</th>
        <th>会员名称</th>
        <th>红包组金额</th>
        <th>红包组个数</h>
        <th>红包发放时间</th>
        <th>红包过期时间</th>
        <!--<th>红包领取个数</th>
        <th>红包使用个数</th>-->
        <th>详情</th>
    </tr>
    <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
        <!--<td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td>-->
        <td><?php echo ($item["id"]); ?></td>
        <td><?php echo ($item["real_name"]); ?></td>
        <td><?php echo ($item["mobile"]); ?></td>
        <td><?php echo ($item["user_name"]); ?></td>
        <td><?php echo ($item["money"]); ?></td>
        <td><?php echo ($item["count"]); ?></td>
        <td><?php echo ($item["create_time"]); ?></td>
        <td><?php echo ($item["expire_time"]); ?></td>
        <!--<td><?php echo ($item["get_count"]); ?></td>
        <td><?php echo ($item["used_count"]); ?></td>-->
        <td>
            <a href="./m.php?m=BonusGroupQuery&a=detail&group_id=<?php echo ($item["id"]); ?>">详情</a>&nbsp;
        </td>
    </tr><?php endforeach; endif; ?>
    <tr>
        <td colspan="20" class="bottomTd">&nbsp;</td>
    </tr>
</table>
<!-- Think 系统列表组件结束 -->
<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
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