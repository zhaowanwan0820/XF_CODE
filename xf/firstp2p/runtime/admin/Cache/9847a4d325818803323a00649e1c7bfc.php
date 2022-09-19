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
<style>
 a {
     white-space:nowrap;
 }
</style>

<div class="main">
<div class="main_title">项目列表</div>
<div class="blank5"></div>
<div class="button_row">
    <a href="/m.php?m=OexchangeProject&a=add" class="button"><?php echo L("ADD");?></a>
</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        项目编号：<input type="text" class="textbox" name="id" value="<?php echo trim($_REQUEST['id']);?>" size="8"/>
        项目名称：<input type="text" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" size="8"/>
        交易所备案产品编号：<input type="text" class="textbox" name="jys_number" value="<?php echo trim($_REQUEST['jys_number']);?>" size="8"/>
        交易所：<select name="jys_id" id="jys_id">
                <option value="0"></option>
                <?php if(is_array($jys)): foreach($jys as $type_key=>$type_item): ?><option value="<?php echo ($type_item['id']); ?>" <?php if($type_item['id'] == $_REQUEST['jys_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item['name']); ?></option><?php endforeach; endif; ?>
            </select>
        发行人id：<input type="text" class="textbox" name="fx_uid" value="<?php echo trim($_REQUEST['fx_uid']);?>" size="8"/>
        发行人：<input type="text" class="textbox" name="fx_name" value="<?php echo trim($_REQUEST['fx_name']);?>" size="8"/>
        业务状态：
        <select name="deal_status">
            <option value="">全部</option>
            <?php if(is_array($project_business_status)): foreach($project_business_status as $status_value=>$status_name): ?><option value="<?php echo ($status_value); ?>" <?php if($_REQUEST['deal_status'] == $status_value): ?>selected="selected"<?php endif; ?>><?php echo ($status_name); ?></option><?php endforeach; endif; ?>
        </select>
        状态：
        <select name="is_ok">
            <option value="-1">全部</option>
            <option value="1" <?php if($_REQUEST['is_ok'] == 1): ?>selected="selected"<?php endif; ?>>正常</option>
            <option value="0" <?php if(isset($_REQUEST['is_ok']) and $_REQUEST['is_ok'] == 0): ?>selected="selected"<?php endif; ?>>作废</option>
        </select>
        <input type="hidden" name = "m" value="OexchangeProject" />
        <input type="hidden" name = "a" value="index" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="8" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th width="50px">项目编号</th>
            <th>项目名称</th>
            <th>交易所备案产品编号</th>
            <th>交易所</th>
            <th>发行人/发行人id</th>
            <th>期限</th>
            <th>还款方式</th>
            <th>预期年化收益率</th>
            <th>业务状态</th>
            <th>状态</th>
            <th>创建时间</th>
            <th style="width:200px">操作</th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$project): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>&nbsp;<?php echo ($project["id"]); ?></td>
            <td>&nbsp;<?php echo ($project["name"]); ?></td>
            <td>&nbsp;<?php echo ($project['jys_number']); ?></td>
            <td>&nbsp;<?php echo ($jys[$project['jys_id']]['name']); ?></td>
            <td>&nbsp;<?php echo ($project["fx_uid"]); ?> / <?php echo ($user_list[$project['fx_uid']]['real_name']); ?></td>
            <td>&nbsp;<?php echo ($project['repay_time']); ?><?php if($project['repay_time'] == 1): ?>天<?php else: ?>个月<?php endif; ?></td>
            <td>&nbsp;<?php if(1 == $project['repay_type']): ?>到期支付本金收益（天）<?php endif; ?>
                <?php if(2 == $project['repay_type']): ?>到期支付本金收益（月）<?php endif; ?>
                <?php if(3 == $project['repay_type']): ?>按月支付收益到期还本<?php endif; ?>
                <?php if(4 == $project['repay_type']): ?>按季支付收益到期还本<?php endif; ?>
            </td>
            <td>&nbsp;<?php echo ($project['expect_year_rate']); ?>%</td>
            <td>&nbsp;<?php echo ($project_business_status[$project['deal_status']]); ?></td>
            <td><?php if($project['is_ok'] == 1): ?>正常<?php else: ?>作废<?php endif; ?></td>
            <td>&nbsp;<?php echo ($project["ctime"]); ?></td>
            <td>
                <?php if(3 > $project['deal_status']): ?><a href="m.php?m=OexchangeProject&a=edit&id=<?php echo ($project["id"]); ?>">编辑</a>&nbsp;<?php endif; ?>
                <a href="m.php?m=OexchangeProject&a=view&id=<?php echo ($project["id"]); ?>">查看</a>&nbsp;
                <a href="m.php?m=OexchangeProject&a=copy&id=<?php echo ($project["id"]); ?>">复制</a>&nbsp;
                <?php if(1 < $project['deal_status']): ?><a href="m.php?m=OexchangeBatch&a=index&pro_id=<?php echo ($project["id"]); ?>">批次列表</a>&nbsp;<?php endif; ?>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
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