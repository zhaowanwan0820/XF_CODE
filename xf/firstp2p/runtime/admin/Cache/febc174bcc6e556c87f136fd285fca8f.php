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
	<input type="button" class="button" value="立即发布" onclick="location.href='m.php?m=Conf&a=setLastUpdateTime';" />
    最后发布时间：<?php echo date('Y-m-d H:i:s', $lastUpdateTime); ?>
</div>
<div class="blank5"></div>
<div class="search_row">
	<form name="search" action="__APP__" method="get">
        <?php echo L("CONF_SITE_ID");?>：
        <select name="site_id">
            <?php if(is_array($site_list)): foreach($site_list as $site_id=>$site_name): ?><option value="<?php echo ($site_id); ?>" <?php if($_REQUEST['site_id'] == $site_id): ?>selected="selected"<?php endif; ?>>
                <?php echo ($site_name); ?>
                </option><?php endforeach; endif; ?>
        </select>
        <?php echo L("CONF_TITLE");?>：<input type="text" class="textbox" name="title" value="<?php echo trim($_REQUEST['title']);?>" />
        <input type="hidden" value="Conf" name="m" />
		<input type="hidden" value="index" name="a" />
		<input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
	</form>
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan="9" class="topTd">&nbsp;</td>
    </tr>
    <tr class="row">
        <th width="8"><input type="checkbox" id="check"
            onclick="CheckAll('dataTable')"></th>
        <th width="50px  "><a
            href="javascript:sortBy('id','<?php echo ($sort); ?>','Conf','index')"
            title="按照<?php echo L("ID");?>
                <?php echo ($sortType); ?> "><?php echo L("ID");?>
                <?php if(($order)  ==  "id"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                width="12" height="17" border="0" align="absmiddle">
            <?php endif; ?>
        </a></th>
        <th><a
            href="javascript:sortBy('title','<?php echo ($sort); ?>','Conf','index')"
            title="按照<?php echo L("CONF_TITLE");?> <?php echo ($sortType); ?>
                "><?php echo L("CONF_TITLE");?> <?php if(($order)  ==  "title"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                width="12" height="17" border="0" align="absmiddle">
            <?php endif; ?></a></th>
        <th><a
            href="javascript:sortBy('name','<?php echo ($sort); ?>','Conf','index')"
            title="按照<?php echo L("CONF_NAME");?> <?php echo ($sortType); ?>
                "><?php echo L("CONF_NAME");?> <?php if(($order)  ==  "name"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                width="12" height="17" border="0" align="absmiddle">
            <?php endif; ?></a></th>
        <th width="150px  "><a
            href="javascript:sortBy('value','<?php echo ($sort); ?>','Conf','index')"
            title="按照<?php echo L("CONF_VALUE");?>
                <?php echo ($sortType); ?> "><?php echo L("CONF_VALUE");?>
                <?php if(($order)  ==  "value"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                width="12" height="17" border="0" align="absmiddle">
            <?php endif; ?>
        </a></th>
        <th><a
            href="javascript:sortBy('site_id','<?php echo ($sort); ?>','Conf','index')"
            title="按照<?php echo L("CONF_SITE_ID");?> <?php echo ($sortType); ?>
                "><?php echo L("CONF_SITE_ID");?> <?php if(($order)  ==  "site_id"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                width="12" height="17" border="0" align="absmiddle">
            <?php endif; ?></a></th>
        <th><a
            href="javascript:sortBy('input_type','<?php echo ($sort); ?>','Conf','index')"
            title="按照<?php echo L("CONF_INPUT_TYPE");?> <?php echo ($sortType); ?>
                "><?php echo L("CONF_INPUT_TYPE");?> <?php if(($order)  ==  "input_type"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                width="12" height="17" border="0" align="absmiddle">
            <?php endif; ?></a></th>
        <th><a
            href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','Conf','index')"
            title="按照<?php echo L("IS_EFFECT");?>
                <?php echo ($sortType); ?> "><?php echo L("IS_EFFECT");?>
                <?php if(($order)  ==  "is_effect"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                width="12" height="17" border="0" align="absmiddle">
            <?php endif; ?>
        </a></th>
        <th style="width:">操作</th>
    </tr>
    <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
        <td><input type="checkbox" name="key" class="key"
            value="<?php echo ($item["id"]); ?>"></td>
        <td>&nbsp;<?php echo ($item["id"]); ?></td>
        <td>&nbsp;<?php echo ($item["title"]); ?></td>
        <td>&nbsp;<?php echo ($item["name"]); ?></td>
        <td>&nbsp;<div style="width:430px;word-break:break-all;"><?php echo ($item["value"]); ?></div></td>
        <td>&nbsp;<?php echo ($item["site_id"]); ?></td>
        <td>&nbsp;<?php echo ($item["input_type"]); ?></td>
        <td>&nbsp;<?php echo (get_is_effect($item["is_effect"],$item['id'])); ?></td>
        <td><a href="javascript:edit('<?php echo ($item["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a
            href="javascript: foreverdel('<?php echo ($item["id"]); ?>')"><?php echo L("FOREVERDEL");?></a>&nbsp;</td>
    </tr><?php endforeach; endif; ?>
    <tr>
        <td colspan="9" class="bottomTd">&nbsp;</td>
    </tr>
</table>
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