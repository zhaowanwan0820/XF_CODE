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
<script type="text/javascript" src="__TMPL__Common/js/cropper.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery-cropper.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/bootstrap.min.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/cropper.css" />

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
.flipped {
    transform: scale(-1, 1);
    -moz-transform: scale(-1, 1);
    -webkit-transform: scale(-1, 1);
    -o-transform: scale(-1, 1);
    -khtml-transform: scale(-1, 1);
    -ms-transform: scale(-1, 1);
}
.img-container,
.img-preview {
    background-color: #f7f7f7;
    text-align: center;
    width: 100%;
}

/*.img-container <?php echo constant("/
    /*margin-bottom: 1rem;*/
    /*max-height: 400px;*/
    /*min-height: 200px;*/
/*");?>*/

/*@media (min-width: 768px) <?php echo constant("/
    /*.img-container {*/
        /*min-height: 400px;*/
    /*");?>*/
/*}*/

.img-container > img {
    max-width: 100%;
}

.docs-preview {
    margin-right: -1rem;
}

.img-preview {
    float: left;
    margin-bottom: .5rem;
    margin-right: .5rem;
    margin-left: .5rem;
    overflow: hidden;
}

.img-preview > img {
    max-width: 100%;
}
.preview-lg {
    height: 300px;
    width: 300px;
}
.cropper-crop-box{
    margin-left:0px;
}
</style>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        会员名：<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" />
        姓名：<input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" />
        手机号：<input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>" />
        状态：
            <select name="status">
                <option value="0" <?php if(intval($_REQUEST['status']) == 0): ?>selected="selected"<?php endif; ?>>全部</option>
                <option value="1" <?php if(intval($_REQUEST['status']) == 1): ?>selected="selected"<?php endif; ?>>未处理</option>
                <option value="2" <?php if(intval($_REQUEST['status']) == 2): ?>selected="selected"<?php endif; ?>>拒绝</option>
                <option value="3" <?php if($_REQUEST['status'] == 3): ?>selected="selected"<?php endif; ?>>批准</option>
            </select>
<br />
         申请时间：<input type="text" class="textbox" id="apply_start" name="apply_start" value="<?php echo trim($_REQUEST['apply_start']);?>" onfocus="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('apply_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
