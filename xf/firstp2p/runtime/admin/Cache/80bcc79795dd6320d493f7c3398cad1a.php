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
<div class="main_title">批量导入网贷调账申请 <a href="<?php echo u('LoanAccountAdjustMoney/index');?>" class="back_list">返回网贷调账管理列表</a></div>
<div class="blank5"></div>
<form name="upload" action="__APP__" method="post" enctype="multipart/form-data">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">选择CSV文件</td>
        <td class="item_input">
            <input type='file' name='upfile' style='width:150px'>
        </td>
    </tr>
    <tr>
        <td class="item_title"></td>
        <td class="item_input">
            <span class='tip_span'>
                (文件格式为csv, 表格首行为"会员名称，姓名，调账金额，类型（1提现充值失败 2系统修正）,备注"
                <a href="/static/admin/Common/images/upload_tpl_loan_account_adjust_money.csv" target="_blank">下载模板</a>)
            </span>
        </td>
    </tr>
    <tr>
        <td class="item_title"></td>
        <td class="item_input">
            <!--隐藏元素-->
            <!-- <input type="hidden" name="user_id" value="<?php echo ($user_info["id"]); ?>" /> -->
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="LoanAccountAdjustMoney" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="doimport" />
            <!--隐藏元素-->
            <input type="submit" class="button" value="导入" />
        </td>
    </tr>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>
</form>
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