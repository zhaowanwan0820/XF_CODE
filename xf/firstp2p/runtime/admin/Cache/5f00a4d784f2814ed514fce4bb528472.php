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
<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/user.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<style type="text/css">
.require1 { border-left:4px solid red;}
</style>

<?php function getBusType($type)
    {
        switch($type)
        {
            case 'COMMON_LARGE':
            case 'LARGE':
                return '大额转账充值';
            case 'OFFLINE':
                return '后台转账充值';
            default:
                return '未知';
        }
    }

    function getRefundStatus($status)
    {
        switch ($status)
        {
            case 'unrefund':
                return '未退款';
            case 'refund_checking':
                return '退款审核中';
            case 'refunding':
                return '退款中';
            case 'refund_succ':
                return '退款成功';
            case 'refund_fail':
                return '退款失败';
            case 'no_need_refund':
                return '无需退款';
            default:
                return '未知';
        }
    }


    function getStatus($status)
    {
        switch ($status)
        {
            case 'ready':
                return '待匹配';
            case 'success':
                return '匹配成功';
            default:
                return '未知';
        }
    } ?>
<div class="main">
<div class="main_title">资金流水查询（只能查询近10天的数据）</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        商户号：<input type="text" class="textbox" placeholder="商户号" name="merchantId" id="searchMerchantId" value="<?php echo ($merchantId); ?>" style="width:100px;" />
        商户订单号：<input type="text" class="textbox" placeholder="商户订单号" name="outOrderId" value="<?php echo trim($_REQUEST['outOrderId']);?>" style="width:100px;" />
        到账起始时间：<input type="text" class="textbox" name="transStartTime" id="transStartTime" value="<?php echo ($transStartTime); ?>" style="width:100px;" onfocus="return showCalendar('transStartTime', '%Y%m%d%H%M%S', false, false, 'transStartTime');" style="width:150px;" onclick="return showCalendar('transStartTime', '%Y%m%d%H%M%S', false, false, 'transStartTime');"/>
        到账结束时间：<input type="text" class="textbox" name="transEndTime" id="transEndTime" value="<?php echo ($transEndTime); ?>" style="width:100px;"  onfocus="return showCalendar('transEndTime', '%Y%m%d%H%M%S', false, false, 'transEndTime');" style="width:150px;" onclick="return showCalendar('transEndTime', '%Y%m%d%H%M%S', false, false, 'transEndTime');"/>
        付款方姓名：<input type="text" class="textbox" placeholder="付款方姓名" name="payAccountName" value="<?php echo trim($_REQUEST['payAccountName']);?>" style="width:100px;" />
        付款方账号：<input type="text" class="textbox" placeholder="付款方账号" name="payAccountNo" value="<?php echo trim($_REQUEST['payAccountNo']);?>" style="width:100px;" />
        金额：<input type="text" class="textbox" placeholder="金额" name="amount" value="<?php echo trim($_REQUEST['amount']);?>" style="width:100px;" />
        备付金账号：<input type="text" class="textbox" placeholder="备付金账号" name="accountNo" value="<?php echo trim($_REQUEST['accountNo']);?>" style="width:100px;" />
        匹配状态：
        <select name="status" width="100px;">
            <option value="">全部</option>
            <option value="READY" <?php if ($_REQUEST['status'] == 'READY') echo "selected"; ?>>待匹配</option>
            <option value="SUCCESS" <?php if ($_REQUEST['status'] == 'SUCCESS') echo "selected"; ?>>匹配成功</option>
        </select>
        上账起始时间：<input type="text" class="textbox" name="accountStartDate" id="accountStartDate" value="<?php echo ($accountStartDate); ?>" style="width:100px;" onfocus="return showCalendar('accountStartDate', '%Y%m%d%H%M%S', false, false, 'accountStartDate');" style="width:150px;" onclick="return showCalendar('accountStartDate', '%Y%m%d%H%M%S', false, false, 'accountStartDate');"/>
        上账结束时间：<input type="text" class="textbox" name="accountEndDate" id="accountEndDate" value="<?php echo ($accountEndDate); ?>" style="width:100px;"  onfocus="return showCalendar('accountEndDate', '%Y%m%d%H%M%S', false, false, 'accountEndDate');" style="width:150px;" onclick="return showCalendar('accountEndDate', '%Y%m%d%H%M%S', false, false, 'accountEndDate');"/>

        <input type="hidden" value="TransferOrderQuery" name="m" />
        <input type="hidden" value="accountRecords" name="a" />
        <input type="SUBMIT" id="subBtn" class="button searchBtn" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<div class="blank5"></div>
    <table class="dataTable">
    <tr>
    <th>流水id</th>
    <th>到账时间</th>
    <th>付款方账户号</th>
    <th>付款方账户名</th>
    <th>付款方银行</th>
    <th>金额</th>
    <th>备付金账户</th>
    <th>商户订单号</th>
    <th>匹配状态</th>
    <th>退款状态</th>
    <th>上账时间</th>
    <th>备注</th>
    <th>借款人备注</th>
    <th>业务类型</th>
    </tr>
<?php if($list['pageCnt'] > 0): ?><?php if(is_array($list["pageList"])): foreach($list["pageList"] as $key=>$item): ?><tr>
        <td><?php echo ($item["id"]); ?></td>
        <td><?php echo ($item["transTime"]); ?></td>
        <td><?php echo ($item["payAccountNo"]); ?></td>
        <td><?php echo ($item["payAccountName"]); ?></td>
        <td><?php echo ($item["bankName"]); ?></td>
        <td><?php echo bcdiv($item['amount'], 100, 2)?></td>
        <td><?php echo ($item["accountNo"]); ?></td>
        <td><?php echo ($item["outOrderId"]); ?></td>
        <td><?php echo getStatus($item['status']);?></td>
        <td><?php echo getRefundStatus($item['refundStatus']);?></td>
        <td><?php echo ($item["accountTime"]); ?></td>
        <td><?php echo ($item["remark"]); ?></td>
        <td><?php echo ($item["payRemark"]); ?></td>
        <td><?php echo getBusType($item['rechargeType']);?></td>
    </tr><?php endforeach; endif; ?>
<?php else: ?>
<tr> <td colspan="14" textalign="center">暂无数据</td></tr><?php endif; ?>

<?php
    $request = $_REQUEST;
    $pageNext = isset($_REQUEST['pageNo']) ? intval($request['pageNo']) + 1 : 2;
    $request['pageNo'] = $pageNext;
    $urlNext = http_build_query($request);
    $pagePrev = isset($_REQUEST['pageNo']) ? (intval($request['pageNo']) - 2 > 0 ? intval($request['pageNo']) - 2 : 1) : 1;
    $request['pageNo'] = $pagePrev;
    $urlPrev = http_build_query($request);
?>
<tr>
    <td colspan="14" textalign="right"> <a href="/m.php?m=TransferOrderQuery&a=accountRecords&<?php echo $urlPrev;?>">上一页</a>&nbsp;<a  href="/m.php?m=TransferOrderQuery&a=accountRecords&<?php echo $urlNext;?>">下一页</a>
</tr>

</table>
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