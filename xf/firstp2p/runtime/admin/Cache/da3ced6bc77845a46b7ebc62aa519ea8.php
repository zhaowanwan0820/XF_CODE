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

<script type="text/javascript" src="__TMPL__Common/js/user_edit.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>

<script type="text/javascript" src="__TMPL__ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/ueditor.all.min.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/lang/zh-cn/zh-cn.js"></script>


<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<script type="text/javascript" src="__TMPL__widget/mulselect/cityData.js"></script>
<script type="text/javascript" src="__TMPL__widget/mulselect/mulselect.v1.js"></script>

<script type="text/javascript" src="//static.firstp2p.com/attachment/region.js?v=<?php echo app_conf('APP_SUB_VER'); ?>"></script>
<div class="main">
<div class="main_title"><?php echo L("EDIT");?>实名信息 <a href="<?php echo u("User/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form name="edit"  id="Jcarry_From_2" action="__APP__" method="post" enctype="multipart/form-data">

<div class="blank5"></div>
<table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>身份信息</b></td>
    </tr>
    <tr>
        <td class="item_title">姓名:</td>
        <td class="item_input"><input size="100" type="text" value="<?php echo ($vo["real_name"]); ?>" class="textbox" name="real_name" /></td>
    </tr>
    <tr>
        <td class="item_title">身份类型:</td>
        <td class="item_input">
        <select id="id_type" name="id_type">
        <option value="0">请选择</option>
        <?php if(is_array($idTypes)): foreach($idTypes as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if($vo["id_type"] == $key): ?>selected<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">证件号码:</td>
        <td class="item_input">
            <input type="text" value="<?php echo ($vo["idno"]); ?>" class="textbox" name="idno" <?php if($stock == 1): ?>disabled<?php endif; ?>/></td>
    </tr>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>

<div class="blank5"></div>
    <table class="form" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan=2 class="topTd"></td>
        </tr>
        <tr>
            <td class="item_title"></td>
            <td class="item_input">
            <!--隐藏元素-->
            <input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>" />
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="User" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="do_edit_identity" />
            <!--隐藏元素-->
            <input type="submit" class="button" value="<?php echo L("EDIT");?>" />
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