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

<div class="main">
    <div class="main_title">
        <label>编辑客户投资</label>
        <a href="<?php echo u("ExchangeLoad/index?batch_id=". $loadInfo['batch_id']);?>" class="back_list"><?php echo L("BACK_LIST");?></a>
    </div>
    <div class="blank5"></div>

    <form name="edit" action="<?php echo u("ExchangeLoad/save");?>" method="post" enctype="multipart/form-data">
        <table class="form" cellpadding=0 cellspacing=0>
            <tr>
                <td colspan=2 class="topTd">
                    <input type="hidden" name="id" value="<?php echo ($loadInfo['id']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">用户名称:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="real_name" value="<?php echo ($loadInfo['real_name']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">身份证类型:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="certificate_type" value="<?php echo ($loadInfo['certificate_type']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">证件号:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="certificate_no" value="<?php echo ($loadInfo['certificate_no']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">手机号:</td>
                <td class="item_input">
                   <input type="text" class="textbox" name="mobile" value="<?php echo ($loadInfo['mobile']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">银行卡号:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="bank_no" value="<?php echo ($loadInfo['bank_no']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">开户行名称:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="bank_name" value="<?php echo ($loadInfo['bank_name']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">联行号:</td>
                <td class="item_input">
                   <input type="text" class="textbox" name="cnaps_no" value="<?php echo ($loadInfo['cnaps_no']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">开户行所在省:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="bank_province" value="<?php echo ($loadInfo['bank_province']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">开户行所在市:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="bank_city" value="<?php echo ($loadInfo['bank_city']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">邀请码:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="invite_code" value="<?php echo ($loadInfo['invite_code']); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">认购金额:</td>
                <td class="item_input">
                   <input type="text" class="textbox require" name="pay_money" value="<?php echo sprintf("%.2f", $loadInfo['pay_money'] / 100);?>" readonly="readonly"/>
                </td>
            </tr>
            <tr>
                <td class="item_title">打款时间:</td>
                <td class="item_input">
                  <input type="text" class="textbox require" name="pay_time" id="pay_time" value="<?php echo date('Y-m-d', $loadInfo['pay_time']);?>" />
                  <input type="button" class="button" id="btn_pay_time" value="选择时间" onclick="return showCalendar('pay_time', '%Y-%m-%d', false, false, 'btn_pay_time');" />
                </td>
            </tr>
            <tr>
                <td class="item_title"></td>
                <td class="item_input">
                    <input type="submit" class="button" value="<?php echo L("EDIT");?>" />
                    <input type="reset" class="button" value="<?php echo L("RESET");?>" />
                </td>
            </tr>
            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr>
        </table>
    </form>
    <div class="blank5"></div>

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