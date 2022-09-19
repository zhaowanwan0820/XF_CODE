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
<script>
var clickNum = 0;
$(document).ready(function(){
    $("#doVerify").click(function(){
        if (clickNum > 0) {
            alert("请耐心等待验证结果，不要连续提交！");
            return false;
        }
        $.post("/m.php?m=IDVerify&a=index", $("#verify_form").serialize(),function(rs){
            var rs = $.parseJSON(rs);
            alert(rs.info);
            clickNum = 0;
        });
        clickNum++;
        return false;
    });
});
</script>
<div class="main">
<div class="main_title">身份证验证</div>
<div class="blank5"></div>
<form name="verify_form" id="verify_form" action="__APP__" method="post" enctype="multipart/form-data">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr><td colspan=2 style="color:red">友情提示：身份证验证价格不菲，约每验证一次消耗一瓶可乐，请节约使用哈！</td></tr>
    <tr>
        <td class="item_title">选择验证接口</td>
        <td class="item_input">
        <select name="verify_type">
        <?php if(is_array($verifyTypes)): foreach($verifyTypes as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if(($key == 1)): ?>selected<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">输入姓名:</td>
        <td class="item_input"><input type="text" class="textbox" name="name" />
        </td>
    </tr>
    <tr>
        <td class="item_title">输入身份证号:</td>
        <td class="item_input"><input type="text" class="textbox" name="idno" />
        </td>
    </tr>
    <tr>
        <td class="item_title">查询原因:</td>
        <td class="item_input"><input type="text" class="textbox" name="reason" />
        </td>
    </tr>
    <tr>
        <td class="item_title"></td>
        <td class="item_input">
            <input id="doVerify" type="button" class="button" value="验证"/>
            <input type="reset" class="button" value="<?php echo L("RESET");?>" />
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