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


<link rel="stylesheet" type="text/css" href="/static/admin/Common/js/calendar/calendar.css" />
<script type="text/javascript" src="/static/admin/Common/js/calendar/calendar_lang.js" ></script>
<script type="text/javascript" src="/static/admin/Common/js/calendar/calendar.js"></script>

<style>
#dataTable th { text-align: left; }
</style>

<div class="main">
    <div class="main_title">
        <span>批次编号：<?php echo ($batchInfo['id']); ?>； 交易所备案产品编号：<?php echo ($projectInfo['jys_number']); ?>； <?php echo ($batchInfo['batch_number']); ?>期</span>
        <label>提前还款</label>
        <a href="<?php echo u("ExchangeRepayList/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a>
    </div>
    <div class="blank5"></div>
    <div class="blank5"></div>

    <fieldset>
      <legend>贷款管理</legend>
      <table id="dataTable" class="dataTable">
         <tr class="row">
            <td colspan="2">项目名称：<?php echo ($projectInfo['name']); ?></td>
         </tr>
         <tr class="row">
            <td>剩余本金：<?php echo ($batchStat['total_principal'] / 100); ?> 元</td>
            <td>借款期限：<?php echo ($projectInfo['repay_time']); ?><?php if($projectInfo['repay_type'] == 1): ?>天<?php else: ?>个月<?php endif; ?></td>
         </tr>
         <tr class="row">
            <td>出借人年化收益率：<?php echo ($projectInfo['expect_year_rate'] / 100000); ?>%</td>
            <td>提前还款锁定期：<?php echo ($projectInfo['lock_days']); ?>天</td>
         </tr>
         <tr class="row">
            <td>
                还款方式：
                <?php if($projectInfo['repay_type'] == 1): ?>到期支付本金收益(天)
                <?php elseif($projectInfo['repay_type'] == 2): ?>
                到期支付本金收益(月)
                <?php elseif($projectInfo['repay_type'] == 3): ?>
                按月支付收益到期还本
                <?php else: ?>
                按季支付收益到期还本<?php endif; ?>
            </td>
            <td>提前还款违约金系数：<?php echo ($projectInfo['ahead_repay_rate'] / 100000); ?>%</td>
         </tr>
     </table>
   </fieldset>
   <div class="blank5"></div>

    <form name="search" action="/m.php" method="get">
    <fieldset>
      <table id="dataTable" class="dataTable">
         <tr class="row" style="text-align:center">
            <td>到期还款明细</td>
            <td>提前还款明细</td>
         </tr>
         <tr class="row">
            <td>到期日期：<?php echo date("Y-m-d", $endBatchRepayTime);?></td>
            <td>
                计息结束日：
                <input type="text" class="textboxe" name="repay_time" id="repay_time" value="<?php echo ($calculate['selectedRepayDay']); ?>" style="width:85px;"/>
                <input type="button" class="button" id="btn_repay_time" value="选择时间" onclick="return showCalendar('repay_time', '%Y-%m-%d', false, false, 'btn_repay_time');" />
                <input type="button" class="button" id="clr_repay_time" value="清空时间" />
            </td>
         </tr>
         <tr class="row">
            <td>放款日期：<?php echo date("Y-m-d", $batchInfo['repay_start_time']);?></td>
            <td>放款日期：<?php echo date("Y-m-d", $batchInfo['repay_start_time']);?></td>
         </tr>
         <tr class="row">
            <td>借款期限：<?php echo ($projectInfo['repay_time']); ?><?php if($projectInfo['repay_type'] == 1): ?>天<?php else: ?>个月<?php endif; ?></td>
            <td>利息天数：<span class="text_span"><?php echo ($calculate['remainDay']); ?></span> 天</td>
         </tr>
         <tr class="row">
            <td>应还本金：<?php echo ($batchStat['total_principal'] / 100); ?> 元</td>
            <td>应还本金：<span class="text_span"><?php echo ($calculate['principal'] / 100); ?></span> 元</td>
         </tr>
         <tr class="row">
            <td>应还利息：<?php echo ($batchStat['total_interest'] / 100); ?> 元</td>
            <td>应还利息：<span class="text_span"><?php echo ($calculate['interest'] / 100); ?></span> 元</td>
         </tr>
         <tr class="row">
            <td>提前还款违约金：0.00 元</td>
            <td>提前还款违约金：<span class="text_span"><?php echo ($calculate['penaltyFee'] / 100); ?></span> 元</td>
         </tr>
         <tr class="row">
            <td>投资顾问费：<?php echo ($batchStat['total_invest_adviser_fee'] / 100); ?> 元</td>
            <td>投资顾问费：<span class="text_span"><?php echo ($calculate['investAdviserFee'] / 100); ?></span> 元</td>
         </tr>
         <tr class="row">
            <td>发行服务费：<?php echo ($batchStat['total_publish_server_fee'] / 100); ?> 元</td>
            <td>发行服务费：<span class="text_span"><?php echo ($calculate['publishServerFee'] / 100); ?></span> 元</td>
         </tr>
         <tr class="row">
            <td>担保费：<?php echo ($batchStat['total_guarantee_fee'] / 100); ?> 元</td>
            <td>担保费：<span class="text_span"><?php echo ($calculate['guaranteeFee'] / 100); ?></span> 元</td>
         </tr>
         <tr class="row">
            <td>咨询费：<?php echo ($batchStat['total_consult_fee'] / 100); ?> 元</td>
            <td>咨询费：<span class="text_span"><?php echo ($calculate['consultFee'] / 100); ?></span> 元</td>
         </tr>
         <tr class="row">
            <td>挂牌服务费：<?php echo ($batchStat['total_hang_server_fee'] / 100); ?> 元</td>
            <td>挂牌服务费：<span class="text_span"><?php echo ($calculate['hangServerFee'] / 100); ?></span> 元</td>
         </tr>
         <tr class="row">
            <td>还款总额：<?php echo ($batchStat['total_repay_money'] / 100); ?> 元</td>
            <td>还款总额：<span class="text_span"><?php echo ($calculate['repayMoney'] / 100); ?></span> 元</td>
         </tr>
         <tr>
            <td colspan="2" style="text-align:right; padding-right:300px;">
                <input type="hidden" value="prePay" name="a" />
                <input type="hidden" value="ExchangeRepayList" name="m" />
                <input type="hidden" value="<?php echo ($batchInfo['id']); ?>" name="batch_id" />
                <input type="hidden" name="startTime" id="startTime" value="<?php echo ($startTime); ?>"/>
                <input type="submit" id="calculate_btn" name="calculate" value="计算" class="button"/>
                <input type="submit" id="download_btn" name="download" value="回款明细下载" class="button"/>
                <input type="submit" id="preRepay_btn" name="preRepay" value="提前还款" class="button"/>
           </td>
         </tr>
     </table>
    </fieldset>
    </form>
    <div class="blank5"></div>

    <div class="page"><?php echo ($page); ?></div>
    <div class="blank5"></div>
</div>

<script>
    $(function() {
        $('#clr_repay_time').click(function() {
            $('#repay_time').val('');
        })

        $('#calculate_btn, #download_btn').click(function() {
            var repay_time = $('#repay_time').val().trim();
            if (repay_time == '') {
                alert("计息结束日不能为空!");
                return false;
            }
        })

        $('#preRepay_btn').click(function() {
            var repay_time = $('#repay_time').val().trim();
            if (repay_time == '') {
                alert("计息结束日不能为空!");
                return false;
            }

            var  startTime= $('#startTime').val();
            let d = new Date(repay_time);
            var search_repay_time = d.valueOf(d) / 1000 - 8*3600;
            if (search_repay_time <= startTime){
                if (!confirm("选择此日期后将在提前还款锁定期前还款，确定此操作吗?")) {
                    return false;
                }
            }

            if (!confirm("请确定是否提前还款(请下载提前还款回款计划)?")) {
                return false;
            }
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