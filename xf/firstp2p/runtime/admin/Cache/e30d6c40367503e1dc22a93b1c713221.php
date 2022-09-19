<?php if (!defined('THINK_PATH')) exit();?>﻿

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

<link href="__ROOT__/static/admin/easyui/themes/default/easyui.css" rel="stylesheet" type="text/css" />
<link href="__ROOT__/static/admin/easyui/themes/icon.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="__ROOT__/static/admin/easyui/jquery.easyui.min.js"></script> 
<script type="text/javascript" src="__ROOT__/static/admin/easyui/ajaxfileupload.js"></script> 
<script type="text/javascript" src="__ROOT__/static/admin/easyui/p2popen.js"></script>
<div class="main">
<div class="main_title">查看  &nbsp;<a href="<?php echo u("SmsTask/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form action="__APP__" method="post" enctype="multipart/form-data" id="form1">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">短信内容:</td>
        <td class="item_input"><?php echo $info['content'];?></td>
    </tr>
    <tr>
        <td class="item_title">工单编号:</td>
        <td class="item_input"><?php echo $info['work_num'];?></td>
    </tr>
    <tr>
        <td class="item_title">定时/及时发送:</td>
        <td class="item_input"><?php echo empty($info['expect_send_time']) ? "及时发送" : "定时发送"; ?></td>
    </tr>
    <tr>
        <td class="item_title">发送时间:</td>
        <td class="item_input"><?php echo empty($info['expect_send_time']) ? "通过后及时发送" : date("Y-m-d H:i", $info['expect_send_time']); ?></td>
    </tr>
    <tr>
        <td class="item_title">短信发放对象:</td>
        <td class="item_input">
            <?php if($info['send_type'] == 0){ ?>
          <div class="filebox">
           <div style="display:block;" class="JS_bonus_money_box">
            <div style="text-align: left;padding: 5px 0px;" class="">
                <div class="JS_bonus_money" style="display:block;"></div>
                <div class="JS_file_name" style="display:block;color:#25a3e0;text-decoration: underline;">
                    <a href="<?php echo ($prefix); ?>"><?php echo $attach['filename'];?></a>
                </div>
            </div>
            </div>
            </div>
            <?php }elseif($info['send_type'] == 1){
                echo "白泽任务id：".$info['attachment_id'];
              }elseif($info['send_type'] == 2){
                echo "用户id：".$info['attachment_id'];
              }elseif($info['send_type'] == 3){
                echo "用户手机号：".$info['extinfo']['mobile'];
              }
            ?>
          </td>
    </tr>
    <?php if($info['send_type'] == 1 && $info['task_status'] == 6){ ?>
    <tr>
        <td>发送结果查询：</td>
        <td>
            <input type="text" id="serchid"">
            <button type="button" onclick="retserch();">查询</button>
            <span id="js_serret" style="color:red"></span>
        </td>
    </tr>
    <?php }?>
    </table>
</form>
</div>
<style type="text/css">
.file_upload_btn{padding: 0 15px;height: 30px;line-height: 30px;background-color: #25a3e0;text-align: center;display: inline-block;color: #fff !important;font-size: 14px;border-radius: 5px;cursor: pointer;border: 1px solid #25a3e0;vertical-align: middle;position: relative;overflow: hidden;margin: 0 8px;}
.fileinput{width: 100%;height: 100%;opacity: 0;cursor: pointer;position: absolute;top: 0px;left: 0px;}
</style>
<script type="text/javascript">
function retserch(){
    var url = "/m.php?m=SmsTask&a=retser&id=<?php echo $info['id'];?>";
    var serval = $("#serchid").val();
    if(serval.length = 0 || isNaN(serval)){
        alert("请检测输入");
        return false;
    }
    $.post(url, {"val":serval}, function(ret){
        var html = ret.code == 0 ? "发送结果:已发送" : "发送结果:未发送";
        $("#js_serret").html(html);
    }, "json");
}
</script>

</body>
</html>