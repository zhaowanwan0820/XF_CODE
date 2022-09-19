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
<script type="text/javascript" src="__TMPL__Common/js/input-click.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<div class="main">
    <div class="main_title">标的放款批量查询</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="post" id="batch">

        标的编号：
        <textarea name="deal_ids" rows="3" cols="25"><?php echo ($_REQUEST['deal_ids']); ?></textarea>

        放款审批单号：
        <textarea name="approve_numbers" rows="3" cols="25"><?php echo ($_REQUEST['approve_numbers']); ?></textarea>

        <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
        <input type="hidden" value="DealLoan" name="m" />
        <input type="hidden" value="batchQuery" name="a" />
        <input type="hidden" value="0" name="export" id="export" />
        <input type="button" class="button" value="<?php echo L("SEARCH");?>" onclick="batch_search();" />
        <input type="button" class="button" value="导出" onclick="export_csv();" />
    </form>
</div>
<div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="22" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th>借款编号</th>
            <th>贷款类型</th>
            <th>放款审批单号</th>
            <th>借款标题</th>
            <th>标的状态</th>
            <th>标的创建时间</th>
            <th>提现外部订单号</th>
            <th>提现金额</th>
            <th>提现状态</th>
            <th>提现创建时间</th>
            <th>提现更新时间</th>
            <th>提现备注</th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$deal): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>
                &nbsp;<?php echo ($deal["id"]); ?>
            </td>
            <td>
                <?php if($deal["deal_type"] == 3): ?>&nbsp;专享<?php endif; ?>
                <?php if($deal["deal_type"] == 0): ?>&nbsp;网贷<?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["approve_number"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["name"]); ?>
            </td>
            <td>
                &nbsp;<?php echo (a_get_buy_status($deal["deal_status"],$deal.id)); ?>
                <?php if(($deal["deal_status"] == 4) && ($deal["is_has_loans"] == 2)): ?>- 正在放款<?php endif; ?>
                <?php if($deal["is_during_repay"] == 1): ?>- 正在还款<?php endif; ?>
                <?php if(($deal["deal_status"] == 3) && ($deal["is_doing"] == 1)): ?>- 正在流标<?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo to_date($deal['create_time']);?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["withdraw_out_order_id"]); ?>
            </td>
            <td>
                <?php if($deal["withdraw_amount"] > 0): ?>&nbsp;<?php echo ($deal["withdraw_amount"]); ?>元<?php endif; ?>
            </td>
            <td>
                <?php if($deal["withdraw_status"] != ''): ?><?php if($deal["withdraw_status"] == 0): ?>&nbsp;未处理<?php endif; ?>
                    <?php if($deal["withdraw_status"] == 1): ?>&nbsp;提现成功<?php endif; ?>
                    <?php if($deal["withdraw_status"] == 2): ?>&nbsp;提现失败<?php endif; ?>
                    <?php if($deal["withdraw_status"] == 3): ?>&nbsp;提现处理中<?php endif; ?><?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo date('Y-m-d H:i:s', $deal['withdraw_create_time']);?>
            </td>
            <td>
                &nbsp;<?php echo date('Y-m-d H:i:s', $deal['withdraw_update_time']);?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["withdraw_remark"]); ?>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>

    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
<script type="text/javascript">
function batch_search() {
    $('#export').attr('value', 0);
    $('#batch').submit();
}
function export_csv(){
    $('#export').attr('value', 1);
    $('#batch').submit();
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