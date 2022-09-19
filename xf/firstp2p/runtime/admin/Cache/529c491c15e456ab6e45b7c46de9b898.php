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
.case_list{width: 171px;border:2px solid #A6C8FF;background: #fff;border-top:none;position: absolute;z-index: 222;left:5px;top:26px;}
.case_list li{cursor: pointer;}
.case_list>li.case_li{padding:5px;position: relative;}
.case_list>li.case_li:hover{color:#fff;background: #1E90FF;}
.case_list>li.case_li_noClick:hover{color:#fff;background: #1E90FF;}
.case_list>li.case_li_noClick:hover ul{color:#666;display: block;}
.directionR{position: absolute;right:10px;font-style: normal;font-weight: 700;}
.case_list>li.case_li_noClick ul{min-width: 150px;border:2px solid #A6C8FF;background: #fff;
    position: absolute;left: 170px;top:-100px;z-index: 990;display: none;}
.case_list>li.case_li_noClick ul li{padding:5px 10px;text-align: center;}
.case_list>li.case_li_noClick ul li:hover{color:#fff;background: #1E90FF;}
.case_list li.case_li_noClick{padding:5px;position: relative;cursor: default;}
</style>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        编号：<input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" />
        姓名：<input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" />
        证件类型：
        <select id="id_type" name="id_type">
            <option value="-1" <?php if($_REQUEST['status'] == -1): ?>selected<?php endif; ?>>全部</option>
            <?php if(is_array($idTypes)): foreach($idTypes as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if($_REQUEST['id_type'] == $key): ?>selected<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        证件号：<input type="text" class="textbox" name="idno" value="<?php echo trim($_REQUEST['idno']);?>" />
        请求号：<input type="text" class="textbox" name="order_id" value="<?php echo trim($_REQUEST['order_id']);?>" />
        状态：
        <select id="status" name="status">
            <option value="-1" <?php if($_REQUEST['status'] == -1): ?>selected<?php endif; ?>>全部</option>
            <option value="0" <?php if($_REQUEST['status'] == 0): ?>selected<?php endif; ?>>待人工审核</option>
            <option value="1" <?php if($_REQUEST['status'] == 1): ?>selected<?php endif; ?>>已通过</option>
            <option value="2" <?php if($_REQUEST['status'] == 2): ?>selected<?php endif; ?>>已拒绝</option>
        </select>

        <br />
         申请时间：<input type="text" class="textbox" id="apply_start" name="apply_start" value="<?php echo trim($_REQUEST['apply_start']);?>" onfocus="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
                  <input type="text" class="textbox" name="apply_end" id="apply_end" value="<?php echo trim($_REQUEST['apply_end']);?>"  onfocus="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
            <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
         完成时间：<input type="text" class="textbox" id="finish_start" name="finish_start" value="<?php echo trim($_REQUEST['finish_start']);?>" onfocus="return showCalendar('finish_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_deal_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_deal_time_start" value="选择" onclick="return showCalendar('finish_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_deal_time_start');" />
至
                  <input type="text" class="textbox" name="finish_end" id="finish_end" value="<?php echo trim($_REQUEST['finish_end']);?>"  onfocus="return showCalendar('finish_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_deal_time_end');" style="width:150px;" />
            <input type="button" class="button" id="btn_deal_time_end" value="选择" onclick="return showCalendar('finish_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_deal_time_end');" />
        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="userIdentityModifyLog" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<?php function get_id_type_name($id_type) {
    $idTypes = $GLOBALS['dict']['ID_TYPE'];
    return isset($idTypes[$id_type]) ? $idTypes[$id_type] : '未知';
}

function get_user_identity_status($status) {
    $statusMap = \core\dao\UserIdentityModifyLogModel::$statusMap;
    return isset($statusMap[$status]) ? $statusMap[$status] : '未知';
} ?>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="12" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照序号<?php echo ($sortType); ?> ">序号<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照会员编号<?php echo ($sortType); ?> ">会员编号<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('real_name','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照姓名<?php echo ($sortType); ?> ">姓名<?php if(($order)  ==  "real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('id_type','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照证件类型<?php echo ($sortType); ?> ">证件类型<?php if(($order)  ==  "id_type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('idno','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照证件号<?php echo ($sortType); ?> ">证件号<?php if(($order)  ==  "idno"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('order_id','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照请求号<?php echo ($sortType); ?> ">请求号<?php if(($order)  ==  "order_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照状态<?php echo ($sortType); ?> ">状态<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照创建时间<?php echo ($sortType); ?> ">创建时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('update_time','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照完成时间<?php echo ($sortType); ?> ">完成时间<?php if(($order)  ==  "update_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('fail_reason','<?php echo ($sort); ?>','User','userIdentityModifyLog')" title="按照审核失败原因<?php echo ($sortType); ?> ">审核失败原因<?php if(($order)  ==  "fail_reason"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td><td>&nbsp;<?php echo ($item["id"]); ?></td><td>&nbsp;<?php echo ($item["user_id"]); ?></td><td>&nbsp;<?php echo ($item["real_name"]); ?></td><td>&nbsp;<?php echo (get_id_type_name($item["id_type"])); ?></td><td>&nbsp;<?php echo ($item["idno"]); ?></td><td>&nbsp;<?php echo ($item["order_id"]); ?></td><td>&nbsp;<?php echo (get_user_identity_status($item["status"])); ?></td><td>&nbsp;<?php echo (format_date($item["create_time"])); ?></td><td>&nbsp;<?php echo (format_date($item["update_time"])); ?></td><td>&nbsp;<?php echo ($item["fail_reason"]); ?></td><td><a href="javascript:getUserIdentityModifyInfo('<?php echo ($item["id"]); ?>')">查看</a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="12" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>

<!-- 查看 -->
<div id='dialogbox_msg' class="dialog-box dialogbox" style="display: none; position: absolute; overflow: hidden; z-index: 999; width: 800px; top: 200px; right: 200px;">
    <div class="dialog-header">
        <div class="dialog-title">详情</div>
        <div class="dialog-close" onclick='close_div()'></div>
    </div>
    <div class="dialog-content" id="bankInfo" >
    </div>
</div>
<!--  -->
<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>
function close_div() {
    $('.dialogbox').hide();
}
function open_div(id,user_name,des) {
    $('#aid').val(id);
    $('#user_name').text(user_name);
    //$('#msgarea').val(des);
    $('#dialogbox_div').show();
    $('#dialogbox_msg').hide();
    // 清空上一个弹框赋的值
    $("#status_value").val("");
    $("input[name='status']").val("");
    $("input[name='failReasonType']").val("");
}
//获取银行信息
function getUserIdentityModifyInfo(id) {
    if(id) {
        $.ajax({
               type: "POST",
               url: "/m.php?m=User&a=getUserIdentityModifyInfo",
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