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
<script type="text/javascript" src="__TMPL__Common/js/input-click.js"></script>

<?php function get_dmdeal_status($deal_status, $row) {
    $dealStatus = $row['dealStatus'];
    return $dealStatus[$deal_status];
}

function get_jys_name($type, $row)
{
    $jys = $row['jysArr'];
    return $jys[$type] ? $jys[$type] : "无";
}

function get_jys_user_name($user_id, $row)
{
    return $row['userName']."(".$user_id.")";
}

function get_date($time)
{
    return $time ? date('Y-m-d H:i:s', $time) : '--';
} ?>

<div class="main">
<div class="main_title">标的列表</div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="新增" onclick="add();" />
    <!--<a href="/m.php?m=DarkMoonDeal&a=trash">已置废</a>-->
</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        交易所备案编号：<input type="text" class="textbox" name="jys_record_number" value="<?php echo trim($_REQUEST['jys_record_number']);?>"  />
        交易所：
        <select name="jys_id">
            <option value="0" <?php if(intval($_REQUEST['jys_id']) == 0): ?>selected="selected"<?php endif; ?>> 所有平台 </option>
            <?php if(is_array($jys)): foreach($jys as $jys_k=>$jys_v): ?><option value="<?php echo ($jys_k); ?>" <?php if(intval($_REQUEST['jys_id']) == $jys_k): ?>selected="selected"<?php endif; ?>><?php echo ($jys_v); ?></option><?php endforeach; endif; ?>
        </select>

        发行人：
        <input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" size="10" />

        状态：
        <select name="deal_status">
            <option value="" <?php if($_REQUEST['deal_status'] == ''): ?>selected="selected"<?php endif; ?>>所有状态</option>
            <?php if(is_array($dealStatus)): foreach($dealStatus as $status_k=>$status_v): ?><option value="<?php echo ($status_k); ?>" <?php if(strval($_REQUEST['deal_status']) === strval($status_k)): ?>selected="selected"<?php endif; ?>><?php echo ($status_v); ?></option><?php endforeach; endif; ?>
        </select>
        <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
        <input type="hidden" value="DarkMoonDeal" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>
</div>

