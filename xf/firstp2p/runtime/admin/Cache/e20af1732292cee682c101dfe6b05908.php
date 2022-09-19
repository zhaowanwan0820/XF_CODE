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
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
    <input type="button" class="button" value="<?php echo L("FOREVERDEL");?>" onclick="foreverdel();" />
    <input type="button" class="button" value="导出所有记录" onclick="location.href='?m=UserGroup&a=export_csv';" />
</div>
<div class="blank5"></div>

<div class="search_row">
    <form name="search" action="__APP__" method="get">
        会员组名称：<input type="text" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" />
        分属政策组：
        <select name="basic_group_id" >
            <option value="">==未选政策组==</option>
            <?php if(is_array($basic_groups)): foreach($basic_groups as $dkey=>$item): ?><option value="<?php echo ($dkey); ?>" <?php if($dkey == $_REQUEST['basic_group_id']): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        服务标识：
        <select name="service_status" id="service_status">
            <option <?php if($_REQUEST['service_status'] == 'all' || trim($_REQUEST['service_status']) == ''): ?>selected="selected"<?php endif; ?> value="all">全部</option>
            <option <?php if(intval($_REQUEST['service_status']) == 1): ?>selected="selected"<?php endif; ?> value="1">有效</option>
            <option <?php if($_REQUEST['service_status'] != 'all' && trim($_REQUEST['service_status']) != '' && intval($_REQUEST['service_status']) == 0): ?>selected="selected"<?php endif; ?> value="0">无效</option>
        </select>
        会员组状态：
        <select name="is_effect" id="is_effect">
            <option <?php if($_REQUEST['is_effect'] == 'all' || trim($_REQUEST['is_effect']) == ''): ?>selected="selected"<?php endif; ?> value="all">全部</option>
            <option <?php if(intval($_REQUEST['is_effect']) == 1): ?>selected="selected"<?php endif; ?> value="1">有效</option>
            <option <?php if($_REQUEST['is_effect'] != 'all' && trim($_REQUEST['is_effect']) != '' && intval($_REQUEST['is_effect']) == 0): ?>selected="selected"<?php endif; ?> value="0">无效</option>
        </select>
        机构/打包比例：
        <select name="pack_ratio" >
            <option value="-1" <?php if($_REQUEST['pack_ratio'] == -1): ?>selected="selected"<?php endif; ?>>全部</option>
            <?php if(is_array($pack_ratio)): foreach($pack_ratio as $key=>$item): ?><option value="<?php echo ($item); ?>" <?php if($item == $_REQUEST['pack_ratio']): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        打包比例上限：
        <select name="max_pack_ratio" >
            <option value="-1" <?php if($_REQUEST['max_pack_ratio'] == -1): ?>selected="selected"<?php endif; ?>>全部</option>
            <?php if(is_array($max_pack_ratio)): foreach($max_pack_ratio as $key=>$item): ?><option value="<?php echo ($item); ?>" <?php if($item == $_REQUEST['max_pack_ratio']): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        <input type="hidden" value="UserGroup" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<div class="blank5"></div>

<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="12" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','UserGroup','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('name','<?php echo ($sort); ?>','UserGroup','index')" title="按照<?php echo L("USER_GROUP_NAME");?><?php echo ($sortType); ?> "><?php echo L("USER_GROUP_NAME");?><?php if(($order)  ==  "name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('service_status','<?php echo ($sort); ?>','UserGroup','index')" title="按照服务标识<?php echo ($sortType); ?> ">服务标识<?php if(($order)  ==  "service_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('prefix','<?php echo ($sort); ?>','UserGroup','index')" title="按照邀请码前缀<?php echo ($sortType); ?> ">邀请码前缀<?php if(($order)  ==  "prefix"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','UserGroup','index')" title="按照会员组状态<?php echo ($sortType); ?> ">会员组状态<?php if(($order)  ==  "is_effect"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('basic_group_name','<?php echo ($sort); ?>','UserGroup','index')" title="按照所属政策组<?php echo ($sortType); ?> ">所属政策组<?php if(($order)  ==  "basic_group_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_user_name','<?php echo ($sort); ?>','UserGroup','index')" title="按照绑定用户<?php echo ($sortType); ?> ">绑定用户<?php if(($order)  ==  "agency_user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('pack_ratio','<?php echo ($sort); ?>','UserGroup','index')" title="按照机构/打包比例<?php echo ($sortType); ?> ">机构/打包比例<?php if(($order)  ==  "pack_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('max_pack_ratio','<?php echo ($sort); ?>','UserGroup','index')" title="按照打包比例上限<?php echo ($sortType); ?> ">打包比例上限<?php if(($order)  ==  "max_pack_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_related','<?php echo ($sort); ?>','UserGroup','index')" title="按照是否联动<?php echo ($sortType); ?> ">是否联动<?php if(($order)  ==  "is_related"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$group): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($group["id"]); ?>"></td><td>&nbsp;<?php echo ($group["id"]); ?></td><td>&nbsp;<a href="javascript:edit('<?php echo (addslashes($group["id"])); ?>')"><?php echo ($group["name"]); ?></a></td><td>&nbsp;<?php echo ($group["service_status"]); ?></td><td>&nbsp;<?php echo ($group["prefix"]); ?></td><td>&nbsp;<?php echo ($group["is_effect"]); ?></td><td>&nbsp;<?php echo ($group["basic_group_name"]); ?></td><td>&nbsp;<?php echo ($group["agency_user_name"]); ?></td><td>&nbsp;<?php echo ($group["pack_ratio"]); ?></td><td>&nbsp;<?php echo ($group["max_pack_ratio"]); ?></td><td>&nbsp;<?php echo ($group["is_related"]); ?></td><td><a href="javascript:edit('<?php echo ($group["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a href="javascript: foreverdel('<?php echo ($group["id"]); ?>')"><?php echo L("FOREVERDEL");?></a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="12" class="bottomTd"> &nbsp;</td></tr></table>
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