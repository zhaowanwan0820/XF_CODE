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
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<style>
    table .warn-cell {color:#F00;}
</style>
<div class="main">
<div class="main_title">用户预约登记列表</div>
<div class="blank5"></div>
<?php function f_show_status($status) {
    if ($status == 1) {
        return '成功';
    }
    return '取消';
}
function f_to_date($stamp) {
    if (empty($stamp)) {
        return '';
    }
    return date('Y-m-d H:i:s', $stamp);
}
function f_get_realname($userId, $real_name) {
    return "<a href='/m.php?m=User&a=index&user_id=$userId' target='_blank'>$real_name</a>";
}
function f_to_url($reserved_session) {
    return "<a href='/m.php?m=User&a=booking_index&user_id=&reserved_session=$reserved_session'>$reserved_session</a>";
} ?>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        用户ID：<input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" />
        用户姓名：<input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" />
        手机号：<input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>" />
        预约状态：<select id="status" name="status">
            <option value="" <?php if(isset($_REQUEST['status']) and intval($_REQUEST['status']) == ''): ?>selected="selected"<?php endif; ?>><?php echo L("ALL");?></option>
            <option value="1" <?php if(isset($_REQUEST['status']) and intval($_REQUEST['status']) == '1'): ?>selected="selected"<?php endif; ?>>成功</option>
            <option value="0" <?php if(isset($_REQUEST['status']) and $_REQUEST['reserved_session'] != '' and intval($_REQUEST['status']) == '0'): ?>selected="selected"<?php endif; ?>>取消</option>
        </select><br />
        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="booking_index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="导出" onclick='javascript:export_csv()'/>
    </form>
</div>
<div class="blank5"></div>

<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="15" class="topTd" >&nbsp; </td></tr><tr class="row" ><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','booking_index')" title="按照编号<?php echo ($sortType); ?> ">编号<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','User','booking_index')" title="按照用户Id<?php echo ($sortType); ?> ">用户Id<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','User','booking_index')" title="按照用户姓名<?php echo ($sortType); ?> ">用户姓名<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('mobile','<?php echo ($sort); ?>','User','booking_index')" title="按照手机号<?php echo ($sortType); ?> ">手机号<?php if(($order)  ==  "mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('idno','<?php echo ($sort); ?>','User','booking_index')" title="按照身份证号<?php echo ($sortType); ?> ">身份证号<?php if(($order)  ==  "idno"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('wx_cash','<?php echo ($sort); ?>','User','booking_index')" title="按照网信余额<?php echo ($sortType); ?> ">网信余额<?php if(($order)  ==  "wx_cash"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('wx_freeze','<?php echo ($sort); ?>','User','booking_index')" title="按照网信冻结<?php echo ($sortType); ?> ">网信冻结<?php if(($order)  ==  "wx_freeze"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('ph_cash','<?php echo ($sort); ?>','User','booking_index')" title="按照普惠余额<?php echo ($sortType); ?> ">普惠余额<?php if(($order)  ==  "ph_cash"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('ph_freeze','<?php echo ($sort); ?>','User','booking_index')" title="按照普惠冻结<?php echo ($sortType); ?> ">普惠冻结<?php if(($order)  ==  "ph_freeze"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('corpus','<?php echo ($sort); ?>','User','booking_index')" title="按照待收本金<?php echo ($sortType); ?> ">待收本金<?php if(($order)  ==  "corpus"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('reserved_session','<?php echo ($sort); ?>','User','booking_index')" title="按照预约场次<?php echo ($sortType); ?> ">预约场次<?php if(($order)  ==  "reserved_session"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('city_name','<?php echo ($sort); ?>','User','booking_index')" title="按照预约城市<?php echo ($sortType); ?> ">预约城市<?php if(($order)  ==  "city_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('time_range','<?php echo ($sort); ?>','User','booking_index')" title="按照预约时段<?php echo ($sortType); ?> ">预约时段<?php if(($order)  ==  "time_range"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('reserved_at','<?php echo ($sort); ?>','User','booking_index')" title="按照预约提交时间<?php echo ($sortType); ?> ">预约提交时间<?php if(($order)  ==  "reserved_at"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','User','booking_index')" title="按照预约状态<?php echo ($sortType); ?> ">预约状态<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?><tr class="row" ><td>&nbsp;<?php echo ($user["id"]); ?></td><td>&nbsp;<?php echo ($user["user_id"]); ?></td><td>&nbsp;<?php echo (f_get_realname($user["user_id"],$user['real_name'])); ?></td><td>&nbsp;<?php echo ($user["mobile"]); ?></td><td>&nbsp;<?php echo ($user["idno"]); ?></td><td>&nbsp;<?php echo ($user["wx_cash"]); ?></td><td>&nbsp;<?php echo ($user["wx_freeze"]); ?></td><td>&nbsp;<?php echo ($user["ph_cash"]); ?></td><td>&nbsp;<?php echo ($user["ph_freeze"]); ?></td><td>&nbsp;<?php echo ($user["corpus"]); ?></td><td>&nbsp;<?php echo (f_to_url($user["reserved_session"])); ?></td><td>&nbsp;<?php echo ($user["city_name"]); ?></td><td>&nbsp;<?php echo ($user["time_range"]); ?></td><td>&nbsp;<?php echo (f_to_date($user["reserved_at"])); ?></td><td>&nbsp;<?php echo (f_show_status($user["status"])); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="15" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
<div class="blank5"></div>
</div>
<script type="text/javascript" charset="utf-8">
function get_query_string() {
    querystring = '';
    querystring += '&user_id=' +$("input[name='user_id']").val();
    querystring += "&real_name="+$("input[name='real_name']").val();
    querystring += "&mobile="+$("input[name='mobile']").val();
    querystring += "&reserved_session="+$("select[name='reserved_session']").val();
    querystring += "&status="+$("select[name='status']").val();
    return querystring;
}
function export_csv() {
    window.location.href = ROOT+'?m=User&a=get_booking_csv'+get_query_string();
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