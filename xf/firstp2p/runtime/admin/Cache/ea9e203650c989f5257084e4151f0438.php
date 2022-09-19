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
<style type="text/css">
.pw_strengthIndicator .strength{
    font-weight:normal;
}
.strongy{
    float:right;
}
.pw_strengthIndicator{
    float:left;
    width:120px;
}
</style>
<div class="main">
<div class="main_title"><?php echo L("CHANGE_PASSWORD");?></div>
<?php if($force): ?><div style="text-align: center;color: red;margin: 10px;font-weight: bold;">您的密码距离上次修改已超过90天，请修改密码后使用</div><?php endif; ?>
<div class="blank5"></div>
    <div class="change_password">
        <form id="pwd_form" name="edit" action="__APP__" method="post" enctype="multipart/form-data">
        <table class="form" cellpadding=0 cellspacing=0>
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("ADM_NAME");?>:</td>
                <td class="item_input" id="adm_name"><?php echo ($adm_data["adm_name"]); ?></td>
            </tr>
            <tr>
            <td class="item_title"><?php echo L("ADM_PASSWORD");?>:</td>
            <td class="item_input"><input type="password" class="textbox require" name="adm_password" /></td>
            </tr>
            <tr>
            <td class="item_title"><?php echo L("ADM_NEW_PASSWORD");?>:</td>
            <td class="item_input"><input type="password" class="textbox require" name="adm_new_password" />
            <div id="strongy1" class="strongy"></div>
            </td>
            </tr>
            <tr>
            <td class="item_title"><?php echo L("ADM_CONFIRM_PASSWORD");?>:</td>
            <td class="item_input"><input type="password" class="textbox require" name="adm_confirm_password" />
            <div id="strongy2" class="strongy"></div>
            </td>
            </tr>
            <tr>
                <td class="item_title"></td>
                <td class="item_input">
                    <!--隐藏元素-->
                    <input type="hidden" name="adm_id" value="<?php echo ($adm_data["adm_id"]); ?>" />
                    <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="Index" />
                    <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="do_change_password" />
                    <!--隐藏元素-->
                    <input type="button" id="pwd_btn" class="button" value="<?php echo L("CHANGE");?>" />
                    <input type="reset" class="button" value="<?php echo L("RESET");?>" />
                </td>
            </tr>
            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr>
        </table>
        </form>
    </div>
</div>
<script type="text/javascript" src="__ROOT__/static/admin/easyui/jquery.password.min.js"></script>
<script>
    var G_PASS = false;
    var pwdEx = /^(?=.{8,})((?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])).*$/g;
    $(function(){
        $('input[name="adm_confirm_password"]').password({
            minLength:8,
            strengthIndicator:$('#strongy2'),
            }
        );
        $('input[name="adm_new_password"]').password({
            minLength:8,
            strengthIndicator:$('#strongy1'),
            change:function(score, issues, pass) {
                if(score>80){
                    G_PASS = true;
                }else{
                    G_PASS = false;
                }
            }
            }
        );
        $('#pwd_btn').click(function(){
            var pwd = $('input[name="adm_new_password"]').val();
            var repwd = $('input[name="adm_confirm_password"]').val();
            if(pwd == repwd){
                if($('#adm_name').html() == pwd){
                    alert('密码不能与用户名重复');
                }else{
                    if(G_PASS == true){
                    //if(pwdEx.test(pwd) == true){
                        $('#pwd_form').submit();
                    }else{
                        alert('密码长度不少于8位，必须为数字、大写字母、小写字母和特殊字符的组合');
                    }
                }
            }else{
                alert('您输入的密码不相同');
            }
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