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

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<link href="__ROOT__/static/admin/easyui/themes/default/easyui.css" rel="stylesheet" type="text/css" />
<link href="__ROOT__/static/admin/easyui/themes/icon.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="__ROOT__/static/admin/easyui/jquery.easyui.min.js"></script> 
<script type="text/javascript" src="__ROOT__/static/admin/easyui/ajaxfileupload.js"></script> 
<script type="text/javascript" src="__ROOT__/static/admin/easyui/p2popen.js"></script>
<div class="main">
<div class="main_title">修改 &nbsp;<a href="<?php echo u("SmsTask/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form action="__APP__" method="post" enctype="multipart/form-data" id="form1">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">短信内容:</td>
        <td class="item_input">
        <textarea id="content" name="content" cols="80" rows="5" placeholder="请在这里输入要发送的短信内容"><?php echo $info['content'];?></textarea>
        </td>
    </tr>
    <tr>
        <td class="item_title">工单编号</td>
        <td class="item_input"> <input type="text" name="work_num" value="<?php echo $info['work_num'];?>"></td>
    </tr>
    <tr>
        <td class="item_title">发送方式</td>
        <td>
            <select name="send_type" class="js_send_type">
                <option value="1" <?php echo $info['send_type'] == 1 ? 'selected="selected"' : "";?>>导入白泽任务</option>
                <option value="0" <?php echo $info['send_type'] == 0 ? 'selected="selected"' : "";?>>导入CSV</option>
                <option value="2" <?php echo $info['send_type'] == 2 ? 'selected="selected"' : "";?>>用户id</option>
                <option value="3" <?php echo $info['send_type'] == 3 ? 'selected="selected"' : "";?>>用户手机号</option>
            </select>
        </td>
    </tr>
    <tr class="js_0_tr <?php echo $info['send_type'] == 0 ? '' : "hidden";?>">
        <td class="item_title">短信发放对象:</td>
        <td class="item_input">
      <div class="filebox">
        <input type="text" name="file" class="JS-baseconfig_input step_text w280" value="<?php echo empty($attach['filename']) ? "" : $attach['filename'];?>" id="upload_files" data-rule="短信发放对象: required;"/>
        <input type="hidden" name="attachment_id" id="attachment_id" value="<?php echo empty($info['attachment_id']) ? 0 : $info['attachment_id'];?>">
        <span class="file_upload_btn JS-app_icon" uodata-file="uodata-file">导入</span>
        <input type="button" class="button" value="CSV模版下载" onclick="location.href='/static/sms_template.csv';"/>
        <div style="display:block;" class="JS_bonus_money_box">
            <div style="text-align: left;padding: 5px 0px;" class="">
                <div class="JS_bonus_money" style="display:block;"></div>
                <div class="JS_file_name" style="display:block;color:#25a3e0;text-decoration: underline;">
                    <?php echo empty($attach['filename']) ? "" : '<a href="'.$prefix.'">'.$attach['filename'].'</a>';?>
                </div>
            </div>
        </div>
      </div>
        </td>
    </tr>
    <tr class="js_1_tr <?php echo $info['send_type'] == 1 ? '' : "hidden";?>">
        <td class="item_title">白泽任务id</td>
        <td class="item_input"> <input type="text" name="send_type_1_value" value="<?php echo $info['send_type'] == 1 ? $info['attachment_id'] : '';?>">
        <button type="button" onclick="baize.Fimport();">导入白泽任务</button>
        <span class="js_1_msg"></span></td>
    </tr>
    <tr class="js_2_tr <?php echo $info['send_type'] == 2 ? '' : "hidden";?>">
        <td class="item_title">用户id</td>
        <td class="item_input"> <input type="text" name="send_type_2_value" value="<?php echo $info['send_type'] == 2 ? $info['attachment_id'] : '';?>"><span class="js_2_msg"></span></td>
    </tr>
    <tr class="js_3_tr <?php echo $info['send_type'] == 3 ? '' : "hidden";?>">
        <td class="item_title">用户手机号</td>
        <td class="item_input"> <input type="text" name="send_type_3_value" value="<?php echo $info['send_type'] == 3 ? $info['extinfo']['mobile'] : '';?>"><span class="js_3_msg"></span></td>
    </tr>
    <tr>
        <td class="item_title">发送开始时间</td>
        <td class="item_input"> 
            <input type="text" onclick="show_cal(this)" class="textbox" name="expect_send_time" value="<?php echo empty($info['expect_send_time']) ? '' : date('Y-m-d H:i:s', $info['expect_send_time']);?>"/>
            <button type="button" onclick="del_time();">清空时间</button>
        <span>*不填默认在审核成功后即时发送</span></td>
    </tr>
    <tr>
        <td class="item_title"></td>
        <td class="item_input">
            <input type="hidden" name="check" id="sub_check">
            <input type="button" class="button" name="edit" value="修改" onclick="saveData();" />
            <input type="hidden" name="id" value="<?php echo $info['id'];?>" />
            <input type="button" class="button" name="submit_check" value="提交审核" onclick="submitCheck();" />
        </td>
    </tr>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>
