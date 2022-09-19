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

<link rel="stylesheet" type="text/css" href="__ROOT__/static/admin/easyui/jquery.password.css" />
<link rel="stylesheet" type="text/css" href="__TMPL__chosen/css/chosen.min.css" />
<script type="text/javascript" src="__TMPL__chosen/js/chosen.jquery.min.js"></script>
<style type="text/css">
.pw_strengthIndicator .strength{
    font-weight:normal;
}
.strongy{
    float:left;
}
.pw_strengthIndicator{
    float:left;
    width:120px;
}
</style>
<div class="main">
<div class="main_title"><?php echo L("ADD");?> <a href="<?php echo u("Admin/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form name="edit" action="__APP__" method="post" enctype="multipart/form-data" id="adm_form">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("ADM_NAME");?>:</td>
        <td class="item_input"><input type="text" class="textbox require" name="adm_name" id="adm_name"/></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("ADM_PASSWORD");?>:</td>
        <td class="item_input">
            <input type="password" class="textbox require" name="adm_password" style="float:left;" />
            <div id="strongy" class="strongy"></div>
        </td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("ROLE");?>:</td>
        <td class="item_input">
            <select name="role_id" class="require" id="role_id">
                <option value="0">==<?php echo L("EMPTY_SELECT");?>==</option>
                <?php if(is_array($role_list)): foreach($role_list as $key=>$role_item): ?><option value="<?php echo ($role_item["id"]); ?>"><?php echo ($role_item["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("IS_EFFECT");?>:</td>
        <td class="item_input">
            <lable><?php echo L("IS_EFFECT_1");?><input type="radio" name="is_effect" value="1" checked="checked" /></lable>
            <lable><?php echo L("IS_EFFECT_0");?><input type="radio" name="is_effect" value="0" /></lable>
        </td>
    </tr>
    <tr>
        <td class="item_title">姓名:</td>
        <td class="item_input">
            <input type="text" class="textbox" name="name" id="name"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">手机号码:</td>
        <td class="item_input">
            <input type="text" class="textbox" name="mobile" id="mobile"/>
        </td>
    </tr>
    <tr>
        <td class="item_title"></td>
        <td class="item_input">
            <!--隐藏元素-->
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="Admin" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="insert" />
            <!--隐藏元素-->
            <input type="button" id="sub_btn" class="button" value="<?php echo L("ADD");?>" />
            <input type="reset" class="button" value="<?php echo L("RESET");?>" />
        </td>
    </tr>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>     
</form>
</div>
<script type="text/javascript" src="__ROOT__/static/admin/easyui/jquery.password.min.js"></script>
<script>
    var G_PASS = false;
    var pwdEx = /^(?=.{8,})((?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])).*$/g;
    $(function(){
        $('input[name="adm_password"]').password({
            minLength:8,
            strengthIndicator:$('#strongy'),
            change:function(score, issues, pass) {
                if(score>80){
                    G_PASS = true;
                }else{
                    G_PASS = false;
                }
            }
            }
        );
        $('#sub_btn').click(function(){
            if($('input[name="adm_password"]').val() == $('#adm_name').val()){
                alert('密码不能与用户名重复');
            }else{
                if(G_PASS == true){
                //if(pwdEx.test(pwd) == true){
                    $('#adm_form').submit();
                }else{
                    alert('密码长度不少于8位，必须为数字、大写字母、小写字母和特殊字符的组合');
                }
            }
        });
        $('#role_id').chosen();
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