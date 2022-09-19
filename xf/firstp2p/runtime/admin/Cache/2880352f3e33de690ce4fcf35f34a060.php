<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
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
        <form id="search" name="search" action="__APP__" method="get">
            <input type="hidden" name="conf_type" value="<?php echo ($conf_type); ?>">
            <input type="hidden" name="site_id" value="<?php echo ($site_id); ?>">
            <input type="hidden" value="ApiConf" name="m" />
            <input type="hidden" value="index" name="a" />
        </form>
        <?php if(is_array($tab_list)): foreach($tab_list as $key=>$site_name): ?><input type="button" class='button conf_btn <?php if($site_name['conf_type'] == $conf_type and $site_name['site_id'] == $site_id): ?>currentbtn<?php endif; ?>' conf_type= "<?php echo ($site_name['conf_type']); ?>" site_id="<?php echo ($site_name['site_id']); ?>" value="<?php echo ($site_name['name']); ?>" onclick="search_button(this)"/>&nbsp;<?php endforeach; endif; ?>
    </div>
    <div class="blank5"></div>
    <div class="button_row">
        <input id = 'addbutton' type="button" class="button" value="<?php echo L("ADD");?>" onclick="addButton()"/>
        <input type='hidden' class = "site_conf_keys" value='' conf_type="<?php echo ($conf_type); ?>" site_id="<?php echo ($site_id); ?>"/>
        <input type="button" class="button" value="<?php echo L("FOREVERDEL");?>" onclick="foreverdel();" />
        <input type="button" class="button" value="发布" onclick="location.href='m.php?m=ApiConf&a=setLastModifyTime';" />
    </div>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            所属站点: <?php echo ($now_site); ?> &nbsp;&nbsp;
            <?php echo L("API_CONF_TITLE");?>：<input type="text" class="textbox" name="title" value="<?php echo trim($_REQUEST['title']);?>" />
            <?php echo L("API_CONF_NAME");?>：<input type="text" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" />
            <input type="hidden" value="ApiConf" name="m" />
            <input type="hidden" value="index" name="a" />
            <input type="hidden" name="conf_type" value="<?php echo ($conf_type); ?>">
            <input type="hidden" name="site_id" value="<?php echo ($site_id); ?>">
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        </form>
    </div>
    <div class="blank5"></div>
    <!-- Think 系统列表组件开始 -->
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="9" class="topTd">&nbsp;</td>
        </tr>
       <tr class="row">
            <th width="8"><input type="checkbox" id="check"
                                 onclick="CheckAll('dataTable')"></th>
            <th width="50px  "><a
                    href="javascript:sortBy('id','<?php echo ($sort); ?>','ApiConf','index')"
                    title="按照<?php echo L("ID");?>
                    <?php echo ($sortType); ?> "><?php echo L("ID");?>
                    <?php if(($order)  ==  "id"): ?>
                    <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                     width="12" height="17" border="0" align="absmiddle">
                    <?php endif; ?>
                </a></th>
            <th><a
                    href="javascript:sortBy('title','<?php echo ($sort); ?>','ApiConf','index')"
                    title="按照<?php echo L("API_CONF_TITLE");?> <?php echo ($sortType); ?>
                    "><?php echo L("API_CONF_TITLE");?> <?php if(($order)  ==  "title"): ?>
                    <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                     width="12" height="17" border="0" align="absmiddle">
                    <?php endif; ?></a></th>
            <th><a
                    href="javascript:sortBy('name','<?php echo ($sort); ?>','ApiConf','index')"
                    title="按照<?php echo L("API_CONF_NAME");?> <?php echo ($sortType); ?>
                    "><?php echo L("API_CONF_NAME");?> <?php if(($order)  ==  "name"): ?>
                    <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                     width="12" height="17" border="0" align="absmiddle">
                    <?php endif; ?></a></th>
            <th width="250px  "><a
                    href="javascript:sortBy('value','<?php echo ($sort); ?>','ApiConf','index')"
                    title="按照<?php echo L("API_CONF_VALUE");?>
                    <?php echo ($sortType); ?> "><?php echo L("API_CONF_VALUE");?>
                    <?php if(($order)  ==  "value"): ?>
                    <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                     width="12" height="17" border="0" align="absmiddle">
                    <?php endif; ?>
                </a></th>
            <th width="250px  "><a
                    href="javascript:sortBy('tip','<?php echo ($sort); ?>','ApiConf','index')"
                    title="按照<?php echo L("API_CONF_TIP");?>
                    <?php echo ($sortType); ?> "><?php echo L("API_CONF_TIP");?>
                    <?php if(($order)  ==  "tip"): ?>
                    <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                     width="12" height="17" border="0" align="absmiddle">
                    <?php endif; ?>
                </a></th>
            <th><a
                    href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','ApiConf','index')"
                    title="按照<?php echo L("IS_EFFECT");?>
                    <?php echo ($sortType); ?> "><?php echo L("IS_EFFECT");?>
                    <?php if(($order)  ==  "is_effect"): ?>
                    <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                     width="12" height="17" border="0" align="absmiddle">
                    <?php endif; ?>
                </a></th>
            <th style="width:">操作</th>
        </tr>
        <?php if(is_array($list)): foreach($list as $key=>$conf_item): ?><tr class="row">
	                <td><input type="checkbox" name="key" class="key"
	                           value="<?php echo ($conf_item[id]); ?>"></td>
	                <td>&nbsp;<?php echo ($conf_item['id']); ?></td>
	                <td>&nbsp;<?php echo ($conf_item['title']); ?></td>
	                <td>&nbsp;<?php echo ($conf_item['name']); ?></td>
	                <td>&nbsp;<?php echo ($conf_item['value']); ?></td>
	                <td>&nbsp;<?php echo ($conf_item['tip']); ?></td>
	                <td>&nbsp;<?php echo (get_is_effect($conf_item["is_effect"],$conf_item['id'])); ?></td>
	                <td><a href="javascript:edit('<?php echo ($conf_item['id']); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a
	                        href="javascript: foreverdel('<?php echo ($conf_item['id']); ?>')"><?php echo L("FOREVERDEL");?></a>&nbsp;</td>
	            </tr><?php endforeach; endif; ?>
        <tr>
            <td colspan="9" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
    <!-- Think 系统列表组件结束 -->
    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
<script type="text/javascript">
//添加跳转
function addButton()
{
    conf_type = "";
    site_id = "";
    if($("input.site_conf_keys").length == 1){
        conf_type = $("input.site_conf_keys").attr("conf_type");
        site_id = $("input.site_conf_keys").attr("site_id");
    }
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=add"+'&conf_type='+conf_type+'&site_id='+site_id;
}
function search_button(e) {
    conf_type = $(e).attr('conf_type');
    site_id = $(e).attr('site_id');
    $("#search").find("input[name='conf_type']").val(conf_type);
    $("#search").find("input[name='site_id']").val(site_id);
    $("#search").submit();
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