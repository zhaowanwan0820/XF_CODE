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
        用户id：<input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" />
        接收手机号：<input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>" />
        操作人：<input type="text" class="textbox" name="apply_uname" value="<?php echo trim($_REQUEST['apply_uname']);?>" />
        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="userPwdResetAudit" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="<?php echo L("EXPORT");?>" onclick="export_csv()" />
    </form>
</div>

<div class="blank5"></div>

<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="22" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th>序号</th>
            <th>用户id</th>
            <th>会员名称</th>
            <th>接收手机号</th>
            <th>操作人</th>
            <th>操作时间</th>
            <th>审核人</th>
            <th>审核时间</th>
            <th>审核状态</th>
            <th>操作</th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$audit): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>
                &nbsp;<?php echo ($audit["id"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($audit["user_id"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($audit["user_name"]); ?>
            </td>
            <td <?php if($audit["is_same_mobile"] != 1): ?>style="background:red"<?php endif; ?>>
                <?php if($audit["status"] == 0): ?>&nbsp;<?php echo ($audit["mobile"]); ?>
                <?php else: ?>
                &nbsp;<?php echo ($audit["mobileFormat"]); ?><?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo ($audit["apply_uname"]); ?>
            </td>
            <td>
                &nbsp;<?php echo (format_date($audit["apply_time"])); ?>
            </td>
            <td>
                &nbsp;<?php echo ($audit["audit_uname"]); ?>
            </td>
            <td>
                &nbsp;<?php echo (format_date($audit["audit_time"])); ?>
            </td>
            <td>
                &nbsp;<?php echo ($audit["status_text"]); ?>
            </td>
            <td>
                <?php if($audit["status"] == 0): ?><a href="javascript:;" onclick="do_audit(<?php echo ($audit["id"]); ?>, 1)">通过</a>
                <a href="javascript:;" onclick="do_audit(<?php echo ($audit["id"]); ?>, 2)">拒绝</a><?php endif; ?>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
</table>



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

function do_audit(id, status) {
    if (!window.confirm('确认操作？')) {
        return false;
    }
    window.location.href="/m.php?m=User&a=confirm_edit_password&id="+id+"&status="+status
}
function get_query_string(){
    querystring = '';
    querystring += "&user_id="+$("input[name='user_id']").val();
    querystring += "&mobile="+$("input[name='mobile']").val();
    querystring += "&apply_uname="+$("input[name='apply_uname']").val();
    return querystring;

}
function export_csv() {
    window.location.href = ROOT+'?export=1&m=User&a=userPwdResetAudit'+get_query_string();
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