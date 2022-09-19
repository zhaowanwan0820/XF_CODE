<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
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
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<div class="main">
<div class="main_title">站内信与推送</div>
<div class="blank5"></div>

<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
    <tr><td colspan="10" class="topTd" >&nbsp; </td></tr>
    <tr class="row" >
        <th>发送站内信</th>
        <td>
            <form action="__APP__" method="post" name="search" enctype="multipart/form-data">
                <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="PushTool" />
                <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="sendMsg" />

                方式:
                <select id="msg_select" name="uidType">
                    <option value="msg_userid">用户ID</option>
                    <option value="msg_csv">上传CSV文件</option>
                    <!--<option value="msg_user_group_id">会员所属网站</option>-->
                    <option value="msg_user_all">全站公告</option>
                </select>
                <div class="blank5"></div>

                <div id="msg_toggle">
                <div id="msg_userid">
                用户:
                <input name="userId" value="" />
                <span class='tip_span'>(输入用户ID，多个用逗号隔开)</span>
                <div class="blank5"></div>
                </div>

                <div id="msg_csv">
                文件:
                <input type="file" name="csv"> <a href="/m.php?m=PushTool&a=downloadTemplate">下载模板</a>
                <div class="blank5"></div>
                </div>

                <div id="msg_user_group_id">
                分组:
                <select id="group_select" name="groupId">
                    <?php foreach($allGroup as $group) { ?>
                    <option value="<?php echo $group['id'] ?>"><?php echo $group['name'] ?></option>
                    <?php } ?>
                </select>
                <div class="blank5"></div>
                </div>

                </div>

                <div id="type_show">
                类型:
                <select name="msg_type">
                    <?php foreach ($allType as $key => $value) { ?>
                        <option value="<?php echo $key?>"><?php echo $value; ?></option>
                    <?php } //end foeach ?>
                </select>
                </div>
                <div class="blank5"></div>

                标题:
                <input name="title" value="" />
                <div class="blank5"></div>

                内容:
                <textarea name="content" style="width:500px;height:50px;"></textarea>
                <div class="blank5"></div>
                链接:
                <input type="text" name="url" class="textbox">
                <div class="blank5"></div>

                定时:
                <input type="text" class="textbox" name="msg_send_time" id="msg_send_time" value="<?php echo trim($_REQUEST['msg_send_time']);?>" onfocus="return showCalendar('msg_send_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_msg_send_time');" style="width:120px;" />
                <input type="button" class="button" id="btn_msg_send_time" value="选择时间" onclick="return showCalendar('msg_send_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_msg_send_time');" />
                <input type="button" class="button" value="清空时间" onclick="$('#msg_send_time').val('');" />

                <div class="blank5"></div>
                <input type="button" class="button submit" value="提交" />
                <a href="/m.php?log_info=系统站内信发送UID&m=Log&a=index">查看发送日志</a>
            </form>
        </td>
    </tr>
    <tr class="row" >
        <th>发送推送</th>
        <td>
            <form action="__APP__" method="post" name="search" enctype="multipart/form-data">
                <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="PushTool" />
                <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="sendPush" />

                方式:
                <select id="push_select" name="uidType">
                    <option value="push_userid">用户ID</option>
                    <option value="push_csv">上传CSV文件</option>
                    <!--<option value="push_user_group_id">会员所属网站</option>-->
                    <option value="push_user_all">全体用户</option>
                </select>
                <div class="blank5"></div>

                <div id="push_toggle">
                <div id="push_userid">
                用户:
                <input name="userId" value="" />
                <span class='tip_span'>(输入用户ID，多个用逗号隔开)</span>
                <div class="blank5"></div>
                </div>

                <div id="push_csv">
                文件:
                <input type="file" name="csv"> <a href="/m.php?m=PushTool&a=downloadTemplate">下载模板</a>
                <div class="blank5"></div>
                </div>

                <div id="push_user_group_id">
                分组:
                <select id="group_select" name="groupId">
                    <?php foreach($allGroup as $group) { ?>
                    <option value="<?php echo $group['id'] ?>"><?php echo $group['name'] ?></option>
                    <?php } ?>
                </select>
                <div class="blank5"></div>
                </div>
                </div>

                角标:
                <input name="badge" value="1" style="width:100px;" />
                <div class="blank5"></div>

                内容:
                <textarea name="content" style="width:500px;height:50px;"></textarea>
                <div class="blank5"></div>

                定时:
                <input type="text" class="textbox" name="push_send_time" id="push_send_time" value="<?php echo trim($_REQUEST['push_send_time']);?>" onfocus="return showCalendar('push_send_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_push_send_time');" style="width:120px;" />
                <input type="button" class="button" id="btn_push_send_time" value="选择时间" onclick="return showCalendar('push_send_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_push_send_time');" />
                <input type="button" class="button" value="清空时间" onclick="$('#push_send_time').val('');" />

                <div class="blank5"></div>
                <input type="button" class="button submit" value="提交" />
                <a href="/m.php?log_info=系统推送发送UID&m=Log&a=index">查看发送日志</a>
            </form>
        </td>
    </tr>
    <tr><td colspan="10" class="bottomTd">&nbsp; </td></tr>
</table>

<div class="blank5"></div>
</div>
<script type="text/javascript" src="/static/admin/Common/js/jquery-1.10.2.min.js"></script>
<script>
/**
 * HTML显示控制类
 * @param {[type]} config [description]
 */
var HtmlControl = function(config)
{
    var self = this;
    this.selectID = config.selectID;
    this.toggleID = config.toggleID;
    this.showDefault = config.showDefault;

    // 显示隐藏开关
    this.htmlToggle = function (showID)
    {
        $("#" + self.toggleID + " div[id]").each(function(_, div) {
            $(div).hide()
        });
        $("#" + showID).show();
        if (showID == 'msg_user_all') {
            $("#type_show").hide();
        } else {
            $("#type_show").show();
        }
    }
    // 初始化
    this.init = function()
    {
        $("#" + self.selectID).change(function() {
            self.htmlToggle($(this).val());
        }).val(self.showDefault).trigger("change")
    }
}

var msgConfig = {
    selectID: "msg_select",
    toggleID: "msg_toggle",
    showDefault:  "msg_userid",
} // 站内信配置
var pushConfig = {
    selectID: "push_select",
    toggleID: "push_toggle",
    showDefault:  "push_userid",
} // 推送配置

var msgControl = new HtmlControl(msgConfig);
var pushControl = new HtmlControl(pushConfig);
msgControl.init();
pushControl.init();

// 提交时检查CSV文件是否OK
$(".submit").click(function()
{
    var currentForm = $(this).parent('form'),
        type = currentForm.find('select').val();
    if (type.indexOf('csv') !== -1) { // csv文件上传，进行校验
        var checkAPI = "/m.php?m=PushTool&a=checkCSV";
        $.ajax({
            type: "POST",
            url: checkAPI,
            data: new FormData(currentForm.get(0)),
            dataType: 'json',
            enctype: 'multipart/form-data',
            processData: false,
            contentType: false,
            success: (function(data) {
                if (data.errorCode) {
                    alert(data.msg);
                } else {
                    currentForm.submit();
                }
            })
        });
    } else {
        currentForm.submit();
    }
})

$("[name='url']").change(function(){
    var url = $(this).val();
    var hasHref = ($(this).next().attr('id') == 'testUrl');
    if (url != '' && !hasHref) {
        $(this).after("<a id='testUrl' href='"+url+"' target='_blank'>"+url+"</a>");
    } else if (url != '') {
        $("#testUrl").attr('href', url)
    } else if (hasHref){
        $(this).next().remove();
    }
});

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