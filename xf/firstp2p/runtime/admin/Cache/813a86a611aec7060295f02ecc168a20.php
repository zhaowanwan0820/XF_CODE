<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo conf("APP_NAME");?><?php echo l("ADMIN_PLATFORM");?></title>
<script type="text/javascript" src="__ROOT__/static/admin/lang.js"></script>
<script type="text/javascript">
    var version = '<?php echo app_conf("DB_VERSION");?>';
</script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/style.css" />
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/main.css" />
<script type="text/javascript" src="__TMPL__Common/js/jquery.js"></script>
</head>

<body>
    <div class="main">
    <div class="main_title"><?php echo conf("APP_NAME");?><?php echo l("ADMIN_PLATFORM");?> <?php echo L("HOME");?>    </div>
    <div class="blank5"></div>
    <table class="form" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan=2 class="topTd"></td>
        </tr>
        <tr>
            <td class="item_title" style="width:200px;">
                <?php echo L("CURRENT_VERSION");?>
            </td>
            <td class="item_input">
                <?php echo L("APP_VERSION");?>:<?php echo conf("DB_VERSION");?>
            </td>
        </tr>

        <tr>
            <td class="item_title" style="width:200px;">
                <?php echo L("TIME_INFORMATION");?>
            </td>
            <td class="item_input">
                <?php echo L("CURRENT_TIME");?>：<?php echo to_date(get_gmtime()); ?>
            </td>
        </tr>
        <tr>
            <!--
            <td class="item_title" style="width:200px;">
                <?php echo L("TOTAL_REG_USER_COUNT");?>
            </td>
            <td class="item_input">
                <?php echo sprintf(L("TOTAL_USER_COUNT_FORMAT"),$total_user,$total_verify_user); ?>
            </td>
            -->
        </tr>
        <tr>
            <td class="item_title" style="width:200px;">
                待审核用户
            </td>
            <td class="item_input">
                <a href="<?php echo u("User/wait");?>" <?php if($wait_deal_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($wait_user_count); ?>个待审核用户</a>
            </td>
        </tr>
        <!--add待审核身份证更换用户  -->
        <tr>
            <td class="item_title" style="width:200px;">
                待审核更换银行卡用户
            </td>
            <td class="item_input">
                <a href="<?php echo u("User/AuditBankInfo",array('status'=>1));?>" <?php if($updateBank_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($updateBank_count); ?>个待审核用户</a>
            </td>
        </tr>

        <!--  -->
        <tr>
            <td class="item_title" style="width:200px;">
                待审核的借款
            </td>
            <td class="item_input">
                <a href="<?php echo u("Deal/publish");?>" <?php if($wait_deal_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($wait_deal_count); ?>待审核的借款</a>
            </td>
        </tr>

        <tr>
            <td class="item_title" style="width:200px;">
                等待客户确认的借款
            </td>
            <td class="item_input">
                <a href="<?php echo u("Deal/wait_confirm");?>" <?php if($wait_confirm_deal_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($wait_confirm_deal_count); ?><?php echo L("DEAL_STATUS_0");?>的借款</a>
            </td>
        </tr>

        <tr>
            <td class="item_title" style="width:200px;">
                                       客户已确认的借款
            </td>
            <td class="item_input">
                <a href="<?php echo u("Deal/confirm");?>" <?php if($confirm_deal_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($confirm_deal_count); ?>已确认的借款</a>
            </td>
        </tr>

        <tr>
            <td class="item_title" style="width:200px;">
                满标的借款
            </td>
            <td class="item_input">
                <a href="<?php echo u("Deal/index",array("deal_status"=>2));?>" <?php if($suc_deal_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($suc_deal_count); ?>满标的借款</a>
            </td>
        </tr>

        <tr>
            <td class="item_title" style="width:200px;">
                三日内需还款的借款
            </td>
            <td class="item_input">
                <a href="<?php echo u("Deal/three");?>" <?php if($threeday_repay_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($threeday_repay_count); ?>需还款的借款</a>
            </td>
        </tr>
        <tr>
            <td class="item_title" style="width:200px;">
                24小时内即将流标的订单
            </td>
            <td class="item_input">
                <a href="<?php echo u("Deal/twenty_four");?>" <?php if($twenty_four_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($twenty_four_count); ?>个即将流标</a>
            </td>
        </tr>

        <tr>
            <td class="item_title" style="width:200px;">
                逾期未还的借款
            </td>
            <td class="item_input">
                <a href="<?php echo u("Deal/yuqi");?>" <?php if($yq_repay_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($yq_repay_count); ?>逾期的借款</a>
            </td>
        </tr>
        <tr>
            <td class="item_title" style="width:200px;">
                待审核提现申请
            </td>
            <td class="item_input">
                <a href="<?php echo u("UserCarry/index",array("status"=>0));?>" <?php if($carry_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($carry_count_0); ?>新待审核提现申请</a>
            </td>
        </tr>
        <tr>
            <td class="item_title" style="width:200px;">
                待处理提现申请
            </td>
            <td class="item_input">
                <a href="<?php echo u("UserCarry/index",array("status"=>1));?>" <?php if($carry_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($carry_count_1); ?>新待处理提现申请</a>
            </td>
        </tr>
        <tr>
            <td class="item_title" style="width:200px;">
                充值申请
            </td>
            <td class="item_input">
                <a href="<?php echo u("MoneyApply/index");?>" <?php if($moneyapply_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($moneyapply_count); ?>待审核充值申请</a>
            </td>
        </tr>
        <tr>
            <td class="item_title" style="width:200px;">
                待处理举报
            </td>
            <td class="item_input">
                <a href="<?php echo u("Reportguy/index",array("status"=>0));?>" <?php if($reportguy_count > 0): ?>style="color:#f60;"<?php endif; ?>><?php echo ($reportguy_count); ?>待处理举报</a>
            </td>
        </tr>
        <tr>
            <!--
            <td class="item_title" style="width:200px;">
                订单统计
            </td>
            <td class="item_input">
                充值成交<?php echo ($incharge_order_buy_count); ?>

                <?php if($reminder['incharge_count'] > 0): ?>(<a href="<?php echo u("DealOrder/incharge_index");?>" style="color:#f60;"><?php echo ($reminder["incharge_count"]); ?>新充值单</a>)<?php endif; ?>
            </td>
            -->
        </tr>


        <tr>
            <!--
            <td class="item_title" style="width:200px;">
                资金
            </td>
            <td class="item_input">
                总收款<?php echo (format_price($income_amount)); ?>，退款<?php echo (format_price($refund_amount)); ?><br />
                机构余额：<?php echo (format_price($platformMoney)); ?><br />
                用户余额（正）：<?php echo (format_price($userMoneyPlus)); ?><br />
                用户余额（负）：<?php echo (format_price($userMoneyMinus)); ?><br />
            </td>
            -->
        </tr>

        <tr>
            <td colspan=2 class="bottomTd"></td>
        </tr>
    </table>
    </div>
</body>
</html>