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


<style>
#dataTable th { text-align: left; }
</style>

<div class="main">
    <div class="main_title">
        <label>还款计划</label>
        <a href="<?php echo u("OexchangeBatch/index?pro_id=". $projectInfo['id']);?>" class="back_list"><?php echo L("BACK_LIST");?></a>
    </div>
    <div class="blank5"></div>
    <div class="blank5"></div>

    <p><input type="button" id="export_btn" value="导出" class="button"/></p>
    <div class="blank5"></div>
    <div class="blank5"></div>

    <table id="dataTable" class="dataTable">
        <tr>
            <th style="text-align:left;width:130px">交易所备案产品编号：</th>
            <td><?php echo ($projectInfo['jys_number']); ?></td>
            <th style="text-align:left;width:70px;">批次id：</th>
            <td><?php echo ($batchInfo['id']); ?></td>
            <th style="text-align:left;width:120px;">发行人名称：</th>
            <td><?php echo ($publishInfo['real_name']); ?></td>
            <th style="text-align:left;width:50px;">期数：</th>
            <td><?php echo ($batchInfo['batch_number']); ?></td>
            <th style="text-align:left;width:70px;">咨询机构：</th>
            <td><?php echo ($consultInfo['name']); ?></td>
        </tr>
    </table>
    <div class="blank5"></div>
    <div class="blank5"></div>

    <table id="dataTable" class="dataTable">
        <tr class="row">
            <th>序号</th>
            <th>还款日</th>
            <th>还款金额</th>
            <th>本息</th>
            <th>投资顾问费</th>
            <th>发行服务费</th>
            <th>咨询费</th>
            <th>担保费</th>
            <th>挂牌服务费</th>
            <th>回款明细</th>
        </tr>
        <?php if(is_array($list)): $index = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$index;$mod = ($index % 2 )?><tr class="row">
            <td><?php echo ($pageSize * ($nowPage - 1) + $index); ?></td>
            <td><?php echo date("Y-m-d", $item['repay_time']);?></td>
            <td><?php echo ($item['repay_money'] / 100); ?></td>
            <td><?php echo ($item['principal'] / 100 + $item['interest'] / 100); ?></td>
            <td><?php echo ($item['invest_adviser_fee'] / 100); ?></td>
            <td><?php echo ($item['publish_server_fee'] / 100); ?></td>
            <td><?php echo ($item['consult_fee'] / 100); ?></td>
            <td><?php echo ($item['guarantee_fee'] / 100); ?></td>
            <td><?php echo ($item['hang_server_fee'] / 100); ?></td>
            <td>
                <?php if($item['principal'] or $item['interest']): ?><a href="<?php echo u('ExchangeLoadRepay/planExport?batch_id=' . $batchInfo['id'] . '&repay_id=' . $item['id']);?>">回款明细</a>
                <?php else: ?>
                    前收手续费<?php endif; ?>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
    <div class="blank5"></div>

    <div class="page"><?php echo ($page); ?></div>
    <div class="blank5"></div>
</div>

<script>
    $(function() {
        $('#export_btn').click(function() {
            location.href="/m.php?m=ExchangeBatchRepay&a=planExport&batch_id=<?php echo ($batchInfo['id']); ?>"
        })
    })
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