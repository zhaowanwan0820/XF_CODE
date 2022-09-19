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

<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/user.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript">
	function show_content(id) {
		$.weeboxs.open(ROOT+'?m=DealMsgList&&a=show_content&datatype=sms&id='+id, {contentType:'ajax',showButton:false,title:LANG['SHOW_CONTENT'],width:600});
	}
	
	function send(id)
	{
		$.ajax({ 
				url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=send&id="+id, 
				data: "ajax=1",
				success: function(msg){
					alert(msg);
				}
		});
	}

    function sendNow(id)
    {
        $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=sendSms&id="+id,
            data: "ajax=1",
            success: function(msg){
                var msg = $.parseJSON(msg);
                alert(msg.info);
            }
        });
    }

    function sendSms(id)
    {
        $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=sendSms&id="+id,
            data: "ajax=1",
            success: function(msg){
                var msg = $.parseJSON(msg);
                alert(msg.info);
            }
        });
    }

	function resend() {
		$.ajax({
			url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=resend", 
			data: "ajax=1",
	        dataType: "json",
			success: function(msg){
				alert(msg.info);
			}
		});
	}
	
	//批量重复发送
	function batchResendData() {
		var select_value = jqchk();
		if(select_value.length >0) {
			if(confirm('批量重发')) {
				$.ajax({
					   type: "POST",
					   url: '/m.php?m=DealMsgList&a=batchResend',
					   data: "ids="+select_value,
					   dataType:"json",
					   success: function(data){
						   if(data.code != '0000') {
							   alert(data.message);
							   return false;
						   }else{
							   var querySting = $('#queryString').val();
							   window.location.href='/m.php?m=DealMsgList&a=index&'+querySting;
						   }
					   }
				});
			}	
		}else{
			alert('你还没有选择任何内容！');
		}
	}
	
	
	//全选
	function jqchk(){  
		var chk_value =[];    
		$('input[name="key"]:checked').each(function(){    
			  chk_value.push($(this).val());    
		});  
		return chk_value;
	}    
</script>
<div class="main">
<div class="main_title">业务短信列表</div>
<div class="blank5"></div>
<div class="button_row">
    <!--  <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="location.href='<?php echo u("DealMsgList/add_msg");?>';" />-->
</div>
<div class="blank5"></div>
<div class="search_row">
	<form name="search" action="__APP__" method="get">	
	<input type='hidden' id='queryString' value='<?php echo ($queryString); ?>'>
		<?php echo L("SEND_DEST");?>：<input type="text" class="textbox" name="dest" value="<?php echo trim($_REQUEST['dest']);?>" style="width:100px;" />
		发送结果：
		<select name='is_success' >
                <option value="0" <?php if(intval($_REQUEST['is_success']) == 0): ?>selected="selected"<?php endif; ?>>发送到队列失败</option>
                <option value="1" <?php if(intval($_REQUEST['is_success']) == 1): ?>selected="selected"<?php endif; ?>>队列处理成功</option>
                <option value="2" <?php if(intval($_REQUEST['is_success']) == 2): ?>selected="selected"<?php endif; ?>>发送到队列</option>
                <option value="3" <?php if(intval($_REQUEST['is_success']) == 3): ?>selected="selected"<?php endif; ?>>队列处理失败</option>
                <option value="" <?php if($_REQUEST['is_success'] == ''): ?>selected="selected"<?php endif; ?>>全部</option>
         </select>
		<input type="hidden" value="DealMsgList" name="m" />
		<input type="hidden" value="indexSms" name="a" />
		<input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
	</form>
</div>
<div class="blank5"></div>
<?php function get_sms_success($result) {
		if($result == 0) {
			return '发送到队列失败';
		}elseif($result == 2){
			return '发送到队列';
		}elseif($result == 1){
			return '队列处理成功';
		}elseif($result == 3){
			return '队列处理失败';
		}
	} ?>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="11" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px  "><a href="javascript:sortBy('id','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('dest','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照接收人手机  <?php echo ($sortType); ?> ">接收人手机  <?php if(($order)  ==  "dest"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照<?php echo L("USER_NAME");?>  <?php echo ($sortType); ?> "><?php echo L("USER_NAME");?>  <?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('show_content','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照<?php echo L("CONTENT");?>  <?php echo ($sortType); ?> "><?php echo L("CONTENT");?>  <?php if(($order)  ==  "show_content"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照<?php echo L("CREATE_TIME");?>  <?php echo ($sortType); ?> "><?php echo L("CREATE_TIME");?>  <?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('send_time','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照<?php echo L("SEND_TIME");?>  <?php echo ($sortType); ?> "><?php echo L("SEND_TIME");?>  <?php if(($order)  ==  "send_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_send','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照<?php echo L("SEND_STATUS");?>  <?php echo ($sortType); ?> "><?php echo L("SEND_STATUS");?>  <?php if(($order)  ==  "is_send"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_success','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照<?php echo L("SEND_RESULT");?>  <?php echo ($sortType); ?> "><?php echo L("SEND_RESULT");?>  <?php if(($order)  ==  "is_success"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="100px"><a href="javascript:sortBy('result','<?php echo ($sort); ?>','DealMsgList','indexSms')" title="按照<?php echo L("SEND_INFO");?><?php echo ($sortType); ?> "><?php echo L("SEND_INFO");?><?php if(($order)  ==  "result"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$msg): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($msg["id"]); ?>"></td><td>&nbsp;<?php echo ($msg["id"]); ?></td><td>&nbsp;<?php echo ($msg["dest"]); ?></td><td>&nbsp;<?php echo (get_user_name($msg["user_id"])); ?></td><td>&nbsp;<?php echo ($msg["show_content"]); ?></td><td>&nbsp;<?php echo (to_date($msg["create_time"])); ?></td><td>&nbsp;<?php echo (to_date($msg["send_time"])); ?></td><td>&nbsp;<?php echo (get_is_send($msg["is_send"])); ?></td><td>&nbsp;<?php echo (get_sms_success($msg["is_success"])); ?></td><td>&nbsp;<?php echo ($msg["result"]); ?></td><td><a href="javascript:sendSms('<?php echo ($msg["id"]); ?>')">立即发送</a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="11" class="bottomTd"> &nbsp;</td></tr></table>
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