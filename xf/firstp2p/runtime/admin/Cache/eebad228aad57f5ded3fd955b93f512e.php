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
<div class="main_title"><?php echo ($deal["name"]); ?> ???????????? <a href="<?php echo u("Deal/yuqi?ref=1&$querystring");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div id="edit_button">
   <!-- <input type="button" class="button" value="??????" onclick="edit_fee();" />-->
    ?????????
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
<div id="save_button" style="display:none"><input type="button" class="button" value="??????" onclick="save_fee();" /></div>
<input type="hidden" name="deal_id" value="<?php echo ($deal["id"]); ?>"/>
    <tr>
        <td colspan="12" class="topTd" >&nbsp; </td>
    </tr>
    <tr class="row" >
        <th>????????????</th>
        <th>?????????</th>
        <th>????????????</th>
        <th>????????????</th>
        <th>????????????</th>
        <th>?????????</th>
        <th>?????????</th>
        <th>?????????</th>
        <th>???????????????</th>
        <th>???????????????</th>
        <?php if($deal["isDtb"] == 1 ): ?><th>???????????????</th><?php endif; ?>
        <th>????????????</th>
        <th>??????</th>
        <th>??????</th>
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
            <?php if($loan["status"] == 0 ): ?><?php if($role == 'b'): ?><a href="javascript:part_user_repay('<?php echo ($loan["id"]); ?>')">????????????</a>
                <?php else: ?>
                    <a href="javascript:part_user_repay('<?php echo ($loan["id"]); ?>')">??????????????????</a><?php endif; ?><?php endif; ?>
            <a href="javascript:export_repay_user_bank_list('<?php echo ($loan["id"]); ?>')">??????????????????</a>
            <a href="javascript:offline_repay('<?php echo ($loan["id"]); ?>')">????????????</a>
        </td>
    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
</table>
<div style="maigin: 20px;">
    <input type="checkbox" name="ignore_impose_money" id="ignore_impose_money"
    <?php if($ignore_impose_money): ?>checked="checked"<?php endif; ?> value="1"
    <?php if($role == 'b'): ?>onclick="return false;"<?php endif; ?>><label for="ignore_impose_money">?????????????????????</label>
</div>
<?php if($role == 'b'): ?><table cellpadding="0" cellspacing="0">
        <tr>
            <td class="item_title">????????????</td>
            <td class="item_input">
                <select name="return_type" id="return_type">
                    <option value="0" <?php if($_REQUEST['return_type'] == 0): ?>selected<?php endif; ?>>?????????</option>
                    <?php if(is_array($return_type_list)): foreach($return_type_list as $key=>$item): ?><option value="<?php echo ($key); ?>"><?php echo ($item); ?></option><?php endforeach; endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="item_title">????????????</td>
            <td class="item_input">
                <textarea id="return_reason" name="return_reason" style="height:85px;width:450px;"></textarea>
            </td>
        </tr>
    </table>
    <input  class="button" id="submitAudit" onclick="audit('return')" value="??????">
    <input type="submit" class="button" onclick="return confirmSubmit();" value="??????">
<?php else: ?>
    <input  class="button" id="submitAudit" onclick="audit('submit')" value="??????"><?php endif; ?>
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
            alert("???????????????");
            return false;
        }
        res1 = true;
        //if(is_beyond) {
        //    res1 = confirm('????????????????????????????????????20???????????????????????????');
        //}
        var repay_user = '?????????' ;
        if( 1 == $('#repay_user_type_by_a').val()){
            var repay_user = '????????????';
        }
        if( 2 == $('#repay_user_type_by_a').val()){
            var repay_user = '????????????';
        }

        if($("#repay_user").val() == 1){
            if(res1 && money > Number($("#advance_money").val())){
                return confirm('?????????????????????????????????????????????????????????????????????');
            }
        }else if($("#repay_user").val() == 2){
            if(res1 && money > Number($("#agency_money").val())){
                return confirm('?????????????????????????????????????????????????????????????????????');
            }
        }else if($("#repay_user").val() == 3){
            if(res1 && money > Number($("#generation_recharge_money").val())){
                return confirm('???????????????????????????????????????????????????????????????????????????');
            }
        }else if($("#repay_user").val() == 5){
            if(res1 && money > Number($("#indirect_agency_money").val())){
                return confirm('?????????????????????????????????????????????????????????????????????????????????');
            }
        }else{
            if(res1 && money > Number($("#user_money").val())){
                return confirm('?????????????????????????????????????????????????????????????????????????????????');
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
                alert("???????????????");
                return false;
            }
        });
    }
    //sDate1???sDate2???2016-12-18??????
    function  dateDiff(sDate1, sDate2){
        var  aDate,  oDate1,  oDate2,  iDays
        aDate  =  sDate1.split("-")
        oDate1  =  new  Date(aDate[0],aDate[1]-1,aDate[2],0,0,0).getTime();
        aDate  =  sDate2.split("-")
        oDate2  =  new  Date(aDate[0],aDate[1]-1,aDate[2],0,0,0).getTime();
        iDays  =  parseInt(Math.abs(oDate1  -  oDate2)/1000/86400)  //????????????????????????????????????
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
            alert("???????????????");
            return false;
        }
        res1 = true;
        <?php if($role != 'b'): ?>$("#repay_user").removeAttr("disabled");
        if(is_beyond) {
            res1 = confirm('????????????????????????????????????20???????????????????????????');
            if (res1 == false) {
                return;
            }
        }
        <?php else: ?>
        var repay_user = '?????????' ;
        if( 1 == $('#repay_user_type_by_a').val()){
            var repay_user = '????????????';
        }
         if( 2 == $('#repay_user_type_by_a').val()){
            var repay_user = '????????????';
        }
        if(res1 && money > Number($("#user_money").val()) && optype == 'submit'){
            if(!confirm(repay_user+'??????????????????????????????'+repay_user+'???????????????????????????')){
                return false;
            }else{
                return confirm('????????????????????????????????????');
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
            var caution = confirm("????????????");
        } else if (optype == 'return') {
            if ($("#return_type").val() == '0') {
                alert("?????????????????????");
                return;
            }
            if ($("#return_reason").val() == '') {
                alert("?????????????????????");
                return;
            }
            var type = "POST";
            var caution = confirm("????????????");
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
                            alert('??????????????????');
                        }
                        if (optype == 'return') {
                            alert('????????????');
                        }
                    }
                    //location.href = '<?php echo $redirectUrl; ?>';
                    location.href = 'm.php?m=Deal&a=yuqi&ref=1&role='+$("#role").val()+'&<?php echo ($querystring); ?>';
                }
            });
        }

    }

    /**
     * ??????????????????
     * @param dealRepayId
     */
    function part_user_repay(dealRepayId) {
        url =  ROOT+'?m=Deal&a=part_user_repay&deal_repay_id='+dealRepayId+'&role='+$("#role").val();

        window.open(url);
    }

    /**
     * ????????????
     * @param dealRepayId
     */
    function offline_repay(dealRepayId) {
        url =  ROOT+'?m=Deal&a=offline_repay&deal_repay_id='+dealRepayId+'&role='+$("#role").val();

        window.open(url);
    }

    /**
     * ??????????????????
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