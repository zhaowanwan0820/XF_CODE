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

<script type="text/javascript" src="__TMPL__Common/js/user.field.js"></script>
<div class="main">
    <div class="main_title">新增网贷调账申请 <a href="<?php echo u('LoanAccountAdjustMoney/index');?>" class="back_list">返回网贷申请列表</a></div>
    <div class="blank5"></div>
    <form name="doadd" action="__APP__" method="post" enctype="multipart/form-data">
        <table class="form" cellpadding=0 cellspacing=0>
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>
            <tr>
                <td class="item_title">用户ID:</td>
                <td class="item_input"><input type="text" class="textbox require" name="user_id" onblur="getInfo(this.value)">
                </td>
            </tr>

            <tr>
                <td class="item_title">姓名：</td>
                <td class="item_input">
                    <input id="user_name" name="user_name" readonly="readonly" />
                </td>
            </tr>

            <tr>
                <td class="item_title">网贷账户余额：</td>
                <td class="item_input">
                    <input id="account_money" name="account_money" readonly="readonly" />
                </td>
            </tr>

            <tr>
                <td class="item_title">调账金额:</td>
                <td class="item_input"><input type="text" class="textbox require" name="money" />
                </td>
            </tr>

            <tr>
                <td class="item_title">类型:</td>
                <td class="item_input">
                    <select name="type" id="js_type">
                        <?php if(is_array($loan_account_adjust_money_type)): foreach($loan_account_adjust_money_type as $key=>$type): ?><option value="<?php echo ($key); ?>"><?php echo ($type); ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="item_title">备注:</td>
                <td class="item_input"><textarea class="textbox" name="note"></textarea>
                </td>
            </tr>

            <tr>
                <td class="item_title"></td>
                <td class="item_input">
                    <!--隐藏元素-->
                    <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="LoanAccountAdjustMoney" />
                    <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="doadd" />
                    <!--隐藏元素-->
                    <input type="submit" class="button" value="<?php echo L("ADD");?>" />
                    <input type="reset" class="button" value="<?php echo L("RESET");?>" />
                </td>
            </tr>
            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr>
        </table>
    </form>
</div>

<script>
    function getInfo(user_id)
    {
        $.ajax({
            type: "POST",//方法类型
            url: "/m.php?m=LoanAccountAdjustMoney&a=get_info",//url
            data: {"user_id": user_id},
            dataType: "json",//预期服务器返回的数据类型
            success: function (result) {
                console.log(result);
                if (result.status == 1) {
                    $("#user_name").val(result.user_name);
                    $("#account_money").val(result.money);
                }
            }
        });
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