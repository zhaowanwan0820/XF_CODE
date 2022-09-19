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


<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<script type="text/javascript" src="__TMPL__searchselect/jquery.1.11.1.min.js"></script>
<script type="text/javascript" src="__TMPL__searchselect/jquery.searchableselect.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__searchselect/searchableselect.css" />
<script type="text/javascript">
// 交易类型筛选
$(function(){
    $('#log_info').searchableSelect();
})

//完全删除
function foreverdel_account_detail(id)
{
    var islot = 0;
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert(LANG['DELETE_EMPTY_WARNING']);
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
        islot = 1;
    }
    if(confirm(LANG['CONFIRM_DELETE']))
    $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=foreverdelete_account_detail&id="+id+"&islot="+islot,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                $("#info").html(obj.info);
                if(obj.status==1)
                location.href=location.href;
            }
    });
}
</script>
<?php function get_remaining_lock_money($id) {
        $user_log = M("UserLog")->where("id=".$id)->find();
        return format_price($user_log['remaining_total_money'] - $user_log['remaining_money']);
    } ?>
<div class="main">
<div class="main_title"><?php echo ($user_info["user_name"]); ?> <?php echo ($accountDetailName); ?></div>
<div class="blank5"></div>
<div class="button_row">
    <input class="button" type="button" id="export_list" value="导出全部">
</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        操作时间：
<input type="text" class="textbox" id="log_time_start" name="log_time_start" value="<?php echo trim($_REQUEST['log_time_start']);?>" onfocus="return showCalendar('log_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('log_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
                  <input type="text" class="textbox" name="log_time_end" id="log_time_end" value="<?php echo trim($_REQUEST['log_time_end']);?>"  onfocus="return showCalendar('log_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('log_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />

        类型：
        <select name="log_info" id="log_info">
                <option value="" <?php if(intval($_REQUEST['log_info']) == 0): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($log_info_type)): foreach($log_info_type as $key=>$log_info_item): ?><option value="<?php echo ($log_info_item); ?>" <?php if($_REQUEST['log_info'] == $log_info_item): ?>selected="selected"<?php endif; ?>><?php echo ($log_info_item); ?></option><?php endforeach; endif; ?>
        </select>
        <input type="hidden" value="<?php echo ($user_info['id']); ?>" name="id" />
        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="account_detail_gold" name="a" />
        <input type="submit" id='submit_button' class="button" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<div class="blank5"></div>
<?php
//计算冻结余额
foreach ($list as $key => $item) {
    $list[$key]['remainingLockMoney'] = floorfix($item['remainingTotalMoney'] - $item['remainingMoney'],3);
    $list[$key]['logTime'] = date("Y-m-d H:i:s",$item['logTime']);
}
?>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="12" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('dealLoadId','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照投资记录id<?php echo ($sortType); ?> ">投资记录id<?php if(($order)  ==  "dealLoadId"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('logInfo','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照交易类型<?php echo ($sortType); ?> ">交易类型<?php if(($order)  ==  "logInfo"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('logTime','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照<?php echo L("USER_LOG_TIME");?><?php echo ($sortType); ?> "><?php echo L("USER_LOG_TIME");?><?php if(($order)  ==  "logTime"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('gold','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照黄金变动<?php echo ($sortType); ?> ">黄金变动<?php if(($order)  ==  "gold"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('note','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照备注<?php echo ($sortType); ?> ">备注<?php if(($order)  ==  "note"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('lockMoney','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照冻结(+)/解冻(-)<?php echo ($sortType); ?> ">冻结(+)/解冻(-)<?php if(($order)  ==  "lockMoney"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('remainingTotalMoney','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照黄金账户资金总额<?php echo ($sortType); ?> ">黄金账户资金总额<?php if(($order)  ==  "remainingTotalMoney"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('remainingMoney','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照黄金账户可用余额<?php echo ($sortType); ?> ">黄金账户可用余额<?php if(($order)  ==  "remainingMoney"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('remainingLockMoney','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照黄金账户冻结总额<?php echo ($sortType); ?> ">黄金账户冻结总额<?php if(($order)  ==  "remainingLockMoney"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('logAdminId','<?php echo ($sort); ?>','User','account_detail_gold')" title="按照<?php echo L("LOG_ADMIN");?><?php echo ($sortType); ?> "><?php echo L("LOG_ADMIN");?><?php if(($order)  ==  "logAdminId"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$log): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($log["id"]); ?>"></td><td>&nbsp;<?php echo ($log["id"]); ?></td><td>&nbsp;<?php echo ($log["dealLoadId"]); ?></td><td>&nbsp;<?php echo ($log["logInfo"]); ?></td><td>&nbsp;<?php echo ($log["logTime"]); ?></td><td>&nbsp;<?php echo ($log["gold"]); ?></td><td>&nbsp;<?php echo (htmlspecialchars($log["note"])); ?></td><td>&nbsp;<?php echo ($log["lockMoney"]); ?></td><td>&nbsp;<?php echo ($log["remainingTotalMoney"]); ?></td><td>&nbsp;<?php echo ($log["remainingMoney"]); ?></td><td>&nbsp;<?php echo ($log["remainingLockMoney"]); ?></td><td>&nbsp;<?php echo (get_admin_name($log["logAdminId"])); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="12" class="bottomTd"> &nbsp;</td></tr></table>
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
    $('#export_list').click(function(){
       var href='<?php echo u("User/account_export_gold", array("id"=>$user_info['id']));?>';
       if($('#log_time_start').val()){
          href = href + '&log_time_start='+$('#log_time_start').val();
       }
       if($('#log_time_end').val()){
          href = href + '&log_time_end='+$('#log_time_end').val();
       }
       if($('#log_info').val()){
          href = href + '&log_info='+$('#log_info').val();
       }
        if($('#backup').val()){
            href = href + '&backup='+$('#backup').val();
        }
        href = href + '&deal_type=0,1,2,3';
       window.location.href=href;
       return false;
    });
</script>