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
<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />

<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<div class="main">
    <div class="main_title"> 编辑 <a href="<?php echo u("DarkMoonDeal/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
    <div class="blank5"></div>
    <form name="edit" action="__APP__" id='editform' method="post" enctype="multipart/form-data">
        <div class="blank5"></div>
        <table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">

            <tr>
                <td class="item_title">交易所备案编号:</td>
                <td class="item_input"> <input class="require" type="text" name="jys_record_number" value="<?php echo ($deal["jys_record_number"]); ?>" /></td>
            </tr>

            <tr>
                <td class="item_title">交易所:</td>
                <td class="item_input">
                    <select id="jys_id" name="jys_id" class="require">
                        <option value="">==请选择==</option>
                        <?php if(is_array($jys)): foreach($jys as $jys_k=>$jys_v): ?><option value="<?php echo ($jys_k); ?>" <?php if($deal["jys_id"] == $jys_k): ?>selected="selected"<?php endif; ?>><?php echo ($jys_v); ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="item_title">发行人ID:</td>
                <td class="item_input"> <input class="require" type="text" name="user_id" value="<?php echo ($deal["user_id"]); ?>" /></td>
            </tr>

            <tr>
                <td class="item_title">担保机构:</td>
                <td class="item_input">
                    <select id="agency_id" name="agency_id" class="require">
                    <option value="">==请选择==</option>
                    <?php if(is_array($deal_agency)): foreach($deal_agency as $key=>$agency_item): ?><option value="<?php echo ($agency_item["id"]); ?>" <?php if($deal["agency_id"] == $agency_item['id']): ?>selected="selected"<?php endif; ?>><?php if($agency_item['short_name'] != ''): ?><?php echo ($agency_item["short_name"]); ?>(<?php echo ($agency_item["name"]); ?>)<?php else: ?><?php echo ($agency_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>


            <tr>
                <td class="item_title">咨询机构:</td>
                <td class="item_input">
                    <select id='advisory_id' name="advisory_id" class="require">
                    <option value="">==请选择==</option>
                    <?php if(is_array($deal_advisory)): foreach($deal_advisory as $key=>$advisory_item): ?><option value="<?php echo ($advisory_item["id"]); ?>" <?php if($deal["advisory_id"] == $advisory_item['id']): ?>selected="selected"<?php endif; ?>><?php if($advisory_item['short_name'] != ''): ?><?php echo ($advisory_item["short_name"]); ?><?php else: ?><?php echo ($advisory_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="item_title">借款手续费率:</td>
                <td class="item_input"> <input class="require" type="text" name="loan_fee_rate" value="<?php echo ($deal["loan_fee_rate"]); ?>" /> %</td>
            </tr>
            <tr>
                <td class="item_title">借款手续费收取方式:</td>
                <td class="item_input">
                    <select id="loan_fee_rate_type" class="require" name="loan_fee_rate_type">
                        <option value="" selected="selected">==请选择==</option>
                        <option value="1" <?php if($deal["loan_fee_rate_type"] == 1): ?>selected="selected"<?php endif; ?>>年化前收</option>
                        <option value="2" <?php if($deal["loan_fee_rate_type"] == 2): ?>selected="selected"<?php endif; ?>>年化后收</option>
                        <option value="3" <?php if($deal["loan_fee_rate_type"] == 3): ?>selected="selected"<?php endif; ?>>年化分期收</option>
                        <option value="4" <?php if($deal["loan_fee_rate_type"] == 4): ?>selected="selected"<?php endif; ?>>代销分期</option>
                        <option value="5" <?php if($deal["loan_fee_rate_type"] == 5): ?>selected="selected"<?php endif; ?>>固定比例前收</option>
                        <option value="6" <?php if($deal["loan_fee_rate_type"] == 6): ?>selected="selected"<?php endif; ?>>固定比例后收</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="item_title">借款咨询费率:</td>
                <td class="item_input"> <input class="require" type="text" name="consult_fee_rate" value="<?php echo ($deal["consult_fee_rate"]); ?>" /> %</td>
            </tr>
            <tr>
                <td class="item_title">借款咨询费收取方式:</td>
                <td class="item_input">
                    <select id="consult_fee_rate_type" class="require" name="consult_fee_rate_type">
                        <option value="" selected="selected">==请选择==</option>
                        <option value="1" <?php if($deal["consult_fee_rate_type"] == 1): ?>selected="selected"<?php endif; ?>>前收</option>
                        <option value="2" <?php if($deal["consult_fee_rate_type"] == 2): ?>selected="selected"<?php endif; ?>>后收</option>
                        <option value="3" <?php if($deal["consult_fee_rate_type"] == 3): ?>selected="selected"<?php endif; ?>>分期收取</option>
                    </select>

                </td>
            </tr>

            <tr>
                <td class="item_title">借款担保费率:</td>
                <td class="item_input"> <input class="require" type="text" name="guarantee_fee_rate" value="<?php echo ($deal["guarantee_fee_rate"]); ?>" /> %</td>
            </tr>
            <tr>
                <td class="item_title">借款担保费收取方式:</td>
                <td class="item_input">
                    <select id="guarantee_fee_rate_type" class="require" name="guarantee_fee_rate_type">
                        <option value="" selected="selected">==请选择==</option>
                        <option value="1" <?php if($deal["guarantee_fee_rate_type"] == 1): ?>selected="selected"<?php endif; ?>>前收</option>
                        <option value="2" <?php if($deal["guarantee_fee_rate_type"] == 2): ?>selected="selected"<?php endif; ?>>后收</option>
                        <option value="3" <?php if($deal["guarantee_fee_rate_type"] == 3): ?>selected="selected"<?php endif; ?>>分期收取</option>
                    </select>

                </td>
            </tr>

            <tr>
                <td class="item_title">借款金额:</td>
                <td class="item_input"> <input class="require" type="text" name="borrow_amount" value="<?php echo ($deal["borrow_amount"]); ?>" />元</td>
            </tr>

            <tr>
                <td class="item_title">还款方式:</td>
                <td class="item_input">
                    <select name="loantype" id="repay_mode" onchange="javascript:changeRepay();" >
                        <?php if(is_array($loan_type)): foreach($loan_type as $type_key=>$type_item): ?><option value="<?php echo ($type_key); ?>" <?php if($deal["loantype"] == $type_key): ?>selected="selected"<?php endif; ?>><?php echo ($type_item); ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="item_title">借款期限:</td>
                <td class="item_input">
                    <select id="repay_period" name="repay_time" onchange="javascript:changeRepay();">
                    <?php if(is_array($repay_time)): foreach($repay_time as $time_key=>$time_item): ?><option value="<?php echo ($time_key); ?>" <?php if($deal["repay_time"] == $time_key): ?>selected="selected"<?php endif; ?>><?php echo ($time_item); ?></option><?php endforeach; endif; ?>
                    </select>
                    <input type="text" class="changepmt textbox" SIZE="8" onchange="javascript:changeRepay();" name="repay_time" id="repay_period2" value="<?php echo ($deal["repay_time"]); ?>" /> <span id='tian'>天</span>
                    <select id="repay_period3" name="repay_time" onchange="javascript:changeRepay();" >
                    <?php if(is_array($repay_time_month)): foreach($repay_time_month as $time_key=>$time_item): ?><option value="<?php echo ($time_key); ?>"  <?php if($deal["repay_time"] == $time_key): ?>selected="selected"<?php endif; ?>><?php echo ($time_item); ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="item_title">预期年化收益率:</td>
                <td class="item_input"> <input class="require" type="text" name="rate" value="<?php echo ($deal["rate"]); ?>" /> %</td>
            </tr>

            <tr>
                <td class="item_title">资金用途:</td>
                <td class="item_input"> <input class="require" type="text" name="use_info" value="<?php echo ($deal["use_info"]); ?>" /></td>
            </tr>

            <tr>
                <td class="item_title">锁定期:</td>
                <td class="item_input"> <input class="require" type="text" name="prepay_days_limit" value="<?php echo ($deal["prepay_days_limit"]); ?>" /> 天</td>
            </tr>

            <tr>
                <td class="item_title">最低起投金额:</td>
                <td class="item_input"> <input class="require" type="text" name="min_loan_money" value="<?php echo ($deal["min_loan_money"]); ?>" />元</td>
            </tr>

            <tr>
                <td class="item_title">违约金费率:</td>
                <td class="item_input"> <input class="require" type="text" name="prepay_rate" value="<?php echo ($deal["prepay_rate"]); ?>" /> %</td>
            </tr>

            <tr>
                <td class="item_title">合同类型:</td>
                <td class="item_input">
                    <select id="contract_tpl_type" name="contract_tpl_type" >
                    <?php if(is_array($contract_tpl_type)): foreach($contract_tpl_type as $t_key=>$t_item): ?><option value="<?php echo ($t_item["id"]); ?>" <?php if($deal["contract_tpl_type"] == $t_item["id"] ): ?>selected="selected"<?php endif; ?>><?php echo ($t_item["typeName"]); ?></option><?php endforeach; endif; ?>
                    <option value="" >没有合同</option>
                    </select>
                </td>
            </tr>


        </table>
        <div class="blank5"></div>
        <table class="form" cellpadding=0 cellspacing=0>
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>

        <?php if($deal["deal_status"] == 0): ?><tr>
                <td class="item_title"></td>
                <td class="item_input">

                    <!--隐藏元素-->
                    <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="DarkMoonDeal" />
                    <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="save" />

                    <div id='button_ff'>
                        <!--普通单-->
                        <input type="submit" onclick="return confirmSubmit();" class="button" value="<?php echo L("SAVE");?>" />
                        <input type="reset" class="button" value="<?php echo L("RESET");?>"/>
                    </div>
                </td>

            </tr>
            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr><?php endif; ?>
        </table>
        <input name="id" value="<?php echo ($deal["id"]); ?>" type="hidden">
    </form>

    <script>

    $(document).ready(function(){
        changeRepay();
        <?php if($deal["deal_status"] > 0): ?>$('input').attr("readonly", "readonly");
        $('select').attr("disabled", "disabled");<?php endif; ?>
    });

    function changeRepay(){
        var repay_mode = $('#repay_mode').val();
        changeRepay.is_index_rebate_days = changeRepay.is_index_rebate_days || 0;
        // 自动填写返利天数
        if (repay_mode !=5){
            var repay_period_v = $("#repay_period3").val();
            switch(repay_mode){
                case '1':
                case '6':
                case '7':
                    repay_period_v = $("#repay_period").val();
                    break;
                case '8': //固定日还款特殊算法
                    repay_period_v = $("#repay_period3").val();
                    break;
            }

            $("#rebate_days").val(repay_period_v*30);
        }else if (repay_mode == 5){
            repay_period_v = $("#repay_period2").val();
            $("#rebate_days").val(repay_period_v);
        }
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
            //change_lgl_time();
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
        var deal_status = $("input[name='deal_status']:checked").val();
        if((((loantype == 4 || loantype == 6) && deal_status == 4) || loantype == 8)) {
            $("#first_repay_day_box").show();
        } else {
            $("#first_repay_day_box").hide();
        }
    }
</script>

</div>