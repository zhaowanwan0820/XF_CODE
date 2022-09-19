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

<?php function get_cate_name($cate_id)
    {
        return M("ArticleCate")->where("id=".$cate_id)->getField("title");
    }
    function getSiteName($site_id)
    {
            if(empty($site_id))
                return '未分配';
            else
                return array_search($site_id,$GLOBALS['sys_config']['TEMPLATE_LIST']);
    } ?>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
    <input type="button" class="button" value="<?php echo L("DEL");?>" onclick="del();" />
</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        名称：<input type="text" class="textbox" name="title" value="<?php echo trim($_REQUEST['title']);?>" />
        分站：
        <select name="site_id">
            <?php if(is_array($site_list)): foreach($site_list as $site_id=>$site_name): ?><option value="<?php echo ($site_id); ?>" <?php if($_REQUEST['site_id'] == $site_id): ?>selected="selected"<?php endif; ?>>
                <?php echo ($site_name); ?>
                </option><?php endforeach; endif; ?>
        </select>
        <input type="hidden" value="Article" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="<?php echo L("EXPORT");?>" onclick="export_csv_file('');"/>
    </form>
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="13" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','Article','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('title','<?php echo ($sort); ?>','Article','index')" title="按照<?php echo L("ARTICLE_TITLE");?><?php echo ($sortType); ?> "><?php echo L("ARTICLE_TITLE");?><?php if(($order)  ==  "title"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('cate_id','<?php echo ($sort); ?>','Article','index')" title="按照<?php echo L("CATE_TREE");?><?php echo ($sortType); ?> "><?php echo L("CATE_TREE");?><?php if(($order)  ==  "cate_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','Article','index')" title="按照<?php echo L("IS_EFFECT");?><?php echo ($sortType); ?> "><?php echo L("IS_EFFECT");?><?php if(($order)  ==  "is_effect"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','Article','index')" title="按照<?php echo L("CREATE_TIME");?><?php echo ($sortType); ?> "><?php echo L("CREATE_TIME");?><?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('update_time','<?php echo ($sort); ?>','Article','index')" title="按照<?php echo L("UPDATE_TIME");?><?php echo ($sortType); ?> "><?php echo L("UPDATE_TIME");?><?php if(($order)  ==  "update_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('site_id','<?php echo ($sort); ?>','Article','index')" title="按照所属分站<?php echo ($sortType); ?> ">所属分站<?php if(($order)  ==  "site_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('sort','<?php echo ($sort); ?>','Article','index')" title="按照<?php echo L("SORT");?><?php echo ($sortType); ?> "><?php echo L("SORT");?><?php if(($order)  ==  "sort"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('click_count','<?php echo ($sort); ?>','Article','index')" title="按照<?php echo L("CLICK_COUNT");?><?php echo ($sortType); ?> "><?php echo L("CLICK_COUNT");?><?php if(($order)  ==  "click_count"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('useful_count','<?php echo ($sort); ?>','Article','index')" title="按照有用<?php echo ($sortType); ?> ">有用<?php if(($order)  ==  "useful_count"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('useless_count','<?php echo ($sort); ?>','Article','index')" title="按照无用<?php echo ($sortType); ?> ">无用<?php if(($order)  ==  "useless_count"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$article): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($article["id"]); ?>"></td><td>&nbsp;<?php echo ($article["id"]); ?></td><td>&nbsp;<a href="javascript:edit('<?php echo (addslashes($article["id"])); ?>')"><?php echo ($article["title"]); ?></a></td><td>&nbsp;<?php echo (get_cate_name($article["cate_id"])); ?></td><td>&nbsp;<?php echo (get_is_effect($article["is_effect"],$article['id'])); ?></td><td>&nbsp;<?php echo (to_date($article["create_time"])); ?></td><td>&nbsp;<?php echo (to_date($article["update_time"])); ?></td><td>&nbsp;<?php echo (getSiteName($article["site_id"])); ?></td><td>&nbsp;<?php echo (get_sort($article["sort"],$article['id'])); ?></td><td>&nbsp;<?php echo ($article["click_count"]); ?></td><td>&nbsp;<?php echo ($article["useful_count"]); ?></td><td>&nbsp;<?php echo ($article["useless_count"]); ?></td><td><a href="javascript:edit('<?php echo ($article["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a href="javascript: del('<?php echo ($article["id"]); ?>')"><?php echo L("DEL");?></a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="13" class="bottomTd"> &nbsp;</td></tr></table>
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


<script type="text/javascript">

    function export_csv_file()
    {
        var confirm_msg = "确认要导出csv文件数据吗？";

        if (!confirm(confirm_msg)) {
            return;
        }
        return export_csv();
    }

</script>