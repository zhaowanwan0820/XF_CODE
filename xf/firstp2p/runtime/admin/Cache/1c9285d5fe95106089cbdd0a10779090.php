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

<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/deal.js"></script>

<script>
    $(document).ready(function(){
        $("#calcPrepay").click(function(){
            if($("#end_day").val() == '') {
                alert('请选择计息结束日期');
                return false;
            }
            var res = do_prepay();
            if(!res){
                return false;
            }
            $.ajax({
                url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=calc_prepay",
                data: 'ajax=1&deal_id='+$("#deal_id").val()+'&day='+$("#end_day").val(),
                dataType: "json",
                success: function(obj){
                    if(obj.errCode !=1000 && obj.errCode != 1001) {
                        alert(obj.errMsg);
                        return false;
                    }else {
                        res = true;
                        if(obj.errCode == 1001) {
                            res = confirm("选择此日期后将在提前还款锁定期前还款，确定此操作吗？");
                        }
                        if(res){
                            $("#has_calc").val($("#end_day").val());
                            //$("#interest_time").text(obj.data.interest_day);
                            $("#remain_days").text(obj.data.remain_days+'天');
                            if(obj.data.remain_days >= 90) {
                                $("#remain_days").css("color","red");
                                $("#remain_days").append(" (请联系资产管理部再次确认还款日期)")
                            }else{
                                $("#remain_days").removeAttr("style");
                            }
                            $("#remain_principal").text(obj.data.remain_principal+'元');
                            $("#prepay_interest").text(obj.data.prepay_interest+'元');
                            $("#prepay_compensation").text(obj.data.prepay_compensation+'元');
                            $("#loan_fee").text(obj.data.loan_fee+'元');
                            $("#consult_fee").text(obj.data.consult_fee+'元');
                            $("#guarantee_fee").text(obj.data.guarantee_fee+'元');
                            $("#pay_fee").text(obj.data.pay_fee+'元');
                            $("#canal_fee").text(obj.data.canal_fee+'元');
                            if(parseInt(obj.isDtb) == 1) {
                                $("#management_fee").text(obj.data.management_fee+'元');
                            }
                            $("#prepay_money").text(obj.data.prepay_money+'元');
                            $("#deal_prepay_money").val(obj.data.prepay_money);
                        }
                    }
                }
            });
        });

        $("#savePrepay").click(function(){
            if($("#has_calc").val() == '') {
                alert('在得出计算结果后再操作保存');
                return false;
            }
            $.ajax({
                url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=save_prepay",
                data: 'ajax=1&deal_id='+$("#deal_id").val()+'&day='+$("#has_calc").val()+'&repay_user_type='+$("#repay_user").val(),
                dataType: "json",
                success: function(obj){
                    if(obj.errCode !=1000) {
                        alert(obj.errMsg);
                        return false;
                    }else{
                        $("#is_save").val(1);
                        alert('保存成功');
                    }
                }
            });
        });

        $("#doPrepay").click(function(){
            if($("#has_calc").val() == '') {
                alert('当前数据尚未保存，请先操作保存');
                return false;
            }
            $.ajax({
                url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=do_prepay",
                data: 'ajax=1&deal_id='+$("#deal_id").val(),
                dataType: "json",
                success: function(obj){
                    if(obj.errCode !=1000) {
                        alert(obj.errMsg);
                    }else{
                        alert('操作成功');
                    }
                }
            });
        });

    })

    function audit(optype)
    {
        if($("#has_calc").val() == '') {
            alert('当前数据尚未保存，请先操作保存');
            return false;
        }
        <?php if($role == 'b'): ?>var repay_user = '借款人' ;
        if( 1 == $('#repay_user_type_by_a').val()){
            var repay_user = '代垫机构';
        }
        if( 2 == $('#repay_user_type_by_a').val()){
            var repay_user = '担保机构';
        }
        if( 5 == $('#repay_user_type_by_a').val()){
            var repay_user = '间接代偿担保机构';
        }

        if(Number($("#deal_prepay_money").val()) > Number($("#user_money").val()) && optype != 'return'){
            if(!confirm(repay_user+'账户余额不足，还款后'+repay_user+'账户余额将为负数！')){
                return false;
            }else{
                if(!confirm('是否确定将还款账户扣负？')){
                    return false;
                }
            }
        }<?php endif; ?>
        if ($('#deal_repay_id').val() == '' && $('#is_save').val() == '') {
            alert('当前数据尚未保存，请先操作保存');
            return false;
        }
        var type = 'GET';
        var data = {
                return_reason : $("#return_reason").val(),
                return_type : $("#return_type").val(),
                agree : $('#agree').val(),
                deal_repay_id : $("#deal_repay_id").val(),
                repay_user_type : $('#repay_user').val(),
        }
        url = ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=submitAudit&audit_type=5"+"&role="+$("#role").val()+"&deal_id="+$("#deal_id").val();
        if (optype == 'submit') {
            var type = "POST";
            var caution = confirm("确定提交");
        } else if (optype == 'agree') {
            var data = '';
            var caution = confirm("确认提交");
            if (caution == true) {
                $("#repay_user").removeAttr("disabled");
                var url = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=do_prepay&role="+$("#role").val()+"&deal_id="+$("#deal_id").val()
                $.ajax({
                    url: url,
                    data: 'ajax=1&deal_id='+$("#deal_id").val()+'&deal_repay_id='+$("#deal_repay_id").val(),
                    dataType: "json",
                    success: function(obj){
                        if(obj.errCode !=1000) {
                            alert(obj.errMsg);
                        }else{
                            alert('操作成功');
                            location.href = ROOT+"?m=Deal&a=yuqi&ref=1&<?php echo ($querystring); ?>"
                        }
                    }
                });
            }
            return;
        } else if (optype == 'return') {
            if ($("#return_type").val() == '0') {
                alert("请选择回退类型");
                return;
            }
            if ($("#return_reason").val() == '') {
                alert("请填写回退原因");
                return;
            }
            var type = "POST";
            var caution = confirm("确认回退");
        }
        if (caution == true) {
            $.ajax({
                url: url,
                data: data,
                type: type,
                dataType: "json",
                success: function(obj){
                    if(obj.errCode != 0) {
                        alert(obj.errMsg);
                    } else {
                        if (optype == 'submit') {
                            alert('提交审核成功');
                        }
                        if (optype == 'return') {
                            alert('回退成功');
                        }
                    }
                    //location.href = '<?php echo $redirectUrl; ?>';
                    location.href = 'm.php?m=Deal&a=yuqi&ref=1&role='+$("#role").val()+'&<?php echo ($querystring); ?>';
                }
            });
        }
    }

    // 计算利息天数
    function do_prepay() {
        var prepay_days_limit = $("#prepay_days_limit").val(); // 提前还款锁定期限
        var end_day = $("#end_day").val(); // 利息结束天数
        var end_day_arr = end_day.split('-');
        var end_interest_time = Date.parse(new Date(end_day_arr[0],end_day_arr[1]-1,end_day_arr[2],0,0,0)) / 1000;
        var start_interest_time = $("#start_interest_time").val(); // 利息开始天数
        //var remain_days = Math.ceil((end_interest_time - start_interest_time)/86400);

        var first_interest_time = $("#first_interest_time").val(); // 放款日期
        var remain_days =  Math.ceil((end_interest_time - first_interest_time)/86400);

        var repay_start_time = <?php echo ($deal['repay_start_time']+28800); ?>; // (服务器和正常差8小时) 放款日期
        var end_day_time = $("#end_day_time").val(); // 到期日期

        if(end_interest_time == repay_start_time) {
            alert('计息结束日期不能等于放款日期');
            return false;
        }
        if(end_interest_time >= end_day_time) {
            alert('计息结束日期不能大于等于到期日期');
            return false;
        }

        if(remain_days <=0) {
            alert('计息结束日期必须大于放款日期');
            return false;
        }
        /**
         *
         */
        if(remain_days < prepay_days_limit) {
            return confirm("选择此日期后将在提前还款锁定期前还款，确定此操作吗？");
        }
        return true;
    }
