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
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<div class="main">
<div class="main_title">服务关系修改 </div>

<div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
                       投资人id：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['user_id']); ?>" name="user_id">
                       投资人手机号：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['user_mobile']); ?>" name="user_mobile">
                       会员编号：<input  class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['user_num']); ?>" name="user_num">
                       当前服务人id：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['refer_user_id']); ?>" name="refer_user_id">
                       当前服务人手机号：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['refer_user_mobile']); ?>" name="refer_user_mobile">
                       当前服务人邀请码：<input class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['short_alias']); ?>" name="short_alias">
            　　　　　 操作人员：<input  class="textbox" type="text" style="width:100px;" value="<?php echo ($_REQUEST['operator']); ?>" name="operator">
            　　　　　 操作时间：<input type="textbox" id="begin"　 style="width:100px;" onclick="show_cal(this)" class="textbox" name="begin" value="<?php echo trim($_REQUEST['begin']);?>" style="width:135px;" />
                        至<input type="textbox" id="end" 　 style="width:100px;"　class="textbox" onclick="show_cal(this)" name="end" value="<?php echo trim($_REQUEST['end']);?>" style="width:135px;" />
            <input type="hidden" value="CouponBind" name="m" />
            <input type="hidden" value="index" name="a" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        </form>
    </div>

<div class="blank5"></div>

<div class="search_row">
            新服务人邀请码：<input class="textbox" type="text" style="width:100px;" value="" name="new_short_alias" id ="new_short_alias">
    <input type="button" class="button" value="替换" onclick="change_short_alias();" />

</div>
    <div class="blank5"></div>
        <form method="post" enctype="multipart/form-data" action="__APP__">
        <div class="button_row">
            <input type="hidden" name="a" value="importCsvShortAlias">
            <input type="hidden" name="m" value="CouponBind">
            <input type='hidden' name='is_check' id='is_check' value='0'>
            <input type="file" name="upfile" style="width:150px" >
            <a href="?m=CouponBind&a=download_tpl">模板下载</a>
            <!--
            
            <input type="submit" class="button" value="数据校验" onclick='make_check(0);' />
        -->
            <input type="submit" class="button" value="批量导入" onclick='make_check(1);' />
            <strong style="color:#ff0000">每次导入最多2000条，请导入csv格式。如需下载错误数据请先点击下载错误数据，点击批量导入则将正确数据导入但并不提示错误数据.</strong>
            <input type="checkbox" name="check_group_id" value="1">进行同一机构验证 
        </div>
        </form>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="15" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th><a href="javascript:sortBy('user_id_url','<?php echo ($sort); ?>','CouponBind','index')" title="按照投资人ID     <?php echo ($sortType); ?> ">投资人ID     <?php if(($order)  ==  "user_id_url"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_mobile','<?php echo ($sort); ?>','CouponBind','index')" title="按照投资人手机号     <?php echo ($sortType); ?> ">投资人手机号     <?php if(($order)  ==  "user_mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_group_name','<?php echo ($sort); ?>','CouponBind','index')" title="按照投资人会员组     <?php echo ($sortType); ?> ">投资人会员组     <?php if(($order)  ==  "user_group_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('real_name','<?php echo ($sort); ?>','CouponBind','index')" title="按照投资人姓名     <?php echo ($sortType); ?> ">投资人姓名     <?php if(($order)  ==  "real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_num','<?php echo ($sort); ?>','CouponBind','index')" title="按照投资人会员编号     <?php echo ($sortType); ?> ">投资人会员编号     <?php if(($order)  ==  "user_num"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('short_alias','<?php echo ($sort); ?>','CouponBind','index')" title="按照当前服务人邀请码     <?php echo ($sortType); ?> ">当前服务人邀请码     <?php if(($order)  ==  "short_alias"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_user_id','<?php echo ($sort); ?>','CouponBind','index')" title="按照当前服务人ID     <?php echo ($sortType); ?> ">当前服务人ID     <?php if(($order)  ==  "refer_user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_user_mobile','<?php echo ($sort); ?>','CouponBind','index')" title="按照当前服务人手机号     <?php echo ($sortType); ?> ">当前服务人手机号     <?php if(($order)  ==  "refer_user_mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_user_group_name','<?php echo ($sort); ?>','CouponBind','index')" title="按照当前服务人会员组     <?php echo ($sortType); ?> ">当前服务人会员组     <?php if(($order)  ==  "refer_user_group_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('service_status','<?php echo ($sort); ?>','CouponBind','index')" title="按照服务人服务标识     <?php echo ($sortType); ?> ">服务人服务标识     <?php if(($order)  ==  "service_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_real_name','<?php echo ($sort); ?>','CouponBind','index')" title="按照当前服务人姓名     <?php echo ($sortType); ?> ">当前服务人姓名     <?php if(($order)  ==  "refer_real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('admin_id','<?php echo ($sort); ?>','CouponBind','index')" title="按照操作人     <?php echo ($sortType); ?> ">操作人     <?php if(($order)  ==  "admin_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_fixed','<?php echo ($sort); ?>','CouponBind','index')" title="按照是否绑定     <?php echo ($sortType); ?> ">是否绑定     <?php if(($order)  ==  "is_fixed"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('update_time','<?php echo ($sort); ?>','CouponBind','index')" title="按照更新时间     <?php echo ($sortType); ?> ">更新时间     <?php if(($order)  ==  "update_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($item["user_id"]); ?>"></td><td>&nbsp;<?php echo ($item["user_id_url"]); ?></td><td>&nbsp;<?php echo ($item["user_mobile"]); ?></td><td>&nbsp;<?php echo ($item["user_group_name"]); ?></td><td>&nbsp;<?php echo ($item["real_name"]); ?></td><td>&nbsp;<?php echo ($item["user_num"]); ?></td><td>&nbsp;<?php echo ($item["short_alias"]); ?></td><td>&nbsp;<?php echo ($item["refer_user_id"]); ?></td><td>&nbsp;<?php echo ($item["refer_user_mobile"]); ?></td><td>&nbsp;<?php echo ($item["refer_user_group_name"]); ?></td><td>&nbsp;<?php echo ($item["service_status"]); ?></td><td>&nbsp;<?php echo ($item["refer_real_name"]); ?></td><td>&nbsp;<?php echo (get_admin_name($item["admin_id"])); ?></td><td>&nbsp;<?php echo ($item["is_fixed"]); ?></td><td>&nbsp;<?php echo (to_date($item["update_time"])); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="15" class="bottomTd"> &nbsp;</td></tr></table>
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

<script type="text/javascript">
    function show_cal(obj) {
        obj.blur();
        return showCalendar(obj.id, '%Y-%m-%d %H:%M:%S', true, false, obj.id);
    }

</script>

<script type="text/javascript">

    function make_check(is_check){
      $('#is_check').val(is_check);
    }




    //复制全局返利规则
    function change_short_alias() {

        new_short_alias = $("#new_short_alias").val();

        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert("请选择要替换的用户");
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");

        if(confirm("请确认要替换吗？"))
            $.ajax({
                    url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=changeShortAlias&user_ids="+id+"&new_short_alias="+new_short_alias,
                    data: "ajax=1",
                    dataType: "json",
                    success: function(obj){
                        alert(obj.info);
                        if(obj.status==1)
                        {
                            location.href=location.href;
                        }
                    }
            });

    }
 </script>