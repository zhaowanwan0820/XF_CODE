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

<?php function get_is_reset($status)
	{
		if($status==1)
		return l("YES");
		else
		return l("NO");
	} ?>
<script type="text/javascript">
	function send_demo()
	{		
		$.ajax({ 
				url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=send_demo&test_mail="+$.trim($("input[name='test_email']").val()), 
				data: "ajax=1",
				dataType: "json",
				success: function(obj){
					if(obj.status==0)
					{
						alert(obj.info);
					}
					else
					$("#info").html(obj.info);
				}
		});
	}
	$(document).ready(function(){
		$("input[name='test_mail_btn']").bind("click",function(){
			var mail = $.trim($("input[name='test_email']").val());	
			if(mail!='')
			send_demo();
		});
	});
</script>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?> 
			<input type="text" class="textbox" name="test_email" />
			<input type="button" class="button" name="test_mail_btn" value="<?php echo L("TEST");?>" />
</div>
<div class="blank5"></div>
<div class="button_row">
	<input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
	<input type="button" class="button" value="<?php echo L("FOREVERDEL");?>" onclick="foreverdel();" />
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="9" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px  "><a href="javascript:sortBy('id','<?php echo ($sort); ?>','MailServer','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('smtp_server','<?php echo ($sort); ?>','MailServer','index')" title="按照<?php echo L("SMTP_SERVER");?>  <?php echo ($sortType); ?> "><?php echo L("SMTP_SERVER");?>  <?php if(($order)  ==  "smtp_server"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('smtp_name','<?php echo ($sort); ?>','MailServer','index')" title="按照<?php echo L("SMTP_NAME");?>  <?php echo ($sortType); ?> "><?php echo L("SMTP_NAME");?>  <?php if(($order)  ==  "smtp_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('use_limit','<?php echo ($sort); ?>','MailServer','index')" title="按照<?php echo L("USE_LIMIT");?>  <?php echo ($sortType); ?> "><?php echo L("USE_LIMIT");?>  <?php if(($order)  ==  "use_limit"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('total_use','<?php echo ($sort); ?>','MailServer','index')" title="按照<?php echo L("TOTAL_USE");?>  <?php echo ($sortType); ?> "><?php echo L("TOTAL_USE");?>  <?php if(($order)  ==  "total_use"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_reset','<?php echo ($sort); ?>','MailServer','index')" title="按照<?php echo L("IS_RESET");?>  <?php echo ($sortType); ?> "><?php echo L("IS_RESET");?>  <?php if(($order)  ==  "is_reset"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','MailServer','index')" title="按照<?php echo L("IS_EFFECT");?><?php echo ($sortType); ?> "><?php echo L("IS_EFFECT");?><?php if(($order)  ==  "is_effect"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$serveritem): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($serveritem["id"]); ?>"></td><td>&nbsp;<?php echo ($serveritem["id"]); ?></td><td>&nbsp;<?php echo ($serveritem["smtp_server"]); ?></td><td>&nbsp;<?php echo ($serveritem["smtp_name"]); ?></td><td>&nbsp;<?php echo ($serveritem["use_limit"]); ?></td><td>&nbsp;<?php echo ($serveritem["total_use"]); ?></td><td>&nbsp;<?php echo (get_is_reset($serveritem["is_reset"])); ?></td><td>&nbsp;<?php echo (get_is_effect($serveritem["is_effect"],$serveritem['id'])); ?></td><td><a href="javascript:edit('<?php echo ($serveritem["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a href="javascript: foreverdel('<?php echo ($serveritem["id"]); ?>')"><?php echo L("FOREVERDEL");?></a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="9" class="bottomTd"> &nbsp;</td></tr></table>
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