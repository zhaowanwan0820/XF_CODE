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

<form action="?m=Deal&a=save_part_user_repay" method="post" class="j-form-post">
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <div class="main_title">回款计划详情 </div>
            <tr>
                <td colspan="7" class="topTd" >&nbsp; </td>
            </tr>

            <tr class="row" >
                <th><input type="checkbox" onclick="part_repay_total_money(-1,0);" name="check_all" id="check_all" <?php if($is_select_checked_all == 1): ?>checked="checked"<?php endif; ?>   <?php if($role == 'b'): ?>disabled<?php endif; ?>></th>
                <th>投资人id</th>
                <th>投资人姓名</th>
                <th>投资人会员名称</th>
                <th>回款总额</th>
                <th>回款本金</th>
                <th>回款利息</th>
                <th>操作时间</th>
            </tr>
        <input type="hidden" id="deal_repay_id" value="<?php echo ($deal_repay_id); ?>">
        <input type="hidden" id="json_str" value="">
            <?php if(is_array($repayInfos)): $id = 0; $__LIST__ = $repayInfos;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$repayInfo): ++$id;$mod = ($id % 2 )?><tr name="repayrow" id="row_<?php echo ($id); ?>" class="row">
                    <td align="center">
                        <?php if($repayInfo["status"] == 0): ?><input onclick="part_repay_total_money(<?php echo ($id); ?>,<?php echo ($repayInfo["loan_user_id"]); ?>);" type="checkbox" id="status_<?php echo ($id); ?>"  name="status_<?php echo ($id); ?>" value="<?php echo ($repayInfo["status"]); ?>" <?php if($repayInfo["status"] == 1 or $is_select_checked_all == 1): ?>checked="checked"<?php endif; ?>>
                        <?php else: ?>
                            <input type="checkbox" name="status_<?php echo ($id); ?>" value="<?php echo ($repayInfo["status"]); ?>" checked="checked" disabled><?php endif; ?>
                    </td>
                    <td> <?php echo ($repayInfo["loan_user_id"]); ?> </td>
                    <td> <?php echo ($repayInfo["real_name"]); ?> </td>
                    <td> <?php echo ($repayInfo["user_name"]); ?> </td>
                    <td> <?php echo ($repayInfo["repay_money"]); ?></td>
                    <td> <?php echo ($repayInfo["principal"]); ?> </td>
                    <td> <?php echo ($repayInfo["interest"]); ?> </td>
                    <td> <?php
                    if ($repayInfo['status'] == 1){
                            echo to_date($repayInfo['update_time']);
                    }else{
                        echo '-';
                    }
                ?> </td>
                    <td style="display:none"><input type="hidden" id="deal_loan_id_<?php echo ($id); ?>" value="<?php echo ($repayInfo["deal_loan_id"]); ?>"></td>
                    <td style="display:none"><input type="hidden" id="loan_user_id_<?php echo ($id); ?>" value="<?php echo ($repayInfo["loan_user_id"]); ?>"></td>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>

    <div style="maigin: 20px;">
        <label id ="money_info">总还款金额：0</label>
    </div>

        <input type="button"  class="button" id="submitAudit" onclick=" save_part_user_repay()" value="保存">

</form>

