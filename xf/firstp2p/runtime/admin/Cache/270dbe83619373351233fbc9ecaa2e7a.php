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

<link rel="stylesheet" type="text/css" href="__TMPL__chosen/css/chosen.min.css" />
<script type="text/javascript" src="__TMPL__chosen/js/chosen.jquery.min.js"></script>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="search_row" style="background:none;color:black;">
    <form name="search" action="__APP__" method="get">
        管理员账号: <input type="text" name="adm_name" value="<?php echo trim($_REQUEST['adm_name']);?>"/> &nbsp;
        管理员组: <input type="text" name="role_name" value="<?php echo trim($_REQUEST['role_name']);?>"/> &nbsp;

        状态: <select name="effect_status" style="padding:2px 5px;">
                    <?php if(is_array($all_effect_status)): foreach($all_effect_status as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if(intval($_REQUEST['effect_status']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
                  </select> &nbsp;

        <input type="hidden" value="Admin" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>"/>
        <?php if($is_cn == 0): ?><input type="button" class="button" value="导出" onclick="exportCSV()" /><?php endif; ?>
    </form>
</div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
    <input type="button" class="button" value="<?php echo L("DEL");?>" onclick="del();" />
</div>
<div class="blank5"></div>
    <!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="11" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','Admin','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('adm_name','<?php echo ($sort); ?>','Admin','index')" title="按照<?php echo L("ADM_NAME");?><?php echo ($sortType); ?> "><?php echo L("ADM_NAME");?><?php if(($order)  ==  "adm_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('role_id','<?php echo ($sort); ?>','Admin','index')" title="按照<?php echo L("ROLE");?><?php echo ($sortType); ?> "><?php echo L("ROLE");?><?php if(($order)  ==  "role_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','Admin','index')" title="按照<?php echo L("IS_EFFECT");?><?php echo ($sortType); ?> "><?php echo L("IS_EFFECT");?><?php if(($order)  ==  "is_effect"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('name','<?php echo ($sort); ?>','Admin','index')" title="按照姓名<?php echo ($sortType); ?> ">姓名<?php if(($order)  ==  "name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('mobile','<?php echo ($sort); ?>','Admin','index')" title="按照手机号<?php echo ($sortType); ?> ">手机号<?php if(($order)  ==  "mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('login_time','<?php echo ($sort); ?>','Admin','index')" title="按照<?php echo L("LOGIN_TIME");?><?php echo ($sortType); ?> "><?php echo L("LOGIN_TIME");?><?php if(($order)  ==  "login_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('login_ip','<?php echo ($sort); ?>','Admin','index')" title="按照<?php echo L("LOGIN_IP");?><?php echo ($sortType); ?> "><?php echo L("LOGIN_IP");?><?php if(($order)  ==  "login_ip"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('password_update_time','<?php echo ($sort); ?>','Admin','index')" title="按照最后修改密码时间<?php echo ($sortType); ?> ">最后修改密码时间<?php if(($order)  ==  "password_update_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$admin): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($admin["id"]); ?>"></td><td>&nbsp;<?php echo ($admin["id"]); ?></td><td>&nbsp;<a href="javascript:edit('<?php echo (addslashes($admin["id"])); ?>')"><?php echo ($admin["adm_name"]); ?></a></td><td>&nbsp;<?php echo (get_role_name($admin["role_id"])); ?></td><td>&nbsp;<?php echo (get_is_effect($admin["is_effect"],$admin['id'])); ?></td><td>&nbsp;<?php echo ($admin["name"]); ?></td><td>&nbsp;<?php echo ($admin["mobile"]); ?></td><td>&nbsp;<?php echo (to_date($admin["login_time"])); ?></td><td>&nbsp;<?php echo ($admin["login_ip"]); ?></td><td>&nbsp;<?php echo (to_date($admin["password_update_time"])); ?></td><td><a href="javascript:edit('<?php echo ($admin["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a href="javascript: del('<?php echo ($admin["id"]); ?>')"><?php echo L("DEL");?></a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="11" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->

    <div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
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

<script>
/**
 * CSV导出，构造下载链接
 */
function exportCSV()
{
    var idBox = $(".key:checked"),
        id = '',
        action = $("form [name=a]"),
        curVal = action.val(),
        params = '';

    if(idBox.length > 0)
    {
        var idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    action.val('index');
    params = $('form').serialize();
    action.val(curVal);
    location.href = "/m.php?export=1&" + params + '&id=' + id;
}
</script>