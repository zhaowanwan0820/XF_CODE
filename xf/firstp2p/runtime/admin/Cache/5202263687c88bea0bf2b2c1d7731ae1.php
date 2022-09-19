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

<script type="text/javascript" src="__TMPL__Common/js/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/user.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<style>
.span_block{
    display:block;
}
.content{width: 100%;overflow:hidden;}
.content .left{width:50%;float: left;overflow:hidden;}
.content .right{width: 50%;float: left;}
.content .info{height: 20px;line-height: 20px;}
/*.image{
    width:400px;
    overflow:hidden;
}*/
/*#status_div{width:175px;float: left}*/
#status_div .failReasonSpan{display: block;float: left;}
#passedBox{position: relative;}
.status_value{width: 171px;text-indent: 5px;border:2px solid #ccc;}
</style>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        会员名：<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" />
        姓名：<input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" />
        手机号：<input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>" />
        人脸验证：<select name="face_verified">
            <option value="0"<?= $_REQUEST['face_verified']==0 ? ' selected' : '' ?>>所有</option>
            <option value="1"<?= $_REQUEST['face_verified']==1 ? ' selected' : '' ?>>人脸识别</option>
            <option value="2"<?= $_REQUEST['face_verified']==2 ? ' selected' : '' ?>>非人脸识别</option>
        </select>
        申请时间：<input type="text" class="textbox" id="apply_start" name="apply_start" value="<?php echo trim($_REQUEST['apply_start']);?>" onfocus="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
                  <input type="text" class="textbox" name="apply_end" id="apply_end" value="<?php echo trim($_REQUEST['apply_end']);?>"  onfocus="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
            <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="autoAuditBankInfo" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" id="export" class="button" onclick="export_csv();" value="导出" />
    </form>
</div>
<?php function get_user_by_name($name){
    return '<a href="/m.php?user_name='.$name.'&m=User&a=index" target="_blank">'.$name."</a>";
}
function get_user_by_real_name($name){
    return '<a href="/m.php?real_name='.$name.'&m=User&a=index" target="_blank">'.$name."</a>";
}
function get_user_by_mobile($name){
    return '<a href="/m.php?mobile='.$name.'&m=User&a=index" target="_blank">'.$name."</a>";
}
function get_list_by_user_id($name, $item){
    return '<a href="/m.php?mobile='.$item['mobile'].'&status=0&m=User&a=autoAuditBankInfo">'.$name."</a>";
} ?>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="10" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','autoAuditBankInfo')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_name','<?php echo ($sort); ?>','User','autoAuditBankInfo')" title="按照用户名<?php echo ($sortType); ?> ">用户名<?php if(($order)  ==  "user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('real_name','<?php echo ($sort); ?>','User','autoAuditBankInfo')" title="按照姓名<?php echo ($sortType); ?> ">姓名<?php if(($order)  ==  "real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('mobile','<?php echo ($sort); ?>','User','autoAuditBankInfo')" title="按照手机号<?php echo ($sortType); ?> ">手机号<?php if(($order)  ==  "mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','User','autoAuditBankInfo')" title="按照申请时间<?php echo ($sortType); ?> ">申请时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('total_assets','<?php echo ($sort); ?>','User','autoAuditBankInfo')" title="按照申请时总资产<?php echo ($sortType); ?> ">申请时总资产<?php if(($order)  ==  "total_assets"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('face_verified','<?php echo ($sort); ?>','User','autoAuditBankInfo')" title="按照人脸验证<?php echo ($sortType); ?> ">人脸验证<?php if(($order)  ==  "face_verified"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('count','<?php echo ($sort); ?>','User','autoAuditBankInfo')" title="按照累计申请次数<?php echo ($sortType); ?> ">累计申请次数<?php if(($order)  ==  "count"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td><td>&nbsp;<?php echo ($item["id"]); ?></td><td>&nbsp;<?php echo (get_user_by_name($item["user_name"])); ?></td><td>&nbsp;<?php echo (get_user_by_real_name($item["real_name"])); ?></td><td>&nbsp;<?php echo (get_user_by_mobile($item["mobile"])); ?></td><td>&nbsp;<?php echo (to_date($item["create_time"])); ?></td><td>&nbsp;<?php echo ($item["total_assets"]); ?></td><td>&nbsp;<?php echo ($item["face_verified"]); ?></td><td>&nbsp;<?php echo (get_list_by_user_id($item["count"],$item)); ?></td><td><a href="javascript:getAutoAuditBankInfo('<?php echo ($item["id"]); ?>')">查看</a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="10" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<!-- 查看 -->
<div id='dialogbox_msg' class="dialog-box dialogbox" style="display: none; position: absolute; overflow: hidden; z-index: 999; width: 800px; top: 200px; right: 200px;">
    <div class="dialog-header">
        <div class="dialog-title">修改银行卡认证</div>
        <div class="dialog-close" onclick='close_div()'></div>
    </div>
    <div class="dialog-content" id="bankInfo" >
    </div>
</div>

<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>
function get_query_string(){
    querystring = '';
    querystring += "&apply_start="+$("input[name='apply_start']").val();
    querystring += "&apply_end="+$("input[name='apply_end']").val();
    querystring += "&user_name="+$("input[name='user_name']").val();
    querystring += "&mobile="+$("input[name='mobile']").val();
    querystring += "&status="+$("select[name='status']").val();
    return querystring;

}

function export_csv() {
    window.location.href = ROOT+'?export=1&m=User&a=autoAuditBankInfo'+get_query_string();
}

function close_div() {
    $('.dialogbox').hide();
}
//获取银行信息
function getAutoAuditBankInfo(id) {
    if(id) {
        $.ajax({
               type: "POST",
               url: "/m.php?m=User&a=getAutoAuditBankInfo",
               data: "id="+id,
               dataType:'json',
               success: function(msg){
                       if(msg.code == '0000') {
                       $('#bankInfo').html(msg.msg);
                       $('#dialogbox_msg').show();
                       $('#dialogbox_div').hide();
                    }else{
                        alert(msg.msg);
                    }
               }
        });
    }else{
        alert('参数id不能为空');
    }
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