<script type="text/javascript" charset="utf-8">


    formatNum = function(f, digit) {
        var m = Math.pow(10, digit);
        return Math.round(f * m, 10) / m;
    }


    function same_user_checked(id,userId){

        is_status_checked = $("#status_"+id).is(":checked");

        $("tr[name='repayrow']").each(function () {
            var tdArr = $(this).children();
            status_val = tdArr.eq(0).find('input').val();
            var loan_user_id = tdArr.eq(9).find('input').val();
            if ( status_val !=0){
                // 结束本次循环
                return true;
            }

            if (userId == loan_user_id){

                tdArr.eq(0).find('input').attr("checked",is_status_checked ? true : false);
            }
        })

        return;
    }

    function total_repay_money_b() {
        var money = 0;
        $("tr[name='repayrow']").each(function () {
            var tdArr = $(this).children();
            var repay_mony = 0;

            tdArr.eq(0).find('input').attr("disabled","disabled");
                // 首页要判断状态
                status_val = tdArr.eq(0).find('input').val();

                var is_checked = tdArr.eq(0).find('input').is(":checked");
                if (is_checked){
                    repay_mony = tdArr.eq(4).html();
                }
                money += parseFloat(repay_mony,2);
        });

        $("#money_info").html("总还款金额："+ formatNum(money,2));
        return true;
    }

    <?php if($role == 'b'): ?>total_repay_money_b();<?php endif; ?>
    // 全选单选 总还款金额
    function part_repay_total_money(id,userId) {
        money = 0;

        if (id ==-1) {
            if (!$("#check_all").is(":checked")) {
                part_repay_check_all(0);
                $("#money_info").html("总还款金额：0");
                return;
            }else{
                part_repay_check_all(1);
                part_repay_total_money(0,0);
                return;
            }
        }

        same_user_checked(id,userId);
       
        $("tr[name='repayrow']").each(function () {
            var tdArr = $(this).children();
            var repay_mony = 0;
            var loan_user_id = tdArr.eq(9).find('input').val();
            status_val = tdArr.eq(0).find('input').val();
            if (status_val !=0){
                // 结束本次循环
                return true;
            }
            if (id==0) {
                // var deal_loan_id = tdArr.eq(6).find('input').val();
                //var loan_user_id = tdArr.eq(7).find('input').val();
                repay_mony = tdArr.eq(4).html();
            }else{
                // 首页要判断状态
                var is_checked = tdArr.eq(0).find('input').is(":checked");
                if (is_checked){
                     repay_mony = tdArr.eq(4).html();
                }else if(id!=-10){
                    $("#check_all").attr("checked",false);
                }
            }
            money += parseFloat(repay_mony,2);

        })

        $("#money_info").html("总还款金额："+ formatNum(money,2));
        return;
    }
    // 初始全选
    <?php if($is_select_checked_all == 1): ?>part_repay_total_money(0,0);
    <?php else: ?>
    <?php if($role == 'a'): ?>part_repay_total_money(-10,0);<?php endif; ?><?php endif; ?>

    function save_part_user_repay() {

        if (!confirm("确认操作吗？")){
            return false;
        }
        deal_repay_id = $("#deal_repay_id").val();
        var data_arr ={};
        $("tr[name='repayrow']").each(function(){
            var tdArr = $(this).children();
           var deal_loan_id = tdArr.eq(8).find('input').val();
            //var loan_user_id = tdArr.eq(7).find('input').val();
            status_check = tdArr.eq(0).find('input').is(":checked");
            status_val = tdArr.eq(0).find('input').val();
            if (status_val == 0 &&  status_check){
                data_arr[deal_loan_id] = status_val;}

        })
        json_str = JSON.stringify(data_arr);
        if (json_str == "{}"){
            alert("选择项为空");
            return false;}
        var data = {
            repay_id:deal_repay_id,
            json_str:json_str
        }
        $.ajax({
            url: '?m=Deal&a=save_offline_repay',
            data: data,
            type: 'post',
            dataType: "json",
            success: function(obj) {
                if (obj.errCode == 0){
                    alert('操作成功');
                    location.reload();
                    return true;
                }else{
                    alert(obj.errMsg);
                    return false;
                }

            }
        });
        return true;
    }
    // 全选
    function part_repay_check_all(is_all) {

        $("tr[name='repayrow']").each(function () {
            var tdArr = $(this).children();
            status_val = tdArr.eq(0).find('input').val();
            if (status_val !=0 && status_val !=2){
                // 结束本次循环
                return true;
            }
            if (is_all == 1) {
                tdArr.eq(0).find('input').attr("checked",true);
            }else{
                tdArr.eq(0).find('input').attr("checked",false);
            }
        })

        return;
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