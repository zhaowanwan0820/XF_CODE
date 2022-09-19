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
<div class="main_title">审核 &nbsp;<a href="<?php echo u("SmsTask/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
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
    <tr>
        <td class="item_title"></td>
        <td class="item_input">
            <input type="button" class="button" name="reback" value="退回"  onclick="location.href='/m.php?m=SmsTask&a=check&reback=1&id=<?php echo $info['id'];?>'"/>
            <input type="button" class="button" name="check_pass" value="审核通过" onclick="location.href='/m.php?m=SmsTask&a=check&check_pass=1&id=<?php echo $info['id'];?>'"/>
        </td>
    </tr>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>
</div>

</body>
</html>