</script>
<form action="?m=Deal&a=save_apply_prepay" method="post" class="j-form-post">
<input type="hidden" name="deal_id" id="deal_id" value="<?php echo ($deal["id"]); ?>"/>
<input type="hidden" name="role" id="role" value="<?php echo ($role); ?>"/>
<input type="hidden" name="deal_repay_id" id="deal_repay_id" value="<?php echo ($deal_repay_id); ?>"/>
<input type="hidden" name="has_calc" id="has_calc" value="<?php echo ($has_calc); ?>"/>
<input type="hidden" name="start_interest_time" id="start_interest_time" value="<?php echo ($interest_time); ?>"/>
<input type="hidden" id="end_day_time" value="<?php echo strtotime($end_day);?>"/>
<input type="hidden" name="prepay_days_limit" id="prepay_days_limit" value="<?php echo ($deal["prepay_days_limit"]); ?>"/>
<input type="hidden"  name="repay_user_type_by_a" id="repay_user_type_by_a" value="<?php echo ($prepay['repay_type']); ?>">
<input type="hidden" id="first_interest_time" value="<?php echo strtotime(to_date($deal['repay_start_time'],'Y-m-d'))?>">
<input type="hidden" name="user_money" id="user_money" value="<?php echo ($user_money); ?>"/>
<input type="hidden" name="deal_prepay_money" id="deal_prepay_money" value="<?php echo ($prepay["prepay_money"]); ?>"/>
<input type="hidden" name="is_save" id="is_save" value="<?php echo ($is_save); ?>"/>

