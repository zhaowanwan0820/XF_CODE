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
<script>
function yifang_check(info){
    $('.button').css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
    if(!info){
    var msg = '数据正确！';
    alert(msg);
    }
    else{
    alert("有错误数据,点击导入则导入正确数据，如需下载错误数据请点击下载错误数据\n"+info);
    }
}

function yifang_alert(info,reload){
    $('.button').css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
    if(info){
        alert(info);
    }
    if(reload){
        window.location.href = ROOT + '?m=User&a=ChangeGroupLevelLog';
    }
}


function make_check(is_check){
    $('#is_check').val(is_check);
}


</script>

<?php function get_group_name($group_id){
    $group = M("UserGroup")->where("id=".$group_id)->find();
    return $group?$group['name']:'未知';
}

function get_level_name($level_id){
    $level = M("UserCouponLevel")->where("id=".$level_id)->find();
    return ($level)?$level['name']:'未知';
} ?>
<div class="main">
    <div class="main_title">已成功导入的用户<?php echo ($dberror); ?></div>
    <div class="blank5"></div>
    <form name="search" action="__APP__" method="post" enctype="multipart/form-data">
    <div class="button_row">
        <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="User" />
        <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="changeGroupLevelCSV" />
        <input type='file' name='upfile' style='width:150px'>
        <input type='submit'  value='检查数据' class="button" onclick='make_check(1)'>
        <input type='submit'  value='导入' class="button" onclick='make_check(0)'>&nbsp;<a href="/m.php?m=User&a=downLoad_csv_templete">模板下载</a>&nbsp;&nbsp;<span style="color:red;"><input type="checkbox" name="check_group_id" value="1">进行同一机构验证(最多一次处理200个用户)导入的文件类型为csv格式；数据共5列：序号、姓名、手机号、分组ID、优惠码等级ID；导入之前请先点击“检查数据”，导入时只导入匹配正确的数据。</span>
    </div>
    </form>
    <div class="blank5"></div>
    <div class="search_row">
    <form name="search" action="__APP__" method="get">
        用户名：<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" />
        会员编号：<input type="text" class="textbox" name="user_num" value="<?php echo trim($_REQUEST['user_num']);?>" />
        <!--手机号：<input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>"/>-->
        操作人员：<input type="text" class="textbox" name="adm_name" value="<?php echo trim($_REQUEST['adm_name']);?>"/>
        <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="changeGroupLevelLog" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<div class="blank5"></div>
    <!-- Think 系统列表组件开始 -->
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan="20" class="topTd">&nbsp;</td>
        </tr>
        <tr class="row">
            <th>id</th>
            <th>会员id</th>
            <th>用户名</th>
            <th>姓名</th>
            <th>会员编号</th>
            <th>手机号</th>
            <th>旧的分组</th>
            <th>新的分组</th>
            <th>操作时间</th>
            <th>操作人员</th>
        </tr>
        <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
            <td><?php echo ($item["id"]); ?></td>
            <td><?php echo (de32Tonum($item["user_num"])); ?></td>
            <td><a target="_blank" href="/m.php?m=User&a=index&user_id=<?php echo (de32Tonum($item["user_num"])); ?>"><?php echo ($item["user_name"]); ?></a></td>
            <td><?php echo ($item["real_name"]); ?></td>
            <td><?php echo ($item["user_num"]); ?></td>
            <td><?php echo ($item["mobile"]); ?></td>
            <td title='<?php echo ($item["old_groupid"]); ?>-<?php echo ($item["old_levelid"]); ?>'><?php echo (get_group_name($item["old_groupid"])); ?>-<?php echo (get_level_name($item["old_levelid"])); ?></td>
            <td title='<?php echo ($item["new_groupid"]); ?>-<?php echo ($item["new_levelid"]); ?>'><?php echo (get_group_name($item["new_groupid"])); ?>-<?php echo (get_level_name($item["new_levelid"])); ?></td>
            <td><?php echo ($item["update_time"]); ?></td>
            <td><?php echo ($item["adm_name"]); ?></td>
        </tr><?php endforeach; endif; ?>
        <tr>
            <td colspan="20" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
    <!-- Think 系统列表组件结束 --> 
    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
<script type="text/javascript">
    function make_check(flag){
        action = flag == 0? 'changeGroupLevelCSV':'checkGroupLevelCSV';
        $("[name='a']").val(action);
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