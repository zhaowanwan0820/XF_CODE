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

<form action="?m=Deal&a=do_force_repay" method="post" class="j-form-post">
<input type="hidden" name="deal_id" id="deal_id" value="<?php echo ($deal["id"]); ?>"/>
<input type="hidden" name="role" id="role" value="<?php echo ($role); ?>"/>
<input type="hidden" id="today" value="<?php echo ($today); ?>">
<input type="hidden" name="querystring" value="<?php echo ($querystring); ?>">
<input type="hidden"  name="repay_user_type_by_a" id="repay_user_type_by_a" value="<?php echo ($repay_user_type); ?>">
<input type="hidden" id="agency_money" value="<?php echo ($agency_money); ?>">
<input type="hidden" id="advance_money" value="<?php echo ($advance_money); ?>">
<input type="hidden" id="generation_recharge_money" value="<?php echo ($generation_recharge_money); ?>">
<input type="hidden" id="indirect_agency_money" value="<?php echo ($indirect_agency_money); ?>">
<input type="hidden" id="user_money" value="<?php echo ($user_money); ?>">
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
<div class="main_title"><?php echo ($deal["name"]); ?> 强制还款 <a href="<?php echo u("Deal/yuqi?ref=1&$querystring");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div id="edit_button">
   <!-- <input type="button" class="button" value="编辑" onclick="edit_fee();" />-->
    还款方
    <select type="select" name="repay_user_type" id='repay_user'
    <?php if($role == 'b'): ?>disabled = 'disabled'<?php endif; ?>
    >
    <?php if(is_array($repay_user)): foreach($repay_user as $key=>$repay_user_item): ?><option value="<?php echo ($repay_user_item["type"]); ?>"
            <?php if($repay_user_item["type"] == $repay_user_type): ?>selected="selected"
            <?php else: ?>
                <?php if($role != 'b' AND $repay_user_item["is_selected"] == 1): ?>selected="selected"<?php endif; ?><?php endif; ?>
        ><?php echo ($repay_user_item["userName"]); ?></option><?php endforeach; endif; ?>
</select>

</div>
<div id="save_button" style="display:none"><input type="button" class="button" value="保存" onclick="save_fee();" /></div>
<input type="hidden" name="deal_id" value="<?php echo ($deal["id"]); ?>"/>
    <tr>
        <td colspan="12" class="topTd" >&nbsp; </td>
    </tr>
    <tr class="row" >
        <th>选择还款</th>
        <th>还款日</th>
        <th>已还金额</th>
        <th>待还金额</th>
        <th>待还本息</th>
        <th>手续费</th>
        <th>咨询费</th>
        <th>担保费</th>
        <th>支付服务费</th>
        <th>渠道服务费</th>
        <?php if($deal["isDtb"] == 1 ): ?><th>管理服务费</th><?php endif; ?>
        <th>逾期费用</th>
        <th>状态</th>
        <th>操作</th>
    </tr>
    <?php if(is_array($loan_list)): $id = 0; $__LIST__ = $loan_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$loan): ++$id;$mod = ($id % 2 )?><tr class="row">
        <td><?php if($loan["status"] != 0): ?><?php else: ?>
            <input type="checkbox" name="repay_to[]" value="<?php echo ($loan["id"]); ?>" data-day="<?php echo ($loan["repay_day"]); ?>" <?php if(in_array($loan['id'], $chk_ids)): ?>checked="checked"<?php endif; ?> <?php if($role == 'b'): ?>onclick="return false"<?php endif; ?> /><?php endif; ?></td>
        <td> <?php echo ($loan["repay_day"]); ?> </td>
        <td> <?php echo ($loan["month_has_repay_money_all"]); ?> </td>
        <td><?php echo ($loan["month_need_all_repay_money"]); ?></td>
        <td> <?php echo ($loan["month_repay_money"]); ?> </td>

        <td class="service_fee"> <?php echo ($loan["loan_fee"]); ?> </td>
        <td class="service_fee"> <?php echo ($loan["consult_fee"]); ?> </td>
        <td class="service_fee"> <?php echo ($loan["guarantee_fee"]); ?> </td>
        <td class="service_fee"> <?php echo ($loan["pay_fee"]); ?> </td>
        <td class="service_fee"> <?php echo ($loan["canal_fee"]); ?> </td>

        <?php if($deal["isDtb"] == 1 ): ?><td class="service_fee"> <?php echo ($loan["management_fee"]); ?> </td><?php endif; ?>

        <td class="service_fee_input" style="display:none"><input type="text" name="loan_fee_arr[]" value="<?php echo ($loan["loan_fee"]); ?>" <?php if($loan["status"] != 0): ?>disabled="disabled"<?php endif; ?> /></td>
        <td class="service_fee_input" style="display:none"><input type="text" name="consult_fee_arr[]" value="<?php echo ($loan["consult_fee"]); ?>" <?php if($loan["status"] != 0): ?>disabled="disabled"<?php endif; ?>/></td>
        <td class="service_fee_input" style="display:none"><input type="text" name="guarantee_fee_arr[]" value="<?php echo ($loan["guarantee_fee"]); ?>" <?php if($loan["status"] != 0): ?>disabled="disabled"<?php endif; ?>/></td>
        <td class="service_fee_input" style="display:none"><input type="text" name="pay_fee_arr[]" value="<?php echo ($loan["pay_fee"]); ?>" <?php if($loan["status"] != 0): ?>disabled="disabled"<?php endif; ?>/></td>
        <td class="service_fee_inrepput" style="display:none"><input type="text" name="canal_fee_arr[]" value="<?php echo ($loan["canal_fee"]); ?>" <?php if($loan["status"] != 0): ?>disabled="disabled"<?php endif; ?>/></td>

        <?php if($deal["isDtb"] == 1 ): ?><td class="service_fee_input" style="display:none"><input type="text" name="management_fee_arr[]" value="<?php echo ($loan["management_fee"]); ?>" <?php if($loan["status"] != 0): ?>disabled="disabled"<?php endif; ?>/></td><?php endif; ?>

        <td> <?php echo ($loan["impose_money"]); ?> </td>
        <td> <?php echo ($loan["status_text"]); ?> </td>
        <td>
            <?php if($loan["status"] == 0 ): ?><?php if($role == 'b'): ?><a href="javascript:part_user_repay('<?php echo ($loan["id"]); ?>')">查看详情</a>
                <?php else: ?>
                    <a href="javascript:part_user_repay('<?php echo ($loan["id"]); ?>')">部分用户还款</a><?php endif; ?><?php endif; ?>
            <a href="javascript:export_repay_user_bank_list('<?php echo ($loan["id"]); ?>')">投资账户详情</a>
            <a href="javascript:offline_repay('<?php echo ($loan["id"]); ?>')">线下还款</a>
        </td>
    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
