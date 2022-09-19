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
        <span>批次编号：<?php echo ($batchInfo['id']); ?>； 交易所备案产品编号：<?php echo ($projectInfo['jys_number']); ?>； <?php echo ($batchInfo['batch_number']); ?>期</span>
        <label>强制还款</label>
        <a href="<?php echo u("ExchangeRepayList/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a>
    </div>
    <div class="blank5"></div>
    <div class="blank5"></div>

    <form name="search" action="/m.php" method="post">
    <table id="dataTable" class="dataTable">
        <tr class="row">
            <th style="width:60px; text-align:center">选择还款</th>
            <th>还款日</th>
            <th>已还金额</th>
            <th>待还金额</th>
            <th>待还本息</th>
            <th>投资顾问费</th>
            <th>发行服务费</th>
            <th>咨询费</th>
            <th>担保费</th>
            <th>挂牌服务费</th>
            <th>状态</th>
        </tr>
        <?php if(is_array($repayList)): $index = 0; $__LIST__ = $repayList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$index;$mod = ($index % 2 )?><tr class="row">
            <td style="width:60px; text-align:center">
                <?php if($item['status'] == 1): ?><input type="checkbox" name="repay_ids[]" class="repay_ids" value="<?php echo ($item['id']); ?>"/><?php endif; ?>
            </td>
            <td><?php echo date('Y-m-d', $item['repay_time']);?></td>
            <td><?php if($item['status'] > 1): ?><?php echo ($item['repay_money'] / 100); ?><?php else: ?>0<?php endif; ?></td>
            <td><?php if($item['status'] > 1): ?>0<?php else: ?><?php echo ($item['repay_money'] / 100); ?><?php endif; ?></td>
            <td><?php if($item['status'] > 1): ?>0<?php else: ?><?php echo ($item['principal'] / 100 + $item['interest'] / 100); ?><?php endif; ?></td>
            <td><?php echo ($item['invest_adviser_fee'] / 100); ?></td>
            <td><?php echo ($item['publish_server_fee'] / 100); ?></td>
            <td><?php echo ($item['consult_fee'] / 100); ?></td>
            <td><?php echo ($item['guarantee_fee'] / 100); ?></td>
            <td><?php echo ($item['hang_server_fee'] / 100); ?></td>
            <td>
                <?php if($item['status'] == 1): ?>待还
                <?php elseif($item['status'] == 2): ?>
                    准时还款
                <?php elseif($item['status'] == 3): ?>
                    已提前还款
                <?php else: ?>
                    逾期还款<?php endif; ?>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
    <div class="blank5"></div>

    <input type="hidden" value="doNormalPay" name="a" />
    <input type="hidden" value="ExchangeRepayList" name="m" />
    <input type="hidden" value="<?php echo ($batchInfo['id']); ?>" name="batch_id" />
    <input type="submit" id="submit_btn" value="提交" class="button"/>
    <div class="blank5"></div>
    </form>

    <div class="page"><?php echo ($page); ?></div>
    <div class="blank5"></div>
</div>

<script>
    $(function() {
        $('#submit_btn').click(function() {
            var allCheckboxStr = '';
            $('.repay_ids').each(function() {
                allCheckboxStr += $(this).val()
                })

            var checkedCheckboxStr = '';
            $('.repay_ids:checked').each(function() {
                checkedCheckboxStr += $(this).val()
                })

            if (!checkedCheckboxStr) {
                alert("请选择还款项目!");
                return false;
            }

            var reg = new RegExp("^" + checkedCheckboxStr);
            if (!reg.test(allCheckboxStr)) {
                alert("选择还款项目之前存在未还项目!");
                return false;
            }

            if (!confirm("确定强制还款么?")) {
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