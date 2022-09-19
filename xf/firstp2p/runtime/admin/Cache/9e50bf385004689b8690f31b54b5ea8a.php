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

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/deal.js"></script>


<script type="text/javascript" src="__TMPL__ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/ueditor.all.min.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/lang/zh-cn/zh-cn.js"></script>

<script type="text/javascript">

var auto_change_loanrate = false;
function checkUserId(user_id) {
 if(isNaN(user_id)){
        alert("必须为数字");
        return false;
    }
    if(user_id.length>0)
    {
        $.ajax({
            url:ROOT+"?"+VAR_MODULE+"=User&"+VAR_ACTION+"=getAjaxUser&id="+user_id,
            dataType:"json",
            success:function(result){
                if(result.status ==1)
                {
                 if(result.user.user_name) {
                        $("#user_name").html("  会员名称:<a href='<?php echo U('User/edit');?>id="+user_id+"' target='__blank'>"+result.user.user_name+"</a>  会员姓名:"+result.user.name+" 用户类型:"+result.user.user_type_name);
                    }
                }
                else{
                    alert("会员不存在");
                    $("#user_id").val('');
                    $("#user_name").text('');
                    $("#user_id").focus();
                }
            }
        });
    }
}
$(document).ready(function(){
    changeLoanMoneyMode();
 changeRepay();
 checkUserId($("#user_id").val());
 //检验user_id是否存在 如果存在则显示用户名
    $("input[name='user_id']").bind("blur",function(){
        checkUserId($(this).val());
    });
    // 检测项目名称是否重名
    $("input[name='name']").bind("blur",function(){
        if($(this).val().length>0)
        {
            $.ajax({
                url:ROOT+"?"+VAR_MODULE+"=DealProject&"+VAR_ACTION+"=getCntByName&ajax=1&name="+$(this).val()+"&id="+$("#id").val(),
                dataType:"json",
                success:function(result){
                    if(result.status ==1)
                    {
                        if(result.cnt > 0) {
                            $("#name_tip").text('项目名称已经存在');
                            $("#name").focus();
                        }
                    }
                    else{
                         $("#name_tip").text('');
                    }
                }
            });
        }
    });

    // 检测借款总额是否为数字
    $("input[name='borrow_amount']").bind("blur",function(){
        if($(this).val().length>0)
        {
            if(isNaN($(this).val())){
                $("#borrow_tip").text('借款总额必须为数字');
                $("#borrow_amount").val('');
                $("#borrow_amount").focus();
                return false;
            }
        }
    });

 // 检测 还款天数是否为数字
    $("#repay_period2").bind("blur",function(){
        if($(this).val().length>0)
        {
            if(isNaN($(this).val())){
                $("#repay_period2_tip").text('还款天数必须为数字');
                $("#repay_period2").val('');
                $("#repay_period2").focus();
                return false;
            }
        }
    });

  //实例化编辑器
    UE.getEditor('editor');
    UE.getEditor('editor1');
    UE.getEditor('editor2');
});

function checkSave() {
 var is_submit = 0;
    $.ajax({
        url:ROOT+"?"+VAR_MODULE+"=DealProject&"+VAR_ACTION+"=checkSave&id="+$("#id").val()+"&borrow_amount="+$("#borrow_amount").val(),
        dataType:"json",
        async:false,
        success:function(rs){
            if(rs.status ==1)
            {
                if(rs.data.edit_user == 0  && $("#user_id").val() != $("#old_user_id").val()) {
                    alert('该项目已经有子标不能编辑借款人id');    return;
                }
                if(rs.data.sum > $("#borrow_amount").val()) {
                    alert('借款总额不能低于子标借款总和！');  return;
                }
                if(rs.data.amount_auth == 0) {
                    alert(rs.message);
                    $("#borrow_amount").focus();
                    return;
                }
                is_submit = 1;
            }else{
             is_submit = 0;
            }
        }
    });
    if(is_submit == 1) {
        return true;
    }else {
        return false;
    }
}