至
                  <input type="text" class="textbox" name="apply_end" id="apply_end" value="<?php echo trim($_REQUEST['apply_end']);?>"  onfocus="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:150px;" />
            <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('apply_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
         处理时间：<input type="text" class="textbox" id="deal_start" name="deal_start" value="<?php echo trim($_REQUEST['deal_start']);?>" onfocus="return showCalendar('deal_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_deal_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_deal_time_start" value="选择" onclick="return showCalendar('deal_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_deal_time_start');" />
至
                  <input type="text" class="textbox" name="deal_end" id="deal_end" value="<?php echo trim($_REQUEST['deal_end']);?>"  onfocus="return showCalendar('deal_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_deal_time_end');" style="width:150px;" />
            <input type="button" class="button" id="btn_deal_time_end" value="选择" onclick="return showCalendar('deal_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_deal_time_end');" />
        处理人: <INPUT type="TEXT" name="admin_name" class="textbox" value="<?php echo trim($_REQUEST['admin_name']);?>" />
        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="AuditBankInfo" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" id="export" class="button" onclick="export_csv();" value="导出" />
    </form>
</div>
<?php function bank_audit_status($id){
    if($id == 1){
        echo '未审核';
    }
    if($id == 2){
        echo '拒绝';
    }
    if($id == 3){
        echo '批准';
    }
}
function get_bank_edit($type,$item){
    return "<a href=\"javascript:open_div(".$item['id'].",'".$item['user_name']."','".$item['real_name']."')\">审核</a>";
}

function get_user_by_name($name){
    return '<a href="/m.php?user_name='.$name.'&m=User&a=index" target="_blank">'.$name."</a>";
}
function get_user_by_real_name($name){
    return '<a href="/m.php?real_name='.$name.'&m=User&a=index" target="_blank">'.$name."</a>";
}
function get_user_by_mobile($name){
    return '<a href="/m.php?mobile='.$name.'&m=User&a=index" target="_blank">'.$name."</a>";
}
function get_list_by_user_id($name, $item){
    return '<a href="/m.php?mobile='.$item['mobile'].'&status=0&m=User&a=AuditBankInfo">'.$name."</a>";
}
function show_fastpay_cert_status($status) {
    if($status == 1){
        echo '通过验证';
    }else {
        echo '<span style="color:#f00;">验证失败</span>';
    }
}
function format_assets($assets) {
    return $assets . '元';
} ?>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="14" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_name','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照用户名<?php echo ($sortType); ?> ">用户名<?php if(($order)  ==  "user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('real_name','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照姓名<?php echo ($sortType); ?> ">姓名<?php if(($order)  ==  "real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('mobile','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照手机号<?php echo ($sortType); ?> ">手机号<?php if(($order)  ==  "mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照申请时间<?php echo ($sortType); ?> ">申请时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('total_assets','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照申请时总资产<?php echo ($sortType); ?> ">申请时总资产<?php if(($order)  ==  "total_assets"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('count','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照累计申请次数<?php echo ($sortType); ?> ">累计申请次数<?php if(($order)  ==  "count"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('fail_reason','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照审核失败原因<?php echo ($sortType); ?> ">审核失败原因<?php if(($order)  ==  "fail_reason"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('fastpay_cert_status','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照是否通过4要素验证<?php echo ($sortType); ?> ">是否通过4要素验证<?php if(($order)  ==  "fastpay_cert_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('audit_time','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照处理时间<?php echo ($sortType); ?> ">处理时间<?php if(($order)  ==  "audit_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('admin','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照处理人<?php echo ($sortType); ?> ">处理人<?php if(($order)  ==  "admin"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','User','AuditBankInfo')" title="按照状态<?php echo ($sortType); ?> ">状态<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td><td>&nbsp;<?php echo ($item["id"]); ?></td><td>&nbsp;<?php echo (get_user_by_name($item["user_name"])); ?></td><td>&nbsp;<?php echo (get_user_by_real_name($item["real_name"])); ?></td><td>&nbsp;<?php echo (get_user_by_mobile($item["mobile"])); ?></td><td>&nbsp;<?php echo (to_date($item["create_time"])); ?></td><td>&nbsp;<?php echo (format_assets($item["total_assets"])); ?></td><td>&nbsp;<?php echo (get_list_by_user_id($item["count"],$item)); ?></td><td>&nbsp;<?php echo ($item["fail_reason"]); ?></td><td>&nbsp;<?php echo (show_fastpay_cert_status($item["fastpay_cert_status"])); ?></td><td>&nbsp;<?php echo (to_date($item["audit_time"])); ?></td><td>&nbsp;<?php echo ($item["admin"]); ?></td><td>&nbsp;<?php echo (bank_audit_status($item["status"])); ?></td><td><a href="javascript:getBankInfo('<?php echo ($item["id"]); ?>')">查看</a>&nbsp; <?php echo (get_bank_edit($item["add_type"],$item)); ?>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="14" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<!-- 审核 -->
<div id='dialogbox_div' class="dialog-box dialogbox" style="display: none; position: absolute; overflow: visible; z-index: 999; width: 600px; top: 249px; left: 340px;">
    <div class="dialog-header">
        <div class="dialog-title">资料审核</div>
        <div class="dialog-close" onclick='close_div()'></div>
    </div>
    <div class="dialog-content" style="height: 280px;overflow: visible;">
        <div class="main">
            <div class="main_title" id="user_name"></div>
            <div class="blank5"></div>
            <form name="edit" action="<?php echo u("User/BankAuditing");?>" method="post">
                <input type='hidden' value='' name='aid' id='aid'>
                <table class="form" cellpadding="0" cellspacing="0">
                    <tbody><tr>
                        <td colspan="2" class="topTd"></td>
                    </tr>
                    <tr>
                        <td class="item_title">真实姓名:</td>
                        <td class="item_input"><span id="real_name"></span></td>
                    </tr>
                    <tr>
                        <td class="item_title">银行卡修改审核:</td>
                        <td class="item_input" id="passedBox">
                            <!-- <select name="status" id='status_value'>
                                <option selected="selected">请选择</option>
                                <option value="3">审核通过</option>
                                <option value="2">审核失败</option>
                            </select> -->
                            <!-- 修改对应的js -->
                            <div id="status_div">
                                <input type="text" class="status_value" id="status_value" placeholder="请选择" readOnly="true">
                                <span id="failReasonSpan"></span>
                                <input type="hidden" name="status" value="">
                                <input type="hidden" name="failReasonType" value="" >
                                <ul class="case_list" style="display:none;">
                                    <li data-status="3" class="case_li">审核通过</li>
                                    <li data-status="2" class="case_li_noClick">
                                        审核失败
                                        <i class="directionR">></i>
                                        <ul>
                                            <?php if(is_array($failReasonTypeList)): foreach($failReasonTypeList as $key=>$reason_item): ?><li class="case_li" data-failReasonType="<?php echo ($reason_item["reasonId"]); ?>" failReasonDesc="<?php echo ($reason_item["reasonDesc"]); ?>"><?php echo ($reason_item["reason"]); ?></li><?php endforeach; endif; ?>
                                        </ul>
                                    </li>
                                </ul>
                            </div>

                        </td>
                    </tr>
                    <tr>
                        <td class="item_title">原因:</td>
                        <td class="item_input">
                            <textarea type="text" id="msgarea"  class="textbox" name="description" style="width:400px;height:100px"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class="item_title">&nbsp;</td>
                        <td class="item_input">
                            <!--隐藏元素-->
                            <input type="hidden" name="m" value="User">
                            <input type="hidden" name="a" value="BankAuditing">
                            <!--隐藏元素-->
                            <input type="submit" class="button" value="确认" onclick='return checkStatus(this)'>
                            <input type="reset" class="button" value="重置">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="bottomTd"></td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    <div style="clear:both"></div>
    <div class="dialog-button" style="display: none;">
        <input type="button" class="dialog-ok" value="确定">
        <input type="button" class="dialog-cancel" value="取消">
    </div>
</div>
<!--  -->

<!-- 查看 -->
<div id='dialogbox_msg' class="dialog-box dialogbox" style="display: none; position: absolute; overflow: hidden; z-index: 999; width: 800px; top: 200px; right: 200px;">
    <div class="dialog-header">
        <div class="dialog-title">修改银行卡认证</div>
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
$(function(){
    $("#status_div").click(function(){
        $(".case_list").toggle();
    });
    $("#status_div").hover(function(){
        $(".case_list").show();
    },function(){
        $(".case_list").hide();
    });
    $("body").on("click",".case_li",function(){
        var $t = $(this);
        //判断一级的状态值
        var _value = $t.attr("data-status"),
            //选中二级的时候 获取父亲的状态码
            _pvalue = $t.parent().parent().attr("data-status");
        //获取一级的审核提示
        var _valueT = $t.html();
        //获取审核失败的状态码
        var _failReasonType = $t.attr("data-failReasonType");

        //获取审核失败的状态描述
        var _failReasonTextareaT = $(this).attr("failReasonDesc");
        //判断选中的是一级还是二级 : 获取一级状态码
        _value = _value ? _value : _pvalue;
        //span  失败提示
        _failReasonSpanT = _failReasonType ? _valueT :'';
        _failReasonTextareaT = _failReasonType ? _failReasonTextareaT :'';
        _failReasonType = _failReasonType ? _failReasonType :"";
        //input输入框
        _valueT = _failReasonType ? '审核失败' : _valueT;
        $("#status_value").val( _valueT);
        $("#failReasonSpan").html(_failReasonSpanT);
        $("input[name='status']").val(_value);
        $("input[name='failReasonType']").val(_failReasonType);
        if(_failReasonTextareaT){
            $("#msgarea").val(_failReasonTextareaT).attr("readOnly","true");
        }else{
            $("#msgarea").val('').removeAttr("readOnly");
        }

    });

    //图片翻转
    $('#dialogbox_msg').on('click', '#idcard_img_btn', function() {
        $('#dialogbox_msg #idcard_img').toggleClass('flipped');
    });
});
function close_div() {
    $('.dialogbox').hide();
}
function open_div(id,user_name,real_name) {
    $('#aid').val(id);
    $('#user_name').text(user_name);
    $('#real_name').text(real_name);
    $('#dialogbox_div').show();
    $('#dialogbox_msg').hide();
    // 清空上一个弹框赋的值
    $("#status_value").val("");
    $("input[name='status']").val("");
    $("input[name='failReasonType']").val("");
}
//获取银行信息
function getBankInfo(id) {
    if(id) {
        window.open('/m.php?m=User&a=getBankInfo&id='+id);
        return;
        $.ajax({
               type: "POST",
               url: "/m.php?m=User&a=getBankInfo",
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
function get_query_string(){
    querystring = '';
    querystring += "&apply_start="+$("input[name='apply_start']").val();
    querystring += "&apply_end="+$("input[name='apply_end']").val();
    querystring += "&deal_start="+$("input[name='deal_start']").val();
    querystring += "&deal_end="+$("input[name='deal_end']").val();
    querystring += "&user_name="+$("input[name='user_name']").val();
    querystring += "&mobile="+$("input[name='mobile']").val();
    querystring += "&status="+$("select[name='status']").val();
    querystring += "&admin_name="+$("input[name='admin_name']").val();
    return querystring;

}

function export_csv() {
    window.location.href = ROOT+'?export=1&m=User&a=AuditBankInfo'+get_query_string();
}

function checkStatus(btn) {
    var status = $('#status_value').val();
    var $btn = $(btn);
    $btn.css({"color":"gray","background":"#ccc"}).attr("disabled","disabled");
    if(status && status != '请选择'){
        if (confirm("确定此操作吗？")) {
            $btn.css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");

            return true;
        } else {
            $btn.css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");

            return false;
        }

    }else{
        alert('请选择审核操作项');
        $btn.css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
        return false;
    }
}

    //图片
    $('#dialogbox_msg').on('click', '#crop_idcard', function() {
        $('.right').show();
        var $image = $('#idcard_img');
        var $dataX = $('#dataX');
        var $dataY = $('#dataY');
        var $dataWidth = $('#dataWidth');
        var $dataHeight = $('#dataHeight');
        var options = {
            preview: '.img-preview',
            checkCrossOrigin: false,
            checkOrientation: false,
            aspectRatio: 1 / 1,
            autoCropArea: 0.6,
            zoomable: false,
            crop: function (e) {
                $dataX.val(Math.round(e.detail.x));
                $dataY.val(Math.round(e.detail.y));
                $dataWidth.val(Math.round(e.detail.width));
                $dataHeight.val(Math.round(e.detail.height));
            }
        }

        $image.cropper(options);

    });
    $('#dialogbox_msg').on('click', '#cut_idcard', function() {
        var $dataX = $('#dataX').val();
        var $dataY = $('#dataY').val();
        var $dataWidth = $('#dataWidth').val();
        var $dataHeight = $('#dataHeight').val();
        var $imageUrl = $('#imageUrl').val();
        var $name = $('#name').val();
        var $idno = $('#idno').val();
        $.ajax({
               type: "POST",
               url: "/m.php?m=IDVerify&a=cropImageAndCompare",
               data: "x="+$dataX+"&y="+$dataY+"&width="+$dataWidth+"&height="+$dataHeight+"&name="+$name+"&idno="+$idno+"&imageUrl="+$imageUrl,
               dataType:'json',
               success: function(msg){
                    $('#compareRet').val(msg.msg)
               }
        });
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