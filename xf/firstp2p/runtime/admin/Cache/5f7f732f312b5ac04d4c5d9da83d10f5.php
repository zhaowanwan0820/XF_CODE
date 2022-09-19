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

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="__ROOT__/static/admin/Common/js/bank.js"></script>
<div class="main">
    <div class="main_title">支行信息列表</div>
    <div class="blank5"></div>

    <div class="button_row">
        <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="location.href='/m.php?m=BankList&a=add'"/>
    </div>

    <div class="blank5"></div>

    <div class="main_title">
        <form name="search" id='frm' method='get' action='m.php'>
            <input type='hidden' name='m' value='BankList' />
            <input type='hidden' name='a' value='index' />
            银行名称：<input type='text' name='branch' value='<?php echo trim($_REQUEST['branch']); ?>' />
            支行名称：<input type='text' name='name' value='<?php echo trim($_REQUEST['name']); ?>' />
            联行号：<input type='text' name='bank_id' value='<?php echo trim($_REQUEST['bank_id']); ?>' />
            操作人：<input type='text' name='oper_name' value='<?php echo trim($_REQUEST['oper_name']); ?>' />
            <input type='submit' value='搜索' class="button">
        </form>
    </div>
    <div class="blank5"></div>

    <!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="10" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','BankList','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="200px"><a href="javascript:sortBy('branch','<?php echo ($sort); ?>','BankList','index')" title="按照银行名称<?php echo ($sortType); ?> ">银行名称<?php if(($order)  ==  "branch"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="300px"><a href="javascript:sortBy('name','<?php echo ($sort); ?>','BankList','index')" title="按照支行名称<?php echo ($sortType); ?> ">支行名称<?php if(($order)  ==  "name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="100px"><a href="javascript:sortBy('bank_id','<?php echo ($sort); ?>','BankList','index')" title="按照联行号<?php echo ($sortType); ?> ">联行号<?php if(($order)  ==  "bank_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="80px"><a href="javascript:sortBy('province','<?php echo ($sort); ?>','BankList','index')" title="按照支行所在省份<?php echo ($sortType); ?> ">支行所在省份<?php if(($order)  ==  "province"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="80px"><a href="javascript:sortBy('city','<?php echo ($sort); ?>','BankList','index')" title="按照支行所在城市<?php echo ($sortType); ?> ">支行所在城市<?php if(($order)  ==  "city"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('oper_id','<?php echo ($sort); ?>','BankList','index')" title="按照操作人<?php echo ($sortType); ?> ">操作人<?php if(($order)  ==  "oper_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('oper_time','<?php echo ($sort); ?>','BankList','index')" title="按照操作时间<?php echo ($sortType); ?> ">操作时间<?php if(($order)  ==  "oper_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td><td>&nbsp;<?php echo ($item["id"]); ?></td><td>&nbsp;<?php echo ($item["branch"]); ?></td><td>&nbsp;<?php echo ($item["name"]); ?></td><td>&nbsp;<?php echo ($item["bank_id"]); ?></td><td>&nbsp;<?php echo ($item["province"]); ?></td><td>&nbsp;<?php echo ($item["city"]); ?></td><td>&nbsp;<?php echo (get_admin_name($item["oper_id"])); ?></td><td>&nbsp;<?php echo (format_date($item["oper_time"])); ?></td><td><a href="javascript:edit('<?php echo ($item["id"]); ?>')">编辑</a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="10" class="bottomTd"> &nbsp;</td></tr></table>
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