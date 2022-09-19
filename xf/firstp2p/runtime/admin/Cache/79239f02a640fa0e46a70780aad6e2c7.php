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

<script type="text/javascript" src="__TMPL__ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/ueditor.all.min.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/lang/zh-cn/zh-cn.js"></script>

<script type="text/javascript">
function checkSubmit() {
    var name = $("#name").val();
    if($.trim(name).length == 0){
        alert("请正确填写项目名称");
        return false;
    }
    var jys_number = $("#jys_number").val();
    if($.trim(jys_number).length == 0){
        alert("请正确填写交易所备案产品编号");
        return false;
    }
    var fx_uid = $("#fx_uid").val();
    if(isNaN(fx_uid) || $.trim(fx_uid).length == 0){
        alert("请正确填写发行人ID");
        return false;
    }
    var consult_rate = $("#consult_rate").val();
    if(isNaN(consult_rate) || $.trim(consult_rate).length == 0){
        alert("请正确填写借款咨询费率");
        return false;
    }
    var guarantee_rate = $("#guarantee_rate").val();
    if(isNaN(guarantee_rate) || $.trim(guarantee_rate).length == 0){
        alert("请正确填写借款担保费率");
        return false;
    }
    var invest_adviser_rate = $("#invest_adviser_rate").val();
    if(isNaN(invest_adviser_rate) || $.trim(invest_adviser_rate).length == 0){
        alert("请正确填写投资顾问费率");
        return false;
    }
    var invest_adviser_real_rate = $("#invest_adviser_real_rate").val();
    if(isNaN(invest_adviser_real_rate) || $.trim(invest_adviser_real_rate).length == 0){
        alert("请正确填写实际投资顾问费率");
        return false;
    }
    var publish_server_rate = $("#publish_server_rate").val();
    if(isNaN(publish_server_rate) || $.trim(publish_server_rate).length == 0){
        alert("请正确填写发行服务费率");
        return false;
    }
    var publish_server_real_rate = $("#publish_server_real_rate").val();
    if(isNaN(publish_server_real_rate) || $.trim(publish_server_real_rate).length == 0){
        alert("请正确填写实际发行服务费率");
        return false;
    }
    var hang_server_rate = $("#hang_server_rate").val();
    if(isNaN(hang_server_rate) || $.trim(hang_server_rate).length == 0){
        alert("请正确填写挂牌服务费率");
        return false;
    }
    var amount = $("#amount").val();
    if(isNaN(amount) || $.trim(amount).length == 0){
        alert("请正确填写借款金额");
        return false;
    }
    var repay_type = $("#repay_type").val();
    if(repay_type == 1){
        var repay_time_day = $("#repay_time_day").val();
        if(isNaN(repay_time_day) || $.trim(repay_time_day).length == 0){
            alert("请正确填写借款期限");
            return false;
        }
    }
    var expect_year_rate = $("#expect_year_rate").val();
    if(isNaN(expect_year_rate) || $.trim(expect_year_rate).length == 0){
        alert("请正确填写预期年化收益率");
        return false;
    }
    var money_todo = $("#money_todo").val();
    if($.trim(money_todo).length == 0){
        alert("请正确填写资金用途");
        return false;
    }
    var lock_days = $("#lock_days").val();
    if(isNaN(lock_days) || $.trim(lock_days).length == 0){
        alert("请正确填写锁定期");
        return false;
    }
    var min_amount = $("#min_amount").val();
    if(isNaN(min_amount) || $.trim(min_amount).length == 0){
        alert("请正确填写最低起投金额");
        return false;
    }
    var ahead_repay_rate = $("#ahead_repay_rate").val();
    if(isNaN(ahead_repay_rate) || $.trim(ahead_repay_rate).length == 0){
        alert("请正确填写提前还款违约金费率");
        return false;
    }
    return true;
}