<div class="blank5"></div>
<?php if(empty($isTrash)): ?><!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="8" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px     "><a href="javascript:sortBy('id','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('jys_record_number','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照交易所备案编号     <?php echo ($sortType); ?> ">交易所备案编号     <?php if(($order)  ==  "jys_record_number"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('jys_id','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照交易所     <?php echo ($sortType); ?> ">交易所     <?php if(($order)  ==  "jys_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照发行人     <?php echo ($sortType); ?> ">发行人     <?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('deal_status','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照状态     <?php echo ($sortType); ?> ">状态     <?php if(($order)  ==  "deal_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照<?php echo L("CREATE_TIME");?><?php echo ($sortType); ?> "><?php echo L("CREATE_TIME");?><?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$DarkmoonDeal): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($DarkmoonDeal["id"]); ?>"></td><td>&nbsp;<?php echo ($DarkmoonDeal["id"]); ?></td><td>&nbsp;<?php echo ($DarkmoonDeal["jys_record_number"]); ?></td><td>&nbsp;<?php echo (get_jys_name($DarkmoonDeal["jys_id"],$DarkmoonDeal)); ?></td><td>&nbsp;<?php echo (get_jys_user_name($DarkmoonDeal["user_id"],$DarkmoonDeal)); ?></td><td>&nbsp;<?php echo (get_dmdeal_status($DarkmoonDeal["deal_status"],$DarkmoonDeal)); ?></td><td>&nbsp;<?php echo (get_date($DarkmoonDeal["create_time"])); ?></td><td><a href="javascript:edit('<?php echo ($DarkmoonDeal["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a href="javascript: delDeal('<?php echo ($DarkmoonDeal["id"]); ?>')">作废</a>&nbsp;<a href="javascript:genTimestamp('<?php echo ($DarkmoonDeal["id"]); ?>')"><?php if(0== (is_array($DarkmoonDeal)?$DarkmoonDeal["status"]:$DarkmoonDeal->status)){ ?>加盖时间戳<?php } ?></a><a href="javascript: updateDeal(<?php echo ($DarkmoonDeal["id"]); ?>)"><?php if(1== (is_array($DarkmoonDeal)?$DarkmoonDeal["status"]:$DarkmoonDeal->status)){ ?>生成合同<?php } ?></a>&nbsp;<a href="javascript: contractList('<?php echo ($DarkmoonDeal["id"]); ?>')">合同列表</a>&nbsp;<a href="javascript: sendSms('<?php echo ($DarkmoonDeal["id"]); ?>')">发送短信</a>&nbsp;<a href="javascript: sendEmail('<?php echo ($DarkmoonDeal["id"]); ?>')">发送邮件</a>&nbsp;<a href="javascript: goloadlist('<?php echo ($DarkmoonDeal["id"]); ?>')">投资客户明细</a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="8" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->

<?php else: ?>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="8" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px     "><a href="javascript:sortBy('id','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('jys_record_number','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照交易所备案编号     <?php echo ($sortType); ?> ">交易所备案编号     <?php if(($order)  ==  "jys_record_number"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('jys_id','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照交易所     <?php echo ($sortType); ?> ">交易所     <?php if(($order)  ==  "jys_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照发行人     <?php echo ($sortType); ?> ">发行人     <?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('deal_status','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照状态     <?php echo ($sortType); ?> ">状态     <?php if(($order)  ==  "deal_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','DarkMoonDeal','index')" title="按照<?php echo L("CREATE_TIME");?><?php echo ($sortType); ?> "><?php echo L("CREATE_TIME");?><?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$DarkmoonDeal): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($DarkmoonDeal["id"]); ?>"></td><td>&nbsp;<?php echo ($DarkmoonDeal["id"]); ?></td><td>&nbsp;<?php echo ($DarkmoonDeal["jys_record_number"]); ?></td><td>&nbsp;<?php echo (get_jys_name($DarkmoonDeal["jys_id"],$DarkmoonDeal)); ?></td><td>&nbsp;<?php echo (get_jys_user_name($DarkmoonDeal["user_id"],$DarkmoonDeal)); ?></td><td>&nbsp;<?php echo (get_dmdeal_status($DarkmoonDeal["deal_status"],$DarkmoonDeal)); ?></td><td>&nbsp;<?php echo (get_date($DarkmoonDeal["create_time"])); ?></td><td><a href="javascript:edit('<?php echo ($DarkmoonDeal["id"]); ?>')">查看</a>&nbsp;<a href="javascript:goloadlist('<?php echo ($DarkmoonDeal["id"]); ?>')">投资客户明细</a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="8" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 --><?php endif; ?>

<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>
function goloadlist(id) {
    window.location.href=ROOT+'?m=DarkMoonDealLoad&a=index&dealid='+id;
}
function updateDeal(id) {
    window.location.href=ROOT+'?m=DarkMoonDeal&a=updateDealStatus&id='+id;
}
function genTimestamp(id){
    window.location.href=ROOT+'?m=DarkMoonDeal&a=genTimestamp&id='+id;
}
function delDeal(id){
    if (window.confirm("确认要作废吗？")) {
        window.location.href=ROOT+'?m=DarkMoonDeal&a=del&id='+id;
    }
}
function sendSms(id){
    if (window.confirm("确认要发送吗")) {
        window.location.href=ROOT+'?m=DarkMoonDeal&a=sendSms&id='+id;
    }
}
function contractList(id) {
    window.location.href=ROOT+'?m=DarkMoonContract&a=index&dealid='+id;
}
function sendEmail(id){
    if(window.confirm("确认要发送邮件吗？")){
        window.location.href=ROOT+'?m=DarkMoonDeal&a=sendEmail&id='+id;
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