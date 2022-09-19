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
<div class="main_title">批次列表 <a href="<?php echo u("OexchangeProject/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<div class="button_row">
    <a href="javascript:if(confirm('请确认是否新建批次')) location='/m.php?m=OexchangeBatch&a=add&pro_id=<?php echo ($project["id"]); ?>';" class="button"><?php echo L("ADD");?></a>
</div>
<div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="8" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th>批次id</th>
            <th>项目名称</th>
            <th>交易所备案产品编号</th>
            <th>期数</th>
            <th>借款期限</th>
            <th>发行人/发行人id</th>
            <th>批次金额</th>
            <th>投资状态</th>
            <th>状态</th>
            <th>创建时间</th>
            <th>最后一批次起息</th>
            <th style="width:250px">操作</th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$batch): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>&nbsp;<?php echo ($batch['id']); ?></td>
            <td>&nbsp;<?php echo ($project['name']); ?></td>
            <td>&nbsp;<?php echo ($project['jys_number']); ?></td>
            <td>&nbsp;<?php echo ($batch['batch_number']); ?>期</td>
            <td>&nbsp;<?php echo ($project['repay_time']); ?> <?php if(1 == $project['repay_type']): ?>天 <?php else: ?>个月<?php endif; ?> </td>
            <td>&nbsp;<?php echo ($project['fx_uid']); ?> / <?php echo ($fxuser['real_name']); ?></td>
            <td>&nbsp;<?php echo ($batch['amount']); ?></td>
            <td>&nbsp;<?php echo ($branch_business_status[$batch['deal_status']]); ?></td>
            <td><?php if($batch['is_ok'] == 1): ?>有效<?php else: ?>无效<?php endif; ?></td>
            <td>&nbsp;<?php echo ($batch['ctime']); ?></td>
            <td><?php if($batch['is_last_start'] == 1): ?>是<?php else: ?>否<?php endif; ?></td>
            <td>
                <?php if(1 == $batch['deal_status']): ?><a href="m.php?m=OexchangeBatch&a=edit&id=<?php echo ($batch['id']); ?>">编辑</a>&nbsp;<?php endif; ?>
                <a href="m.php?m=ExchangeLoad&a=index&batch_id=<?php echo ($batch['id']); ?>">投资列表</a>&nbsp;
                <a href="m.php?m=OexchangeBatch&a=fee&id=<?php echo ($batch['id']); ?>">费用明细</a>&nbsp;
                <?php if(1 < $batch['deal_status']): ?><a href="m.php?m=ExchangeBatchRepay&a=plan&batch_id=<?php echo ($batch['id']); ?>">还款计划</a>&nbsp;<?php endif; ?>
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