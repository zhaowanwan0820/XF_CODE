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

<script type="text/javascript">
	function edit_mail(id)
	{
		location.href = ROOT+"?"+VAR_MODULE+"=PromoteMsg&"+VAR_ACTION+"=edit_mail&id="+id;
	}
	function view_result(id)
	{
		location.href = ROOT+"?"+VAR_MODULE+"=PromoteMsgList&"+VAR_ACTION+"=index&msg_id="+id;
	}
</script>
<div class="main">
<div class="main_title"><?php echo L("PROMOTEMSG_MAIL_INDEX");?></div>
<div class="blank5"></div>
<div class="button_row">
	<input type="button" class="button" value="<?php echo L("ADD");?>" onclick="location.href='<?php echo u("PromoteMsg/add_mail");?>';" />
	<input type="button" class="button" value="<?php echo L("FOREVERDEL");?>" onclick="foreverdel();" />
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="8" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','PromoteMsg','mail_index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('title','<?php echo ($sort); ?>','PromoteMsg','mail_index')" title="按照<?php echo L("MAIL_TITLE");?>  <?php echo ($sortType); ?> "><?php echo L("MAIL_TITLE");?>  <?php if(($order)  ==  "title"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('send_time','<?php echo ($sort); ?>','PromoteMsg','mail_index')" title="按照<?php echo L("SEND_TIME");?>  <?php echo ($sortType); ?> "><?php echo L("SEND_TIME");?>  <?php if(($order)  ==  "send_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('send_status','<?php echo ($sort); ?>','PromoteMsg','mail_index')" title="按照<?php echo L("SEND_STATUS");?>  <?php echo ($sortType); ?> "><?php echo L("SEND_STATUS");?>  <?php if(($order)  ==  "send_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('deal_id','<?php echo ($sort); ?>','PromoteMsg','mail_index')" title="按照<?php echo L("MAIL_TYPE");?>  <?php echo ($sortType); ?> "><?php echo L("MAIL_TYPE");?>  <?php if(($order)  ==  "deal_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('send_type','<?php echo ($sort); ?>','PromoteMsg','mail_index')" title="按照<?php echo L("MAIL_SEND_TYPE");?><?php echo ($sortType); ?> "><?php echo L("MAIL_SEND_TYPE");?><?php if(($order)  ==  "send_type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$mail): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($mail["id"]); ?>"></td><td>&nbsp;<?php echo ($mail["id"]); ?></td><td>&nbsp;<?php echo ($mail["title"]); ?></td><td>&nbsp;<?php echo (to_date($mail["send_time"])); ?></td><td>&nbsp;<?php echo (get_send_status($mail["send_status"])); ?></td><td>&nbsp;<?php echo (get_send_mail_type($mail["deal_id"])); ?></td><td>&nbsp;<?php echo (get_send_type($mail["send_type"])); ?></td><td><a href="javascript:edit_mail('<?php echo ($mail["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a href="javascript: foreverdel('<?php echo ($mail["id"]); ?>')"><?php echo L("FOREVERDEL");?></a>&nbsp;<a href="javascript: view_result('<?php echo ($mail["id"]); ?>')"><?php echo L("VIEW_PROCESS");?></a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="8" class="bottomTd"> &nbsp;</td></tr></table>
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