function changeRepay(tag){
    var repay_mode = $('#repay_mode').val();

    changeLoantype();
    //切换html
    if(repay_mode == 5){
        $('.xhsoi').hide();
        $('.xhsot').show();

        var repay_period = $('#repay_period2').val();
        $('#repay_period').hide();
        $('#repay_period').removeAttr('name');
        $('#repay_period2,#tian').show();
        $('#repay_period2').attr('name', 'repay_time');
        $('#repay_period3').hide();
        $('#repay_period3').removeAttr('name');
    }else if(repay_mode == 4 || repay_mode == 3 || repay_mode == 2 || repay_mode == 8){
        $('.xhsoi').show();
        $('.xhsot').hide();

        var repay_period = $("#repay_period3").val();
        $('#repay_period3').show();
        $('#repay_period3').attr('name', 'repay_time');
        $('#repay_period2,#tian').hide();
        $('#repay_period2').removeAttr('name');
        $('#repay_period').hide();
        $('#repay_period').removeAttr('name');
    }else{
        $('.xhsoi').show();
        $('.xhsot').hide();

        var repay_period = $("#repay_period").val();
        $('#repay_period').show();
        $('#repay_period').attr('name', 'repay_time');
        $('#repay_period2,#tian').hide();
        $('#repay_period2').removeAttr('name');
        $('#repay_period3').hide();
        $('#repay_period3').removeAttr('name');
    }
}


function changeLoantype() {
    var loantype = $("#repay_mode").val();

    if((loantype == 4 || loantype == 6)) {
        $("#first_repay_day_box").show();
    } else {
        $("#first_repay_day_box").hide();
    }
}

function changeLoanMoneyMode() {
    var loan_money_type = $("#loan_money_mode").val();

    if (loan_money_type == 3) { //受托支付
        $('#cardname').show();
        $('#bankcardnumber').show();
        $('.bankzone_selector').show();
        $('.card_type').show();
    } else {
        $('#cardname').hide();
        $('#bankcardnumber').hide();
        $('.bankzone_selector').hide();
        $('.card_type').hide();
    }
}

