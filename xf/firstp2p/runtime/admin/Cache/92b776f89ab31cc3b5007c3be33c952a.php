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
    <div class="main_title">服务等级配置</div>
    <div class="blank5"></div>
    <div class="button_row">
        <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
        <input type="button" class="button" value="导出所有记录" onclick="location.href='?m=UserCouponLevel&a=export_csv';" />
    </div>
    <div class="blank5"></div>

    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            等级ID：<input type="text" id="id" class="textbox" name="id" value="<?php echo trim($_REQUEST['id']);?>" style="width:100px;" />
            等级名称：<input type="text" id="name" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" style="width:100px;" />
            服务人返利系数：<input type="text" id="rebate_ratio" class="textbox" name="rebate_ratio" value="<?php echo trim($_REQUEST['rebate_ratio']);?>" style="width:100px;" />
            备注：<input type="text" id="comment" class="textbox" name="comment" value="<?php echo trim($_REQUEST['comment']);?>" style="width:100px;" />
            状态：
            <select name="is_effect" id="is_effect">
                <option <?php if($_REQUEST['is_effect'] == 'all' || trim($_REQUEST['is_effect']) == ''): ?>selected="selected"<?php endif; ?> value="all">全部</option>
                <option <?php if(intval($_REQUEST['is_effect']) == 1): ?>selected="selected"<?php endif; ?> value="1">有效</option>
                <option <?php if($_REQUEST['is_effect'] != 'all' && trim($_REQUEST['is_effect']) != '' && intval($_REQUEST['is_effect']) == 0): ?>selected="selected"<?php endif; ?> value="0">无效</option>
            </select>
            <input type="hidden" value="UserCouponLevel" name="m" />
            <input type="hidden" value="index" name="a" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        </form>
    </div>
    <div class="blank5"></div>

    <!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="7" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','UserCouponLevel','index')" title="按照等级ID<?php echo ($sortType); ?> ">等级ID<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('name','<?php echo ($sort); ?>','UserCouponLevel','index')" title="按照服务等级名称<?php echo ($sortType); ?> ">服务等级名称<?php if(($order)  ==  "name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('rebate_ratio','<?php echo ($sort); ?>','UserCouponLevel','index')" title="按照服务人返利系数<?php echo ($sortType); ?> ">服务人返利系数<?php if(($order)  ==  "rebate_ratio"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','UserCouponLevel','index')" title="按照有效状态<?php echo ($sortType); ?> ">有效状态<?php if(($order)  ==  "is_effect"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('comment','<?php echo ($sort); ?>','UserCouponLevel','index')" title="按照备注说明<?php echo ($sortType); ?> ">备注说明<?php if(($order)  ==  "comment"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$group): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($group["id"]); ?>"></td><td>&nbsp;<?php echo ($group["id"]); ?></td><td>&nbsp;<a href="javascript:edit('<?php echo (addslashes($group["id"])); ?>')"><?php echo ($group["name"]); ?></a></td><td>&nbsp;<?php echo ($group["rebate_ratio"]); ?></td><td>&nbsp;<?php echo ($group["is_effect"]); ?></td><td>&nbsp;<?php echo ($group["comment"]); ?></td><td><a href="javascript:edit('<?php echo ($group["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a href="javascript: foreverdel('<?php echo ($group["id"]); ?>')"><?php echo L("FOREVERDEL");?></a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="7" class="bottomTd"> &nbsp;</td></tr></table>
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