</form>
</div>
<div id="error" style="display:none;border: 1px rgb(169, 169, 169) solid;width: 575px;margin-left: 156px;padding: 6px;margin-top: 10px;font-size: 15px;"><div style="color:red;margin-bottom: 10px;">导入报错！</div>
    导入错误信息：
    <div class="error_message" style="font-size: 13px;margin-top: 10px;margin-left: 15px;"></div>
    <input id="submitJustdo" type="button" class="button" name="submit_justdo" value="忽略并提交" onclick="submitCheck();">
</div>
<style type="text/css">
.file_upload_btn{padding: 0 15px;height: 30px;line-height: 30px;background-color: #25a3e0;text-align: center;display: inline-block;color: #fff !important;font-size: 14px;border-radius: 5px;cursor: pointer;border: 1px solid #25a3e0;vertical-align: middle;position: relative;overflow: hidden;margin: 0 8px;}
.fileinput{width: 100%;height: 100%;opacity: 0;cursor: pointer;position: absolute;top: 0px;left: 0px;}
.hidden{display:none;}
</style>
<script type="text/javascript">
var baize = {
    hasId : <?php echo $info['send_type'] == 1 ? $info['attachment_id'] : 0;?>,
    Fimport : function(){
        var id = $("[name='send_type_1_value']").val();
        if(isNaN(id) || id.length == 0){
            alert("请正确填写id");
            return false;
        }
        $.post("/m.php?m=SmsTask&a=importbaize", {"val":id}, function(ret){
            if(ret.code != 0){
                $(".js_1_msg").html("请核对发送对象是否存在");
            }else{
                $(".js_1_msg").html("数据操作员："+ret.sta.create_user+"，共"+ret.sta.spark_count+"条数据");
                baize.hasId = id;
                if(ret.error){
                    $("#error").show();
                    $("#submitJustdo").show();
                    $(".error_message").html(ret.error);
                }else{
                    $("#error").hide();
                }
            }
        }, "json");
    }
};
function download(){ window.location.href = ROOT+'?m=SmsTask&a=download'; }
function checkTemplate(){ 
    var msg_content = $("#content").val();
    var left_count = (msg_content.split('{')).length-1;
    var right_count = (msg_content.split('}')).length-1;
    var is_have_tsfh = /[\@\#\$\^\&\*\=\\\|]/.test(msg_content);
    if(msg_content.length === 0){
        alert("短信内容不能为空");
        $('#content').focus();
        return false;
    }else if(left_count!==right_count){
        alert("短信内容括号不匹配");
        $('#content').focus();
        return false;
    }else if(is_have_tsfh){
        alert("短信内容不能含有特殊符号");
        $('#content').focus();
        return false;
    }
    var sendTypeValue = $(".js_send_type").val();
    if(sendTypeValue != "0"){
        var sendTypeInput = $("[name='send_type_"+sendTypeValue+"_value']").val();
    }
    switch(sendTypeValue){
        case "0" : 
            if($('#upload_files').val()=='' || $('#attachment_id').val()==''){
                alert("短信发放对象不能为空");
                $('#upload_files').focus();
                return false;
            }
        break;
        case "1" :
            if(baize.hasId == 0 || baize.hasId != sendTypeInput){
                alert("请先导入白泽任务再提交");
                return false;
            }
        break;
        case "2" :
        case "3" :
            if(isNaN(sendTypeInput)){
                alert("请正确填写id或手机号");
                return false;
            }
            $.post("/m.php?m=SmsTask&a=checkUserOrMobile", {"send_type":sendTypeValue, "val":sendTypeInput}, function(ret){
                if(ret.code != 0){
                    $(".js_"+sendTypeValue+"_msg").html("请核对发送对象是否存在");
                }else{
                    $('#form1').attr('action', ROOT+'?m=SmsTask&a=save').submit();
                }
            }, "json");
            return false;
        break;
        default :
            return false;
    }
    return true;
}
function saveData(){
    $("#sub_check").val(0);
    var check_template = checkTemplate();
    if(check_template){
        $('#form1').attr('action', ROOT+'?m=SmsTask&a=save').submit();
    }
}
function submitCheck(){
    $("#sub_check").val(1);
    var check_template = checkTemplate();
    if(check_template){
        $('#form1').attr('action', ROOT+'?m=SmsTask&a=save').submit();
    }
}

p2popen.ui.ImagePicker.decorateInstance($(".JS-app_icon")[0]);
$(".file_upload_btn").click(function(event) {
    $(".file_upload_btn .fileinput").val("");
});
p2popen.ui.ImagePicker.prototype._onuploadSuccess_ = function(jsonstr) {
    jsonstr = jsonstr.replace(/;/g, '<br/>');
    var localurl = URL.createObjectURL(this._fileInput_.files[0]);
    var rpcresult = null;
    try { rpcresult = $.parseJSON(jsonstr); } catch (e) { rpcresult = {};}
    if (rpcresult['errorCode'] == 200) {
        var data = rpcresult.data;
        if (this._suc_callback != undefined) {
            this._suc_callback.call(null,data);
        } else {
            $('.JS_bonus_money_box').show();
            $("#attachment_id").val(data["aid"]);
            $(".JS_bonus_money").html(rpcresult['errorMsg']);
            $(".JS_file_name").html('<a href="'+data["full_path"]+'">'+data["original_filename"]+'</a>');
            if(data['check_error_msg']){
                $("#error").show();
                $("#submitJustdo").show();
                $(".error_message").html(data['check_error_msg']);
            }else{
                $("#error").hide();
            }
        }
    } else if (rpcresult['errorMsg']) {
        $("#error").show();
        $("#submitJustdo").hide();
        $(".error_message").html(rpcresult['errorMsg']);
        $('.JS_bonus_money_box').hide();
    } else {
        p2popen.ui.errorTip("服务器忙，请重试");
    }
    this.updateLoading(false);
};
$(".fileinput").change(function(){ $("#upload_files").val($(this).val()); });
$(".js_send_type").change(function() {
    var sendTypeValue = this.value;
    for(var i=0;i<=3;i++){
        if(i == sendTypeValue){
            $(".js_"+i+"_tr").show();
        }else{
            $(".js_"+i+"_tr").hide();
        }
    }
});
function show_cal(obj) {
    obj.blur();
    return showCalendar(obj, '%Y-%m-%d %H:%M:%S', true, false, obj);
}
function del_time(){
    $("[name='expect_send_time']").val('');
}
</script>

</body>
</html>