<div class="main_title"><?php echo ($deal["name"]); ?> 提前还款 <a href="<?php echo u("Deal/yuqi?ref=1&$querystring");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>

    <fieldset>
        <legend>贷款管理</legend>
            <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
                <tr class="row">
                    <td colspan="2">借款标题：<?php echo ($deal["name"]); ?></td>
                </tr>
                <tr class="row">
                    <td>剩余本金：<?php echo (format_price($data["principal"])); ?></td>
                    <td>借款期限：<?php echo ($deal["repay_time"]); ?><?php if($deal["loantype"] == 5): ?>天<?php else: ?>个月<?php endif; ?></td>
                </tr>
                <tr class="row">
                    <td>借款年利率：<?php echo (number_format($deal["rate"],2)); ?>%</td>
                    <td>提前还款锁定期：<?php echo ($deal["prepay_days_limit"]); ?>天</td>
                </tr>
                <tr class="row">
                    <td>出借人年化收益率：<?php echo (number_format($deal["rate"],2)); ?>%</td>
                    <td>提前还款违约金系数：<?php echo (number_format($deal["prepay_rate"],2)); ?>%</td>
                </tr>
                <tr class="row">
                    <td colspan="2">还款方式：<?php echo (get_loantype($deal["loantype"])); ?></td>
                </tr>
           </table>
        </fieldset>
    <table class="dataTable" style="margin-top:5px;border-top: double">
        <tr class="row">
            <td style="width:40%" align="center"><b>到期还款明细</b></td>
            <td align="center"><b>提前还款明细</b></td>
        </tr>
        <tr class="row">
            <td><div style="float:left;width:100px"> 到期日期：</div><div><?php echo ($end_day); ?></div></td>
            <td>计息结束日期：
                <input type="text" class="textbox" name="end_day" id="end_day" value="<?php echo to_date($prepay['prepay_time'],'Y-m-d')?>"
                <?php if($role == 'b'): ?>readonly = 'readonly'>
                    <?php else: ?>
                    onfocus="this.blur(); return showCalendar('end_day', '%Y-%m-%d', false, false, 'end_day');">
                <input type="button" class="button" id="btn_base_contract_repay_time" value="选择时间" onclick="return showCalendar('end_day', '%Y-%m-%d', false, false, 'end_day');">
                <input type="button" class="button" value="清空时间" onclick="$('#end_day').val('');"><?php endif; ?>
            </td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px;">放款日期：</div><div><?php echo to_date($deal['repay_start_time'],'Y-m-d');?></div></td>
            <td><div style="float: left;width:100px;">放款日期：</div><div><span id="interest_time" class="form_prepay"><?php echo to_date($deal['repay_start_time'],'Y-m-d');?></span></div></td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px;">借款期限：</div><div><?php echo ($deal["repay_time"]); ?><?php if($deal["loantype"] == 5): ?>天<?php else: ?>个月<?php endif; ?></div></td>
            <td><div style="float: left;width:100px">利息天数：</div><div><span id="remain_days" <?php if($prepay['remain_days'] >= 90): ?>style="color:red"<?php endif; ?>>
                 <?php echo ($prepay["remain_days"]); ?>天 <?php if($prepay['remain_days'] >= 90): ?>(请联系资产管理部再次确认还款日期)<?php endif; ?></span></div></td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px;">应还本金：</div><div><?php echo (format_price($data["principal"])); ?></div></td>
            <td><div style="float: left;width:100px;">应还本金：</div><div><span id="remain_principal"><?php echo (format_price($prepay["remain_principal"])); ?></span></div></td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px">应还利息：</div><div><?php echo (format_price($data["interest"])); ?></div></td>
            <td><div style="float: left;width:100px;">应还利息：</div><div><span id="prepay_interest"><?php echo (format_price($prepay["prepay_interest"])); ?></span></div></td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px;">提前还款违约金：</div><div> 0 元</div></td>
            <td><div style="float: left;width:100px;">提前还款违约金：</div><div><span id="prepay_compensation"><?php echo ($prepay["prepay_compensation"]); ?> 元</span></div></td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px;">手续费：</div><div><?php echo (format_price($data["loan_fee"])); ?></div></td>
            <td><div style="float: left;width:100px;">手续费：</div><div><span id="loan_fee"><?php echo (format_price($prepay["loan_fee"])); ?></span></div></td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px;">咨询费：</div><div><?php echo (format_price($data["consult_fee"])); ?></div></td>
            <td><div style="float: left;width:100px;">咨询费：</div><div><span id="consult_fee"><?php echo (format_price($prepay["consult_fee"])); ?></span></div></td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px;">担保费：</div><div><?php echo (format_price($data["guarantee_fee"])); ?></div></td>
            <td><div style="float: left;width:100px;">担保费：</div><div><span id="guarantee_fee"><?php echo (format_price($prepay["guarantee_fee"])); ?></span></div></td>
        </tr>
        <tr class="row">
            <td><div style="float: left;width:100px;">支付服务费：</div><div><?php echo (format_price($data["pay_fee"])); ?></div></td>
            <td><div style="float: left;width:100px;">支付服务费：</div><div><span id="pay_fee"><?php echo (format_price($prepay["pay_fee"])); ?></span></div></td>
        </tr>
        <?php if($deal["canal_agency_id"] > 1): ?><td><div style="float: left;width:100px;">渠道服务费：</div><div><?php echo (format_price($data["canal_fee"])); ?></div></td>
            <td><div style="float: left;width:100px;">渠道服务费：</div><div><span id="canal_fee"><?php echo (format_price($prepay["canal_fee"])); ?></span></div></td><?php endif; ?>
        <?php if($deal["isDtb"] == 1 ): ?><tr class="row">
                <td><div style="float: left;width:100px;">管理服务费：</div><div><?php echo (format_price($data["management_fee"])); ?></div></td>
                <td><div style="float: left;width:100px;">管理服务费：</div><div><span id="management_fee"><?php echo (format_price($prepay["management_fee"])); ?></span></div></td>
            </tr><?php endif; ?>
        <tr class="row">
            <td><div style="float: left;width:100px;">还款总额：</div><div><?php echo (format_price($data["repay_money"])); ?></div></td>
            <td><div style="float: left;width:100px;">还款总额：</div><div><span id="prepay_money"><?php echo (format_price($prepay["prepay_money"])); ?></span></div></td>
        </tr>
        <tr class="row">
            <td></td>
            <td>
                <div style="float: left;width:100px;">还款方：</div>
                <div>
                    <select type="select" name="repay_user_type" id='repay_user'
                    <?php if($role == 'b'): ?>disabled = 'disabled'<?php endif; ?>
                      >
                        <?php if(is_array($repay_user)): foreach($repay_user as $key=>$repay_user_item): ?><option value="<?php echo ($repay_user_item["type"]); ?>"
                                <?php if($repay_user_item["type"] == $prepay['repay_type']): ?>selected="selected"
                                <?php else: ?>
                                    <?php if($role != 'b' AND $repay_user_item["is_selected"] == 1): ?>selected="selected"<?php endif; ?><?php endif; ?>
                            ><?php echo ($repay_user_item["userName"]); ?></option><?php endforeach; endif; ?>
                    </select>
                </div>
            </td>
        </tr>
        <?php if($role == 'b'): ?><tr>
            <td colspan="2">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="item_title">退回类型</td>
                        <td class="item_input">
                            <select name="return_type" id="return_type">
                                <option value="0" <?php if($_REQUEST['return_type'] == 0): ?>selected<?php endif; ?>>请选择</option>
                                <?php if(is_array($return_type_list)): foreach($return_type_list as $key=>$item): ?><option value="<?php echo ($key); ?>"><?php echo ($item); ?></option><?php endforeach; endif; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="item_title">退回意见</td>
                        <td class="item_input">
                            <textarea id="return_reason" name="return_reason" style="height:85px;width:450px;"></textarea>
                        </td>
                    </tr>
                </table>
            </td>
        </tr><?php endif; ?>
        <tr>
            <td colspan="2" align="right">
                <?php if($role != 'b'): ?><input  class="button" id="calcPrepay" value="计算"><?php endif; ?>
<?php if($type == 1): ?><?php if($role == 'b'): ?><input  class="button" id="submitAudit" onclick="audit('return')" value="退回">
                    <?php if($not_ab != 1): ?><input  class="button" id="submitAudit" onclick="audit('agree')" value="还款"><?php endif; ?>
                <?php else: ?>
                    <input  class="button" id="savePrepay" value="保存">
                    <?php if($not_ab != 1): ?><input  class="button" id="submitAudit" onclick="audit('submit')" value="提交审核"><?php endif; ?>
                    <!--<input  class="button" id="doPrepay" value="提前还款">--><?php endif; ?>
                <?php if($not_ab == 1): ?><input  class="button" id="doPrepay" value="提前还款"><?php endif; ?><?php endif; ?>
            </td>
        </tr>
    </table>
</form>
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