function changeRepay(){
    var repay_type = $('#repay_type').val();
    //切换html
    if(repay_type == 1){
        $('#repay_time').hide();
        $('#repay_time_ji').hide();
        $('#repay_time_day_div').show();
    }else if(repay_type == 2 || repay_type == 3){
        $('#repay_time').show();
        $('#repay_time_day_div').hide();
        $('#repay_time_ji').hide();
    }else if(repay_type == 4){
        $('#repay_time_ji').show();
        $('#repay_time').hide();
        $('#repay_time_day_div').hide();
    }
}
</script>
<div class="main">
<div class="main_title">项目信息 <a href="<?php echo u("OexchangeProject/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form id="addform" name="edit" action="__APP__?m=OexchangeProject&a=add" method="post">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">项目名称:</td>
        <td class="item_input">
        <input type="text" class="textbox require" name="name" id="name" value="<?php echo ($project['name']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> />
        <span id="name_tip"></span>
        </td>
    </tr>
    <tr>
        <td class="item_title">交易所备案产品编号:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="jys_number" id="jys_number" value="<?php echo ($project['jys_number']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> />
        </td>
    </tr>
    <tr>
        <td class="item_title">交易所:</td>
        <td class="item_input">
            <select name="jys_id" id="jys_id" <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <?php if(is_array($agency['9'])): foreach($agency['9'] as $type_key=>$type_item): ?><option value="<?php echo ($type_item['id']); ?>" <?php if($type_item['id'] == $project['jys_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item['name']); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">结算方式:</td>
        <td class="item_input">
            <select name="settle_type" id="settle_type" <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <option value="1" <?php if(1 == $project['settle_type']): ?>selected="selected"<?php endif; ?>>场内</option>
                <option value="2" <?php if(2 == $project['settle_type']): ?>selected="selected"<?php endif; ?>>场外</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">发行人ID:</td>
        <td class="item_input">
        <input type="text" class="textbox require" name="fx_uid" id="fx_uid" value="<?php echo ($project['fx_uid']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> />
        <a href='<?php echo u("User/index");?>' target="__blank">会员列表</a>
        <span id="user_name"></span>
        </td>
    </tr>
    <tr>
        <td class="item_title">资产类型:</td>
        <td class="item_input">
          <select name="asset_type" id="asset_type" <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <option value="1" <?php if(1 == $project['asset_type']): ?>selected="selected"<?php endif; ?>>收益权转让</option>
                <option value="2" <?php if(2 == $project['asset_type']): ?>selected="selected"<?php endif; ?>>定向融资计划</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">咨询机构:</td>
        <td class="item_input">
            <select name="consult_id" id="consult_id" <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <?php if(is_array($agency['2'])): foreach($agency['2'] as $type_key=>$type_item): ?><option value="<?php echo ($type_item['id']); ?>" <?php if($type_item['id'] == $project['consult_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item['name']); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">担保机构:</td>
        <td class="item_input">
            <select name="guarantee_id" id="guarantee_id" <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <?php if(is_array($agency['1'])): foreach($agency['1'] as $type_key=>$type_item): ?><option value="<?php echo ($type_item['id']); ?>" <?php if($type_item['id'] == $project['guarantee_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item['name']); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">投资顾问机构:</td>
        <td class="item_input">
            <select name="invest_adviser_id" id="invest_adviser_id" <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <?php if(is_array($agency['11'])): foreach($agency['11'] as $type_key=>$type_item): ?><option value="<?php echo ($type_item['id']); ?>" <?php if($type_item['id'] == $project['invest_adviser_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item['name']); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">业务管理方:</td>
        <td class="item_input">
            <select name="business_manage_id" id="business_manage_id" <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <?php if(is_array($agency['12'])): foreach($agency['12'] as $type_key=>$type_item): ?><option value="<?php echo ($type_item['id']); ?>" <?php if($type_item['id'] == $project['business_manage_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item['name']); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">借款咨询费率:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="consult_rate" id="consult_rate" value="<?php echo ($project['consult_rate']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> %
        </td>
    </tr>
    <tr>
        <td class="item_title">借款咨询费收取方式:</td>
        <td class="item_input">
            <input type="radio" name="consult_type" value="1" <?php if(1 == $project['consult_type'] or $view_status == 1): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 前收
            <input type="radio" name="consult_type" value="2" <?php if(2 == $project['consult_type']): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">借款担保费率:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="guarantee_rate" id="guarantee_rate" value="<?php echo ($project['guarantee_rate']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> %
        </td>
    </tr>
    <tr>
        <td class="item_title">借款担保费收取方式:</td>
        <td class="item_input">
            <input type="radio" name="guarantee_type" value="1" <?php if(1 == $project['guarantee_type'] or $view_status == 1): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 前收
            <input type="radio" name="guarantee_type" value="2" <?php if(2 == $project['guarantee_type']): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">投资顾问费率:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="invest_adviser_rate" id="invest_adviser_rate" value="<?php echo ($project['invest_adviser_rate']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> %
        </td>
    </tr>
    <tr>
        <td class="item_title">实际投资顾问费率:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="invest_adviser_real_rate" id="invest_adviser_real_rate" value="<?php echo ($project['invest_adviser_real_rate']); ?>" <?php if($view_status > 3): ?>readonly="readonly"<?php endif; ?> /> %
        </td>
    </tr>
    <tr>
        <td class="item_title">投资顾问费收取方式:</td>
        <td class="item_input">
            <input type="radio" name="invest_adviser_type" value="1" <?php if(1 == $project['invest_adviser_type'] or $view_status == 1): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 前收
            <input type="radio" name="invest_adviser_type" value="2" <?php if(2 == $project['invest_adviser_type']): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">发行服务费率:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="publish_server_rate" id="publish_server_rate" value="<?php echo ($project['publish_server_rate']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> %
        </td>
    </tr>
    <tr>
        <td class="item_title">实际发行服务费率:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="publish_server_real_rate" id="publish_server_real_rate" value="<?php echo ($project['publish_server_real_rate']); ?>" <?php if($view_status > 3): ?>readonly="readonly"<?php endif; ?> /> %
        </td>
    </tr>
    <tr>
        <td class="item_title">发行服务费收取方式:</td>
        <td class="item_input">
            <input type="radio" name="publish_server_type" value="1" <?php if(1 == $project['publish_server_type'] or $view_status == 1): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 前收
            <input type="radio" name="publish_server_type" value="2" <?php if(2 == $project['publish_server_type']): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">挂牌服务费率:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="hang_server_rate" id="hang_server_rate" value="<?php echo ($project['hang_server_rate']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> %
        </td>
    </tr>
    <tr>
        <td class="item_title">挂牌服务费收取方式:</td>
        <td class="item_input">
            <input type="radio" name="hang_server_type" value="1" <?php if(1 == $project['hang_server_type'] or $view_status == 1): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 前收
            <input type="radio" name="hang_server_type" value="2" <?php if(2 == $project['hang_server_type']): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">借款金额:</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="amount" id="amount" value="<?php echo ($project['amount']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> 元
        </td>
    </tr>
    <tr>
        <td class="item_title">实际募集金额:</td>
        <td class="item_input">
            <input type="text" class="textbox" readonly="readonly" value="<?php echo ($project['real_amount']); ?>" /> 元
        </td>
    </tr>
    <tr>
        <td class="item_title">还款方式(与产品期限联动):</td>
        <td class="item_input">
            <select id="repay_type" name="repay_type" onchange="javascript:changeRepay();" <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <option value="1" <?php if(1 == $project['repay_type']): ?>selected="selected"<?php endif; ?>>到期支付本金收益（天）</option>
                <option value="2" <?php if(2 == $project['repay_type']): ?>selected="selected"<?php endif; ?>>到期支付本金收益（月）</option>
                <option value="3" <?php if(3 == $project['repay_type']): ?>selected="selected"<?php endif; ?>>按月支付收益到期还本</option>
                <option value="4" <?php if(4 == $project['repay_type']): ?>selected="selected"<?php endif; ?>>按季支付收益到期还本</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("REPAY_TIME");?>:</td>
        <td class="item_input">
            <select id="repay_time" name="repay_time" <?php if($project['repay_type'] != 2 and $project['repay_type'] != 3): ?>style="display: none;"<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <?php if(is_array($repay_time)): foreach($repay_time as $time_key=>$time_item): ?><option value="<?php echo ($time_key); ?>" <?php if($project['repay_type'] != 1 and $time_key == $project['repay_time']): ?>selected="selected"<?php endif; ?>><?php echo ($time_item); ?></option><?php endforeach; endif; ?>
            </select>
            <select id="repay_time_ji" name="repay_time_ji" <?php if($project['repay_type'] != 4): ?>style="display: none;"<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?>>
                <?php if(is_array($repay_time_ji)): foreach($repay_time_ji as $time_key=>$time_item): ?><option value="<?php echo ($time_key); ?>" <?php if($project['repay_type'] == 4 and $time_key == $project['repay_time']): ?>selected="selected"<?php endif; ?>><?php echo ($time_item); ?></option><?php endforeach; endif; ?>
            </select>
            <div  id="repay_time_day_div" <?php if($project['repay_type'] > 1): ?>style="display: none;"<?php endif; ?>>
            <input type="text" class="changepmt textbox"  SIZE="8" name="repay_time_day" id="repay_time_day" <?php if($project['repay_type'] == 1): ?>value="<?php echo ($project['repay_time']); ?>"<?php endif; ?> <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> 
            <span id='tian'>天</span>
            </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">预期年化收益率:</td>
        <td class="item_input">
            <input type="text" class="textbox require" SIZE="8"  name="expect_year_rate" id="expect_year_rate" value="<?php echo ($project['expect_year_rate']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> > %
        </td>
    </tr>
    <tr>
        <td class="item_title">资金用途：</td>
        <td class="item_input">
            <input type="text" class="textbox require" SIZE="8"  name="money_todo" id="money_todo" value="<?php echo ($project['money_todo']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> />
        </td>
    </tr>
    <tr>
        <td class="item_title">锁定期：</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="lock_days" id="lock_days" value="<?php echo ($project['lock_days']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> 天
        </td>
    </tr>
    <tr>
        <td class="item_title">最低起投金额</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="min_amount" id="min_amount" value="<?php echo ($project['min_amount']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> 元
        </td>
    </tr>

    <tr>
        <td class="item_title">提前还款违约金费率</td>
        <td class="item_input">
            <input type="text" class="textbox require" name="ahead_repay_rate" id="ahead_repay_rate" value="<?php echo ($project['ahead_repay_rate']); ?>" <?php if($view_status > 2): ?>readonly="readonly"<?php endif; ?> /> %
        </td>
    </tr>
    <tr>
        <td class="item_title">借款状态:</td>
        <td class="item_input">
            <input type="radio" name="deal_status" value="1" <?php if(1 == $project['deal_status'] or $view_status == 1): ?>checked=""<?php endif; ?> <?php if($view_status > 2 and !$bCanQR): ?>disabled="disabled"<?php endif; ?> /> 等待确认
            <input type="radio" name="deal_status" value="2" <?php if(2 == $project['deal_status']): ?>checked=""<?php endif; ?> <?php if($view_status != 2): ?>disabled="disabled"<?php endif; ?> /> 进行中
            <input type="radio" name="deal_status" value="3" <?php if(3 == $project['deal_status']): ?>checked=""<?php endif; ?> disabled="disabled" /> 还款中
            <input type="radio" name="deal_status" value="4" <?php if(4 == $project['deal_status']): ?>checked=""<?php endif; ?> disabled="disabled" /> 已还清
        </td>
    </tr>
    <tr>
        <td class="item_title">状态:</td>
        <td class="item_input">
            <input type="radio" name="is_ok" value="1" <?php if(1 == $project['is_ok'] or $view_status == 1): ?>checked=""<?php endif; ?> <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 正常
            <input type="radio" name="is_ok" value="0" <?php if(0 == $project['is_ok'] and $view_status != 1): ?>checked=""<?php endif; ?>  <?php if($view_status > 2): ?>disabled="disabled"<?php endif; ?> /> 作废
        </td>
    </tr>
    <?php if($view_status < 4): ?><tr>
        <td class="item_title"></td>
        <td class="item_input">
            <input type="hidden" name="id" value="<?php echo ($project['id']); ?>">
            <input type="hidden" name="is_save" value="1">
            <input type="submit" class="button" value="保存" name="submit" onclick="return checkSubmit();"/>
            <input type="reset" class="button" value="<?php echo L("RESET");?>" />
        </td>
    </tr><?php endif; ?>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>
</form>
</div>
<script>
function zsyh(user_id){
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
                $("#fx_uid").val('');
                $("#user_name").text('');
                $("#fx_uid").focus();
            }
        }
    });
}
$(document).ready(function(){
    //检验user_id是否存在 如果存在则显示用户名
    $("#fx_uid").bind("blur",function(){
        var user_id = $(this).val();
        if(isNaN(user_id)){
            alert("必须为数字");
            return false;
        }
        if(user_id>0){
            zsyh(user_id);
        }
    });
    var user_id = $("#fx_uid").val();
    if(user_id>0){
        zsyh(user_id);
    }
    $("#invest_adviser_rate").bind("blur",function(){
        var val = $(this).val();
        $("#invest_adviser_real_rate").val(val);
    });
    $("#publish_server_rate").bind("blur",function(){
        var val = $(this).val();
        $("#publish_server_real_rate").val(val);
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