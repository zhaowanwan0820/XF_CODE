<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
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

<div class="main">
    <div class="main_title">预约借款详情</div>
    <div class="blank5"></div>

    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            用户ID:
            <input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" style="width:150px;"/>

            审核状态:
            <select name="audit_status" id="audit_status" style="height:24px;">
                <option value=0 <?php if(trim($_REQUEST['audit_status']) == 0): ?>selected="selected"<?php endif; ?>>全部</option>
                <?php if(is_array($status)): foreach($status as $key=>$value): ?><option value="<?php echo ($key); ?>" <?php if(trim($_REQUEST['audit_status']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($value); ?></option><?php endforeach; endif; ?>
            </select>

            时间段：
            <input type="text" class="textbox" name="time_start" id="time_start" value="<?php echo trim($_REQUEST['time_start']);?>" style="width:150px;"
                   onfocus="return showCalendar('time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
            <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
            -
            <input type="text" class="textbox" name="time_end" id="time_end" value="<?php echo trim($_REQUEST['time_end']);?>"  style="width:150px;"
                   onfocus="return showCalendar('time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
            <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />

            <input type="hidden" value="LoanIntention" name="m" />
            <input type="hidden" value="index" name="a" />

            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
            <input type="button" class="button" value="<?php echo L("EXPORT");?>" onclick="export_csv();" />
        </form>
    </div>
    <div class="blank5"></div>

    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan="20" class="topTd">&nbsp;</td>
        </tr>

        <tr class="row">
            <th>编号</th>
            <th>会员ID</th>
            <th>申请金额</th>
            <th>申请期限(月)</th>
            <th>联系电话</th>
            <th>申请类型</th>
            <th>申请时间</th>
            <th>更新时间</th>
            <th>审核状态</th>
            <th>操作</th>
        </tr>

        <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
            <td><?php echo ($item["id"]); ?></td>
            <td><a href="<?php echo U('User/index');?>&user_id=<?php echo ($item["user_id"]); ?>"><?php echo ($item["user_id"]); ?></a></td>
            <td><?php echo ($item["loan_money"]); ?></td>
            <td><?php echo ($item["loan_time"]); ?></td>
            <td><?php echo ($item["phone"]); ?></td>
            <td><?php if($item["type"] != '2'): ?>变现通<?php else: ?><font style="color:red">职易贷</font><?php endif; ?></td>
            <td><?php echo (to_date($item["apply_time"])); ?></td>
            <td><?php echo (to_date($item["update_time"])); ?></td>
            <td><?php echo ($status[$item['status']]); ?></td>
            <td>
                <a href="<?php echo U('LoanIntention/show');?>&id=<?php echo ($item["id"]); ?>">查看详情</a> &nbsp;
                <?php if($item["status"] == LoanIntentionAction::NOT_AUDIT): ?><a href="javascript:majax('audit', 'id=<?php echo ($item["id"]); ?>&status=2');">同意</a>&nbsp;
                    <a href="javascript:majax('audit', 'id=<?php echo ($item["id"]); ?>&status=3');">拒绝</a><?php endif; ?>
            </td>
        </tr><?php endforeach; endif; ?>

        <tr>
            <td colspan="20" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
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