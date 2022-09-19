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


<div class="main">
<div class="main_title">客服查询详情页<a href="javascript:void(0);" onclick="back_list()" class="back_list" ><?php echo L("BACK_LIST");?></a></div>

<div class="blank5"></div>

<div class="blank5"></div>
<table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">会员编号:</td>
        <td class="item_input"><input type="text" class="textbox" name="id_num" value="<?php echo ($vo["id"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_NAME");?>:</td>
        <td class="item_input"><input class="textbox" name="user_name" value="<?php echo ($vo["user_name"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">会员邮箱:</td>
        <td class="item_input"><input type="text" class="textbox" name="email" value="<?php echo ($vo["email"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_MOBILE");?>:</td>
        <td class="item_input">
        <input type="text" class="textbox" name="mobile" value="<?php echo ($vo["mobile"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_GROUP");?>:</td>
        <td class="item_input">
        <input type="text" class="textbox" name="group" value="<?php echo ($vo["user_net"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">本人邀请码:</td>
        <td class="item_input"><input type="text" class="textbox" name="user_code" value="<?php echo ($vo["user_code"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">投资返利系数:</td>
        <td class="item_input">
        <input type="text" class="textbox" name="ratio" value="<?php echo ($vo["user_ratio"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">推荐人邀请码:</td>
        <td class="item_input"><input type="text" class="textbox" name="reco_user_code" value="<?php echo ($vo["reco_user_code"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">推荐人姓名:</td>
        <td class="item_input">
        <input type="text" class="textbox" name="reco_user_name" value="<?php echo ($vo["reco_user_name"]); ?>" readonly="readonly"/>
        </td>
    </tr>
    
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>身份信息</b></td>
    </tr>
    <tr>
        <td class="item_title">姓名:</td>
        <td class="item_input">
        <input type="text" value="<?php echo ($vo["real_name"]); ?>" class="textbox" name="real_name" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">身份证件类型:</td>
        <td class="item_input">
            <input type="text" value="<?php echo ($vo["idno_type"]); ?>" class="textbox" name="idno_type" readonly="readonly"/></td>
    </tr>
    <tr>
        <td class="item_title">证件号码:</td>
        <td class="item_input">
            <input type="text" value="<?php echo ($vo["idno"]); ?>" class="textbox" name="idno" readonly="readonly"/></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_BIRTHDAY");?>:</td>
        <td class="item_input">
            <input type="text" value="<?php echo ($vo["birthday"]); ?>" class="textbox" name="birthday" readonly="readonly"/></td>
    </tr>
    <tr>
        <td class="item_title">性别:</td>
        <td class="item_input">
            <input type="text" value="<?php echo ($vo["sex"]); ?>" class="textbox" name="sex" readonly="readonly"/></td>
    </tr>

    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>银行信息</b>
        </td>
    </tr>
    <tr>
        <td class="item_title">开户名:</td>
        <td class="item_input">
        <input type="text" value="<?php echo ($user_bankc_info["card_name"]); ?>" class="textbox" name="card_name" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">账户类型:</td>
        <td class="item_input">
        <input type="text" value="借记卡" class="textbox" name="account_type" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">所属银行:</td>
        <td class="item_input">
        <input type="text" value="<?php echo ($user_bankc_info["name"]); ?>" class="textbox" name="bankzone" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td class="item_title">卡号:</td>
        <td class="item_input">
        <input type="text" value="<?php echo ($user_bankc_info["bankcard"]); ?>" class="textbox" name="bankcard" readonly="readonly"/>
        </td>
    </tr>


    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>收货地址</b>
        </td>
    </tr>
    <?php if(count($info_user) == 0 ): ?><tr>
            <td colspan="2" style="text-align:center;">没有相关的记录</td>
        </tr>
        <?php else: ?>
        <?php if(is_array($info_user)): foreach($info_user as $keys=>$info): ?><tr>
                <td class="item_title">收货人名字<?php echo ($keys+1); ?>:</td>
                <td class="item_input">
                    <input type="text" value="<?php echo ($info["consignee"]); ?>" class="textbox" name="bankzone" readonly="readonly"/>
                </td>
            </tr>

            <tr>
                <td class="item_title">手机号<?php echo ($keys+1); ?>:</td>
                <td class="item_input">
                    <input type="text" value="<?php echo ($info["mobile"]); ?>" class="textbox" name="bankzone" readonly="readonly"/>
                </td>
            </tr>

            <tr>
                <td class="item_title">所在地区<?php echo ($keys+1); ?>:</td>
                <td class="item_input">
                    <?php if(is_array($info["area"])): foreach($info["area"] as $key=>$area): ?><input type="text" value="<?php echo ($area); ?>" class="textbox" name="area"
                                   readonly="readonly" /> &nbsp;&nbsp;&nbsp;&nbsp;<?php endforeach; endif; ?>
                </td>
            </tr>

            <tr>
                <td class="item_title">详细地址<?php echo ($keys+1); ?>:</td>
                <td class="item_input">
                   <textarea rows="5" cols="40" readonly="readonly" resize="none"><?php echo ($info["address"]); ?></textarea>
                </td>
            </tr>

            <tr>
                <td class="item_title">邮政编码<?php echo ($keys+1); ?>:</td>
                <td class="item_input">
                    <input type="text" value="<?php echo ($info["postcode"]); ?>" class="textbox" name="bankzone" readonly="readonly"/>
                </td>
            </tr><?php endforeach; endif; ?><?php endif; ?>


    <tr>
        <td colspan="2" class="item_title" style="text-align:center;">
        <div style=" float: left; clear: both; width: 100px; content: ''; visibility: hidden;" >1 </div >
        <b>资金记录</b><input type="button" class="button" value="查看更多" onclick="more_log()" style="float:right;height:28px;"/>
        </td>
    </tr>
    <?php if(count($money_log_pre) == 0 ): ?><tr>
        <td colspan="2" style="text-align:center;">没有相关的记录</td>
        </tr>
    <?php else: ?>
        <?php if(is_array($money_log_pre)): foreach($money_log_pre as $key=>$money_log_vo): ?><tr class="money_log" style="display">
        <td  colspan="3" style="text-align:center;">
        
        <table style="width:100%;">
        <tr>
        <td style="border:none;text-align:left;" ><?php echo ($money_log_vo["log_info"]); ?></td><td style="border:none;text-align:right;"><?php echo ($money_log_vo["money"]); ?>元</td>
        </tr>
        
         <tr>
        <td  style="border:none;text-align:left;"><?php echo ($money_log_vo["note"]); ?></td><td  style="border:none;text-align:right;"><?php echo ($money_log_vo["log_time"]); ?></td>
        </tr>
        </table>
        
        </td>
        </tr><?php endforeach; endif; ?><?php endif; ?>
    <?php if(count($more_log) == 0 && count($money_log_pre) != 0 ): ?><tr class="more_log_show" style="display:none">
        <td colspan="2" style="text-align:center;">没有更多记录</td>
        </tr>
    <?php else: ?>
        <?php if(is_array($more_log)): foreach($more_log as $key=>$more_log_vo): ?><tr class="more_log_show" style="display:none">
        <td  colspan="3" style="text-align:center;">
        
        <table style="width:100%;">
        <tr>
        <td style="border:none;text-align:left;" ><?php echo ($more_log_vo["log_info"]); ?></td><td style="border:none;text-align:right;"><?php echo ($more_log_vo["money"]); ?>元</td>
        </tr>
        
         <tr>
        <td  style="border:none;text-align:left;"><?php echo ($more_log_vo["note"]); ?></td><td  style="border:none;text-align:right;"><?php echo ($more_log_vo["log_time"]); ?></td>
        </tr>
        </table>
        
        </td>
        </tr><?php endforeach; endif; ?><?php endif; ?>
    <!---提现记录-->
    <tr>
        <td colspan="3" class="item_title" style="text-align:center;">
        <div style=" float: left; clear: both; width: 100px; content: ''; visibility: hidden;" >1 </div >
        <b>提现失败记录</b><input type="button" class="button" value="查看更多" onclick="more_withdraw()" style="float:right;height:28px;"/>
        </td>
    </tr>
    <?php if(count($withdrawFailed) == 0 ): ?><tr>
        <td colspan="3" style="text-align:center;">没有相关的记录</td>
        </tr>
    <?php else: ?>
        <tr><th>提现金额</th><th>失败原因</th><th >发起提现时间</th></tr>
        <?php if(is_array($withdrawFailed)): foreach($withdrawFailed as $key=>$withdrawInfo): ?><tr>
        <td><?php echo ($withdrawInfo["money"]); ?>元</td><td><?php echo ($withdrawInfo["withdraw_msg"]); ?></td><td><?php echo ($withdrawInfo["create_time"]); ?></td>
        </tr><?php endforeach; endif; ?><?php endif; ?>
    <?php if(count($moreWithdraw) == 0): ?><tr class="more_" style="display:none;">
        <td colspan="3" style="text-align:center;">没有更多记录</td>
        </tr>
    <?php else: ?>
        <?php if(is_array($moreWithdraw)): foreach($moreWithdraw as $key=>$withdrawInfo): ?><tr class="more_withdraw border:1px;" style="display:none">
        <td ><?php echo ($withdrawInfo["money"]); ?>元</td><td><?php echo ($withdrawInfo["withdraw_msg"]); ?></td><td><?php echo ($withdrawInfo["create_time"]); ?></td>
        </tr><?php endforeach; endif; ?><?php endif; ?>

</table>

<script type="text/javascript">
//添加跳转
function more_withdraw()
{
    $(".more_withdraw").show();
}

//添加跳转
function more_log()
{
    $(".more_log_show").show();
}
function back_list()
{
    location.href = ROOT + '?m=User&a=custServInquir&id='+"<?php echo ($vo["id"]); ?>";
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