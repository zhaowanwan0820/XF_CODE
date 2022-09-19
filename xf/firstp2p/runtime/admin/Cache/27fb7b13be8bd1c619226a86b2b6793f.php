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
<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>
<div class="main">
<div class="main_title">先锋支付资金账户对账</div>
<div class="blank5"></div>

<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
    <tr><td colspan="10" class="topTd" >&nbsp;</td></tr>
    <!--转账-->
    <tr class="row" >
        <th>转账队列</th>
        <td>
            <div style="margin:10px;">
                <a href="?m=FinanceQueue" target="_blank">查看转账队列状态</a> &nbsp;
                <a href="?m=FinanceQueue&time_start=<?php echo date('Y-m-d', time() - 86400); ?>" target="_blank">昨日状态</a>
            </div>
        </td>
    </tr>
    <!--批量用户余额对账-->
    <tr class="row" >
        <th>用户余额对账</th>
        <td>
            <form action="__APP__" method="post" target="_blank" name="search">
                <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="PaymentCheck" />
                <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="userBalance" />
                输入用户ID:
                <span class='tip_span'>(支持多个用户ID、用户名、手机号)</span>
                <div class="blank5"></div>
                <textarea name="ids" style="width:500px;height:100px;"></textarea>
                <div class="blank5"></div>
                <input type="submit" class="button" value="提交" />
            </form>
        </td>
    </tr>
    <!--用户订单对账-->
    <tr class="row" >
        <th width="150">用户订单对账</th>
        <td>
            <form action="__APP__" method="get" target="_blank" name="search">
                <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="PaymentCheck" />
                <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="userOrder" />
                输入用户ID:
                <div class="blank5"></div>
                <input name="id" value="" />
                <div class="blank5"></div>
                <input type="submit" class="button" value="提交" />
            </form>
        </td>
    </tr>
    <!--可信查询-->
    <tr class="row" >
        <th>用户可信查询</th>
        <td>
            <form action="__APP__" method="get" target="_blank" name="search">
                <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="PaymentCheck" />
                <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="credible" />
                输入用户ID:
                <span class='tip_span'>(支持多个用户ID、用户名、手机号)</span>
                <div class="blank5"></div>
                <input name="ids" value="" />
                <div class="blank5"></div>
                <input type="submit" class="button" value="提交" />
            </form>
        </td>
    </tr>
    <!--存管批量拆单补单-->
    <tr class="row" >
        <th>批量拆单补redis数据</th>
        <td>
          <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="PaymentCheck" />
          <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="orderSplitRetry" />
          输入外部交易流水号:
          <div class="blank5"></div>
          <input id='orderSplitId' name='orderSplitId' value='' style="height:20px"/>
          <div class="blank5"></div>
          <button class="button" id="orderSplitBtn" type="button">提交
        </td>
    </tr>
    <tr><td colspan="10" class="bottomTd">&nbsp; </td></tr>
</table>
<div class="main_title">易宝资金账户对账</div>
<div class="blank5"></div>
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
    <tr><td colspan="10" class="topTd" >&nbsp;</td></tr>
    <!--批量用户绑卡查询-->
    <tr class="row" >
        <th width="11%">批量用户绑卡查询</th>
        <td>
            <form action="__APP__" method="post" target="_blank" name="bindcardquery">
                <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="PaymentCheck" />
                <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="userBindcardQuery" />
                输入用户ID:
                <span class='tip_span'>(支持多个用户ID、用户名、手机号)</span>
                <div class="blank5"></div>
                <textarea name="ids" style="width:500px;height:100px;"></textarea>
                <div class="blank5"></div>
                <input type="submit" class="button" value="提交" />
            </form>
        </td>
    </tr>
    <!--易宝补单重试列表-->
    <tr class="row" >
        <th width="11%">易宝补单重试列表</th>
        <td>
            <button class="button" id="clearRetryListBtn" type="button">清空易宝补单列表</a>
        </td>
    </tr>
</table> 
<div class="blank5"></div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $('#clearRetryListBtn').click(function(){
            if (confirm('陛下，您确定要清空易宝补单列表吗？万万不可啊，三思啊'))
            {
                $("#clearRetryListBtn").css({"color":"gray","background":"#ccc"}).attr("disabled","disabled");
                $.post('/m.php?m=PaymentCheck&a=clearRetryList', {}, function(response) {
                    var rs = $.parseJSON(response);
                    alert(rs.msg);
                    $("#clearRetryListBtn").css({ "color": "#fff", "background-color": "#4E6A81"}).removeAttr("disabled");
                },
                'JSON');
            }
        });
        $('#orderSplitBtn').click(function() {
             $("#orderSplitBtn").css({"color":"gray","background":"#ccc"}).attr("disabled","disabled");
             $.post('/m.php?m=PaymentCheck&a=orderSplitRetry', {ids:$("#orderSplitId").val()}, function(response) {
                 var rs = $.parseJSON(response);
                 alert(rs.msg);
                 console.log(rs.data);
                 $("#orderSplitBtn").css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
             },
             'JSON');
        });
    });
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