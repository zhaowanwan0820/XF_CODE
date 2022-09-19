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

<div class="main">
<script type="text/javascript">

</script>
<div class="main_title"><?php echo ($user_name); ?>-重置密码</div>
<div class="blank5"></div>
<form name="edit" action="__APP__" method="post" enctype="multipart/form-data" onsubmit="return check_incharge_form();">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_MOBILE");?>:</td>
        <td class="item_input">
            <select name="country_code" id="country_code" class="require">
                <?php if(is_array($mobile_code_list)): foreach($mobile_code_list as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>"  <?php if($mobile_code == $mobile_code_item['code']): ?>selected="selected"<?php endif; ?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
            - <input type="text" class="textbox" name="mobile" value="<?php echo ($mobile); ?>" <?php if($readonly == true): ?>readonly<?php endif; ?> size="13"/>
            <div class='tip_span'>如需修改，请重新填写</div>
        </td>
    </tr>

    <tr>
        <td class="item_title">&nbsp;</td>
        <td class="item_input">
            <!--隐藏元素-->
            <input type="hidden" name="id" value="<?php echo ($id); ?>" />
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="User" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="do_edit_password" />
            <!--隐藏元素-->
            <input type="submit" class="button" value="<?php echo L("OK");?>" onclick='return confirm("确认提交？")'/>
            <input type="reset" class="button" value="<?php echo L("RESET");?>" />
        </td>
    </tr>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>     
</form>
</div>