function changeRate(tag){
    if(!tag)   return false;
    var repay_time = $("select[name='repay_time']").val();
    var loantype = $("select[name='loantype']").val();


    if(loantype == 5){
        $('#repay_period').hide();
        $('#repay_period').removeAttr('name');
        $('#repay_period2').show();
        $('#repay_period2').attr('name', 'repay_time');
        $('#repay_period3').hide();
        $('#repay_period3').removeAttr('name');

        repay_time = $('#repay_period2').val();
    }else if(loantype == 4 || loantype == 3 || loantype == 2){
        $('#repay_period3').show();
        $('#repay_period3').attr('name', 'repay_time');
        $('#repay_period2').hide();
        $('#repay_period2').removeAttr('name');
        $('#repay_period').hide();
        $('#repay_period').removeAttr('name');

        repay_time = $('#repay_period3').val();
    }else{
        $('#repay_period').show();
        $('#repay_period').attr('name', 'repay_time');
        $('#repay_period2').hide();
        $('#repay_period2').removeAttr('name');
        $('#repay_period3').hide();
        $('#repay_period3').removeAttr('name');

        repay_time = $('#repay_period').val();
    }

}
</script>
<div class="main">
<div class="main_title">查看项目 <a href="<?php echo u("DealProject/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form id="addform" name="view" action="__APP__" method="post" enctype="multipart/form-data">
<table class="form" cellpadding=0 cellspacing=0>
 <tr>
  <td colspan=2 class="topTd"></td>
 </tr>
    <tr>
        <td class="item_title">产品结构:</td>
        <td class="item_input">   <!-- <span class="tip_span"></span> -->
            　<strong>1级</strong><input type="text" class="textbox"  name="product_mix_1" value="<?php echo ($vo['product_mix_1']); ?>" disabled="disabled"/>　　　
            <strong>2级</strong><input type="text" class="textbox"  name="product_mix_2" value="<?php echo ($vo['product_mix_2']); ?>" disabled="disabled" />　　　
            <strong>3级</strong><input type="text" class="textbox"  name="product_mix_3" value="<?php echo ($vo['product_mix_3']); ?>" disabled="disabled" />

        </td>
    </tr>
 <tr>
  <td class="item_title">项目名称:</td>
  <td class="item_input">   <!-- <span class="tip_span"></span> -->
  <input type="text" class="textbox require" name="name" id="name" value="<?php echo ($vo['name']); ?>" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>/>
  <span id="name_tip"></span>
  </td>
 </tr>
    <tr>
        <td class="item_title">产品大类:</td>
        <td class="item_input">
            <input type="text" <?php if($vo['deal_type'] == 2): ?>class="textbox"<?php else: ?>class="textbox require"<?php endif; ?> name="product_class" id="product_class" value="<?php echo ($vo['product_class']); ?>" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>/>
        </td>
    </tr>
    <tr>
        <td class="item_title">产品名称:</td>
        <td class="item_input">
            <input type="text" <?php if($vo['deal_type'] == 2): ?>class="textbox"<?php else: ?>class="textbox require"<?php endif; ?> name="product_name" id="product_name" value="<?php echo ($vo['product_name']); ?>" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>/>
        </td>
    </tr>
    <?php if($vo['deal_type'] == 2): ?><tr>
        <td class="item_title">结算方式:</td>
        <td class="item_input">

            <select name="clearing_type" id="clearing_type">
                <option value="0" <?php if(0 == $vo['clearing_type']): ?>selected="selected"<?php endif; ?>>----</option>
                <option value="1" <?php if(1 == $vo['clearing_type']): ?>selected="selected"<?php endif; ?>>场内</option>
                <option value="2" <?php if(2 == $vo['clearing_type']): ?>selected="selected"<?php endif; ?>>场外</option>
            </select>

        </td>
    </tr><?php endif; ?>
 <tr>
        <td class="item_title">投资风险提示:</td>
        <td class="item_input">
            <input type="text" class="textbox"  name="none_name" value="<?php echo ($vo['risk_describe']); ?>" disabled="disabled" />
        </td>
    </tr>

 <tr>
        <td class="item_title">投资风险承受能力要求:</td>
        <td class="item_input">
            <input type="text" class="textbox"  name="none_name2" value="<?php echo ($vo['risk_name']); ?>" disabled="disabled" />
        </td>
    </tr>
 <tr>
  <td class="item_title">借款人会员ID:</td>
  <td class="item_input">
  <input type="text" class="textbox require" name="user_id" id="user_id" value="<?php echo ($vo['user_id']); ?>" />
  <input type="hidden" class="textbox" id="old_user_id" value="<?php echo ($vo['user_id']); ?>"/>
  <a href='<?php echo u("User/index");?>' target="__blank">会员列表</a>
  <span id="user_name"></span>
  </td>
 </tr>
 <tr>
  <td class="item_title">借款总额:</td>
  <td class="item_input">
    <input type="text" class="textbox require" name="borrow_amount" id="borrow_amount" value="<?php echo ($vo['borrow_amount']); ?>" <?php if($vo['business_status'] > $project_business_status['full_audit']): ?>readonly<?php endif; ?>/>
    <span id="borrow_tip"></span>
    </td>
 </tr>
 <tr>
        <td class="item_title">还款方式:</td>
        <td class="item_input">
            <select name="loantype" id="repay_mode" onchange="javascript:changeRepay();" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>disabled<?php endif; ?>>
                <?php if(is_array($loan_type)): foreach($loan_type as $type_key=>$type_item): ?><option value="<?php echo ($type_key); ?>" <?php if($type_key == $vo['loantype']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>

    <tr>
        <td class="item_title"><?php echo L("REPAY_TIME");?>:</td>
        <td class="item_input">
            <select id="repay_period" name="repay_time" onchange="javascript:changeRepay();" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>disabled<?php endif; ?>>
                <?php if(is_array($repay_time)): foreach($repay_time as $time_key=>$time_item): ?><option value="<?php echo ($time_key); ?>" <?php if($time_key == $vo['repay_time']): ?>selected="selected"<?php endif; ?>><?php echo ($time_item); ?></option><?php endforeach; endif; ?>
            </select>
            <input type="text" class="changepmt textbox" SIZE="8" onchange="javascript:changeRepay();" name="repay_time" id="repay_period2" <?php if($vo["loantype"] == 5): ?>value="<?php echo ($vo["repay_time"]); ?>"<?php endif; ?> <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>/> <span id='tian'>天</span>
            <select id="repay_period3" name="repay_time" onchange="javascript:changeRepay();" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>disabled<?php endif; ?>>
                <?php if(is_array($repay_time_month)): foreach($repay_time_month as $time_key=>$time_item): ?><option value="<?php echo ($time_key); ?>" <?php if($time_key == $vo['repay_time']): ?>selected="selected"<?php endif; ?>><?php echo ($time_item); ?></option><?php endforeach; endif; ?>
            </select>
            <span id="repay_period2_tip"></span>
        </td>
    </tr>
    <tr>
        <td class="item_title">借款综合成本(年化):</td>
        <td class="item_input">
            <input type="text" class="changepmt textbox require" SIZE="8"  name="rate" id="rate" value="<?php echo ($vo["rate"]); ?>" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>>%
        </td>
    </tr>

    <tr>
        <td class="item_title">费用收取方式:</td>
        <td class="item_input">
            <select name="borrow_fee_type" id="borrow_fee_mode" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>disabled<?php endif; ?>>
                <?php if(is_array($borrow_fee_type)): foreach($borrow_fee_type as $type_key=>$type_item): ?><option value="<?php echo ($type_key); ?>" <?php if($type_key == $vo['borrow_fee_type']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>

    <tr>
        <td class="item_title">放款方式:</td>
        <td class="item_input">
            <select name="loan_money_type" id="loan_money_mode" onchange="javascript:changeLoanMoneyMode();" >
                <?php if(is_array($loan_money_type)): foreach($loan_money_type as $type_key=>$type_item): ?><option value="<?php echo ($type_key); ?>" <?php if($type_key == $vo['loan_money_type']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>

    <tr id="cardname" style="display:none;">
        <td class="item_title">收款账户账户名:</td>
        <td class="item_input">
            <input type="text" name="card_name" class="textbox" id="card_name" value="<?php echo ($vo['card_name']); ?>" size="80" />
        </td>
    </tr>

    <!--开户行选择器.start-->
<script type="text/javascript" src="//static.firstp2p.com/attachment/region.js?v=<?php echo app_conf('APP_SUB_VER'); ?>"></script>
<tr class="bankzone_selector">
    <td class="item_title">银行:</td>
    <td class="item_input">
        <select name="bank_id" class="_js_bankinfo">
            <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
            <?php if(is_array($bank_list)): foreach($bank_list as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>" <?php if($item["id"] == $vo['bank_id']): ?>selected="selected"<?php endif; ?>><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
        </select>
    </td>
</tr>
<tr class="bankzone_selector">
    <td class="item_title">开户行所在地:</td>
    <td class="item_input">
        <input type="hidden" value="<?php echo ($bankcard_info["region_lv1"]); ?>" id="deflv1">
            <input type="hidden" value="<?php echo ($bankcard_info["region_lv2"]); ?>" id="deflv2">
            <input type="hidden" value="<?php echo ($bankcard_info["region_lv3"]); ?>" id="deflv3">
            <input type="hidden" value="<?php echo ($bankcard_info["region_lv4"]); ?>" id="deflv4">
            <select name="c_region_lv1" class="_js_bankinfo">
                <option value="0">=<?php echo L("REGION_LV1");?>=</option>
                <?php if(is_array($n_region_lv1)): foreach($n_region_lv1 as $key=>$lv1): ?><option <?php if($bankcard_info['region_lv1'] == $lv1['id']): ?>selected="selected"<?php endif; ?> value="<?php echo ($lv1["id"]); ?>"><?php echo ($lv1["name"]); ?></option><?php endforeach; endif; ?>
            </select>

            <select name="c_region_lv2" class="_js_bankinfo">
                <option value="0">=<?php echo L("REGION_LV2");?>=</option>
            </select>

            <select name="c_region_lv3" class="_js_bankinfo">
                <option value="0">=<?php echo L("REGION_LV3");?>=</option>
            </select>
            <select name="c_region_lv4" id="Jcarry_region_lv4" class="_js_bankinfo">
                <option value="0">=<?php echo L("REGION_LV4");?>=</option>
            </select>
    </td>
</tr>
<script type="text/javascript">
    //银行网点
    $("select[name='bank_id']").bind("change",function(){
        bank_site();
    });

    $(document).ready(function(){
        //setTimeout("bank_site();",1000);
        $("select[name='c_region_lv1']").bind("change",function(){
            load_select("1");
        });
        $("select[name='c_region_lv2']").bind("change",function(){
            load_select("2");
        });
        $("select[name='c_region_lv3']").bind("change",function(){
            load_select("3");
            bank_site();
        });
        $("select[name='c_region_lv4']").bind("change",function(){
            load_select("4");
        });

        // init region
        var devlv1Option = $("select[name='c_region_lv1'] option[value='" + $("#deflv1").val() + "']")[0];
        if (devlv1Option) {
            devlv1Option.selected = true;
            load_select("1");
            var devlv2Option = $("select[name='c_region_lv2'] option[value='" + $("#deflv2").val() + "']")[0];
            if (devlv2Option) {
                devlv2Option.selected = true;
                load_select("2");
                var devlv3Option = $("select[name='c_region_lv3'] option[value='" + $("#deflv3").val() + "']")[0];
                if (devlv3Option) {
                    devlv3Option.selected = true;
                    load_select("3");
                    var devlv4Option = $("select[name='c_region_lv4'] option[value='" + $("#deflv4").val() + "']")[0];
                    if (devlv4Option) {
                        devlv4Option.selected = true;
                    }
                }
            }
        }
    });
    function load_select(lv)
    {
        var name = "c_region_lv"+lv;
        var next_name = "c_region_lv"+(parseInt(lv)+1);
        var id = $("select[name='"+name+"']").val();

        if(lv==1)
        var evalStr="regionConf.r"+id+".c";
        if(lv==2)
        var evalStr="regionConf.r"+$("select[name='c_region_lv1']").val()+".c.r"+id+".c";
        if(lv==3)
        var evalStr="regionConf.r"+$("select[name='c_region_lv1']").val()+".c.r"+$("select[name='c_region_lv2']").val()+".c.r"+id+".c";

        if(id==0)
        {
            var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
        }
        else
        {
            var regionConfs=eval(evalStr);
            evalStr+=".";
            var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
            for(var key in regionConfs)
            {
                html+="<option value='"+eval(evalStr+key+".i")+"'>"+eval(evalStr+key+".n")+"</option>";
            }
        }
        $("select[name='"+next_name+"']").html(html);
    }

    //银行网点
    function bank_site(){
        var c = $("select[name='c_region_lv3']").find("option:selected").text();
        var p = $("select[name='c_region_lv2']").find("option:selected").text();
        var b = $("select[name='bank_id']").find("option:selected").text();
        var n = '<?php echo ($bankcard_info["bankzone"]); ?>';
        var data = {c:c,p:p,n:n};

        $.getJSON("http://www.firstp2p.com/api/banklist?c="+c+"&p="+p+"&n="+n+"&b="+b+"&jsonpCallback=?", function(rs){
            if (rs.indexOf('select') > 0) {
                $("#_js_bank_site").html(rs);
                $('#_js_shoudong').hide();
            }
        });
    }
</script>
<tr class="bankzone_selector">
    <td class="item_title">开户网点:</td>
    <td id="_js_bank_site" >
        <select class="" name="bank_bankzone">
            <option value="<?php echo ($vo["bankzone"]); ?>"><?php echo ($vo["bankzone"]); ?></option>
        </select>
    </td>
</tr>
<!--开户行选择器.end-->


    <tr id="bankcardnumber" style="display:none;">
        <td class="item_title">收款账户银行卡号:</td>
        <td class="item_input">
            <input type="text" name="bankcard" class="textbox" id="bankcard" value="<?php echo ($vo['bankcard']); ?>" />
        </td>
    </tr>

    <tr class="card_type">
        <td class="item_title">银行卡类型:</td>
        <td class="item_input">
            <select name="card_type">
                <?php if(is_array($card_types)): foreach($card_types as $key=>$item): ?><option <?php if($item['id'] == $vo['card_type']): ?>selected="selected"<?php endif; ?> value="<?php echo ($item["id"]); ?>"><?php echo ($item["card_type_name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>

    <tr>
        <td class="item_title">放款审批单编号</td>
        <td class="item_input">
            <input type="text" class="textbox" name="approve_number" id="approve_number" value="<?php echo ($vo["approve_number"]); ?>" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>>
        </td>
    </tr>
    <tr>
        <td class="item_title">项目授信额度</td>
        <td class="item_input">
            <input type="text" class="textbox" name="credit" id="credit" value="<?php echo ($vo["credit"]); ?>" <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>>
        </td>
    </tr>
    <tr>
        <td class="item_title">借款人合同委托签署</td>
        <td class="item_input">
            <input type="text" class="textbox" name="entrust_sign" id="entrust_sign" <?php if($vo[entrust_sign] == 0): ?>value="未委托"<?php else: ?>value="已委托"<?php endif; ?>" disabled="disabled">
        </td>
    </tr>
        <tr>
        <td class="item_title">担保合同委托签署</td>
        <td class="item_input">
            <input type="text" class="textbox" name="entrust_agency_sign" id="entrust_agency_sign" <?php if($vo[entrust_agency_sign] == 0): ?>value="未委托"<?php else: ?>value="已委托"<?php endif; ?>" disabled="disabled">
        </td>
    </tr>
    <tr>
        <td class="item_title">资产管理方合同委托签署</td>
        <td class="item_input">
            <input type="text" class="textbox" name="entrust_advisory_sign" id="entrust_advisory_sign" <?php if($vo[entrust_advisory_sign] == 0): ?>value="未委托"<?php else: ?>value="已委托"<?php endif; ?>" disabled="disabled">
        </td>
    </tr>
     <tr>
        <td class="item_title">项目简介:</td>
        <td class="item_input">
        <script id="editor" name="intro" type="text/plain" style="width:800px;height:200px; float:left;"><?php echo ($vo['intro']); ?></script>
        </td>
    </tr>
    <?php if($vo["deal_type"] == 0): ?><tr>
        <td class="item_title">贷后信息:</td>
        <td class="item_input">
        <script id="editor1" name="post_loan_message" type="text/plain" style="width:800px;height:200px; float:left;"><?php echo ($vo['post_loan_message']); ?></script>
        </td>
    </tr><?php endif; ?>
    <tr>
        <td class="item_title">委托投资标的说明:</td>
        <td class="item_input">
            <script id="editor2" name="contractDescription" type="text/plain" style="width:800px;height:200px; float:left;"><?php echo ($contract); ?></script>
            <!--
            <script type='text/javascript'> var eid = 'editor';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='editor' name='intro' style='width:800px; height:600px;' ><?php echo ($vo['intro']); ?></textarea> </div>
            -->
        </td>
    </tr>
    <tr>
        <td class="item_title">预览项目简介:</td>
        <td class="item_input">
            <a href ="/m.php?m=DealProject&a=show&id=<?php echo ($vo['id']); ?>" target="_blank">预览网页</a>
        </td>
    </tr>
    <tr>
        <td class="item_title">固定起息日</td>
        <td class="item_input">
            <input type="text" class="textbox" name="fixed_value_date" value="<?php echo ($vo["fixed_value_date"]); ?>" readonly="true"/>
        </td>
    </tr>
    <tr <?php if($vo['deal_type'] != 2): ?>style="display:none"<?php endif; ?>>
        <td class="item_title">基础资产描述</td>
        <td class="item_input">
            <textarea name="assets_desc" style="margin: 0px; width: 535px; height: 70px;"><?php echo ($vo['assets_desc']); ?></textarea>
        </td>
    </tr>
    <tr>
        <td class="item_title">业务状态</td>
        <td class="item_input">
            <select name="business_status" id="business_status" disabled="disabled">
                <?php if(is_array($project_business_status_map)): foreach($project_business_status_map as $status_value=>$status_name): ?><option value="<?php echo ($status_value); ?>" <?php if($status_value == $vo['business_status']): ?>selected="selected"<?php endif; ?>><?php echo ($status_name); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">状态:</td>
        <td class="item_input">
            <input type="radio" name="status" value="0" <?php if($vo["status"] == 0): ?>checked<?php endif; ?> <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>>正常
            <input type="radio" name="status" value="1" <?php if($vo["status"] == 1): ?>checked<?php endif; ?> <?php if($vo['business_status'] != $project_business_status['waitting']): ?>readonly<?php endif; ?>>作废
        </td>
    </tr>

 <tr>
  <td class="item_title"></td>
   </if>
  </td>
 </tr>
 <tr>
  <td colspan=2 class="bottomTd"></td>
 </tr>
</table>
</form>
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