</table>
<div style="maigin: 20px;">
    <input type="checkbox" name="ignore_impose_money" id="ignore_impose_money"
    <?php if($ignore_impose_money): ?>checked="checked"<?php endif; ?> value="1"
    <?php if($role == 'b'): ?>onclick="return false;"<?php endif; ?>><label for="ignore_impose_money">不执行逾期罚息</label>
</div>
<?php if($role == 'b'): ?><table cellpadding="0" cellspacing="0">
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
    <input  class="button" id="submitAudit" onclick="audit('return')" value="退回">
    <input type="submit" class="button" onclick="return confirmSubmit();" value="还款">
<?php else: ?>
    <input  class="button" id="submitAudit" onclick="audit('submit')" value="提交"><?php endif; ?>
</form>

<script type="text/javascript" charset="utf-8">
    function confirmSubmit(){
        var chk_value =[];
        var is_beyond = false;
        var money = 0;
        $('input[name="repay_to[]"]:checked').each(function(){
            var days = dateDiff($(this).attr('data-day'),$("#today").val());
            if(days > 20) {
                is_beyond = true;
            }
            repay_money = Number($(this).parent().next().next().next().text().split(",").join(""));
            money=Number(Number(money+repay_money).toFixed(2));
            chk_value.push($(this).val());
        });

        if(chk_value.length == 0){
            alert("请选择还款");
            return false;
        }
        res1 = true;
        //if(is_beyond) {
        //    res1 = confirm('所选择的还款日距今日大于20天，是否继续操作？');
        //}
        var repay_user = '借款人' ;
        if( 1 == $('#repay_user_type_by_a').val()){
            var repay_user = '代垫机构';
        }
        if( 2 == $('#repay_user_type_by_a').val()){
            var repay_user = '担保机构';
        }

        if($("#repay_user").val() == 1){
            if(res1 && money > Number($("#advance_money").val())){
                return confirm('代垫账户余额不足，还款后代垫账户余额将为负数！');
            }
        }else if($("#repay_user").val() == 2){
            if(res1 && money > Number($("#agency_money").val())){
                return confirm('代偿账户余额不足，还款后代偿账户余额将为负数！');
            }
        }else if($("#repay_user").val() == 3){
            if(res1 && money > Number($("#generation_recharge_money").val())){
                return confirm('代充值账户余额不足，还款后代充值账户余额将为负数！');
            }
        }else if($("#repay_user").val() == 5){
            if(res1 && money > Number($("#indirect_agency_money").val())){
                return confirm('间接代偿账户余额不足，还款后间接代偿账户余额将为负数！');
            }
        }else{
            if(res1 && money > Number($("#user_money").val())){
                return confirm('借款人账户余额不足，还款后账户借款人账户余额将为负数！');
            }
        }

        return res1 ? true : false;
    }

    function edit_fee() {
        $("#save_button").show();
        $("#edit_button").hide();
        $("#dataTable").find(".service_fee_input").show();
        $("#dataTable").find(".service_fee").hide();
    }

    function save_fee() {
        $("#edit_button").show();
        $("#save_button").hide();

        var loan_fee = new Array();
        $('input[name="loan_fee_arr[]"]').each(function(){
            loan_fee.push($(this).val());
        });

        var consult_fee = new Array();
        $('input[name="consult_fee_arr[]"]').each(function(){
            consult_fee.push($(this).val());
        });

        var guarantee_fee = new Array();
        $('input[name="guarantee_fee_arr[]"]').each(function(){
            guarantee_fee.push($(this).val());
        });

        var pay_fee = new Array();
        $('input[name="pay_fee_arr[]"]').each(function(){
            pay_fee.push($(this).val());
        });

        var deal_id = <?php echo ($deal["id"]); ?>;

        var passData = {deal_id:deal_id,loan_fee:loan_fee,consult_fee:consult_fee,guarantee_fee:guarantee_fee,pay_fee:pay_fee,canal_fee:canal_fee};
        var isDtb = <?php echo ($deal["isDtb"]); ?>;
        if(isDtb == 1) {
            var management_fee = new Array();
            $('input[name="management_fee_arr[]"]').each(function(){
                management_fee.push($(this).val());
            });
            passData.management_fee = management_fee;
        }

        $.post("/m.php?m=Deal&a=save_service_fee", passData, function(result){
            var rs = $.parseJSON(result);
            if (rs.status) {
                window.location.reload();
            } else {
                alert("操作失败！");
                return false;
            }
        });
    }
    //sDate1和sDate2是2016-12-18格式
    function  dateDiff(sDate1, sDate2){
        var  aDate,  oDate1,  oDate2,  iDays
        aDate  =  sDate1.split("-")
        oDate1  =  new  Date(aDate[0],aDate[1]-1,aDate[2],0,0,0).getTime();
        aDate  =  sDate2.split("-")
        oDate2  =  new  Date(aDate[0],aDate[1]-1,aDate[2],0,0,0).getTime();
        iDays  =  parseInt(Math.abs(oDate1  -  oDate2)/1000/86400)  //把相差的毫秒数转换为天数
        return  iDays;
    }

    function audit(optype)
    {
        var chk_value =[];
        var is_beyond = false;
        var money = 0;
        $('input[name="repay_to[]"]:checked').each(function(){
            var days = dateDiff($(this).attr('data-day'),$("#today").val());
            if(days > 20) {
                is_beyond = true;
            }
            repay_money = Number($(this).parent().next().next().next().text().split(",").join(""));
            money=Number(Number(money+repay_money).toFixed(2));
            chk_value.push($(this).val());
        });

        if(chk_value.length == 0 && optype == 'submit'){
            alert("请选择还款");
            return false;
        }
        res1 = true;
        <?php if($role != 'b'): ?>$("#repay_user").removeAttr("disabled");
        if(is_beyond) {
            res1 = confirm('所选择的还款日距今日大于20天，是否继续操作？');
            if (res1 == false) {
                return;
            }
        }
        <?php else: ?>
        var repay_user = '借款人' ;
        if( 1 == $('#repay_user_type_by_a').val()){
            var repay_user = '代垫机构';
        }
         if( 2 == $('#repay_user_type_by_a').val()){
            var repay_user = '担保机构';
        }
        if(res1 && money > Number($("#user_money").val()) && optype == 'submit'){
            if(!confirm(repay_user+'账户余额不足，还款后'+repay_user+'账户余额将为负数！')){
                return false;
            }else{
                return confirm('是否确定将还款账户扣负？');
            }
        }<?php endif; ?>
        var type = 'GET';
        var data = {
                return_reason : $("#return_reason").val(),
                return_type : $("#return_type").val(),
                agree : $('#agree').val(),
                id : $("#deal_id").val(),
                deal_repay_id : chk_value.join(),
                ignore_impose_money : $('#ignore_impose_money').is(':checked'),
                repay_user_type : $('#repay_user').val(),
        }
        url = ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=submitAudit&audit_type=4&role="+$("#role").val()+"&deal_id="+$("#deal_id").val();
        if (optype == 'submit') {
            var type = "POST";
            var caution = confirm("确定提交");
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
                success: function(obj) {
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

    /**
     * 部分用户还款
     * @param dealRepayId
     */
    function part_user_repay(dealRepayId) {
        url =  ROOT+'?m=Deal&a=part_user_repay&deal_repay_id='+dealRepayId+'&role='+$("#role").val();

        window.open(url);
    }

    /**
     * 线下还款
     * @param dealRepayId
     */
    function offline_repay(dealRepayId) {
        url =  ROOT+'?m=Deal&a=offline_repay&deal_repay_id='+dealRepayId+'&role='+$("#role").val();

        window.open(url);
    }

    /**
     * 导出用户账户
     * @param dealRepayId
     */
    function export_repay_user_bank_list(dealRepayId) {
        url =  ROOT+'?m=Deal&a=export_repay_user_bank_list&deal_repay_id='+dealRepayId+'&role='+$("#role").val();

        window.open(url);
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