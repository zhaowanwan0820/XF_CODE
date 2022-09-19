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

<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>
<div class="main">
<div class="main_title">余额查询 <a href="#" onclick="history.go(-1); return false;" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<table class="form" cellpadding=0 cellspacing=0>
<tr>
    <td colspan=2 class="topTd"></td>
</tr>
<tr>
    <td class="item_title">是否关联支付</td>
    <td class="item_input">
    <?php if ($p2pUserInfo['payment_user_id']) { ?>
    已关联
    <?php } else { ?>
    未关联
    <?php } ?>
    </td>
</tr>
<tr>
    <td class="item_title"><?php if($isEnterprise == 1): ?>支付账户ID<?php else: ?>用户ID<?php endif; ?></td>
    <td class="item_input"><?php echo ($result["userId"]); ?></td>
</tr>
<tr>
    <td class="item_title">可用余额</td>
    <td class="item_input"><?php echo ($result["availableBalance"]["amount"]); ?> (元)</td>
</tr>
<tr>
    <td class="item_title">冻结金额</td>
    <td class="item_input"><?php echo ($result["freezeBalance"]["amount"]); ?> (元)</td>
</tr>
<tr>
    <td colspan=2 class="bottomTd"></td>
</tr>
</table>

<div class="blank5"></div>
<div class="main_title">用户信息</div>
<div class="blank5"></div>

<table class="form" cellpadding=0 cellspacing=0>
<tr>
    <td colspan=2 class="topTd"></td>
</tr>
<tr>
    <td class="item_title">存管账户类型</td>
    <td class="item_input"><?php if (!empty($supervisionUserInfo)) { ?><?php echo ($supervisionUserInfo["bizType"]); ?> <?php } ?></td>
</tr>
<tr>
    <td class="item_title"><?php if($isEnterprise == 1): ?>用户ID<?php else: ?>ID<?php endif; ?></td>
    <td class="item_input"><?php echo ($userInfo["userId"]); ?></td>
</tr>
<tr>
    <td class="item_title"><?php if($isEnterprise == 1): ?>企业全称<?php else: ?>姓名<?php endif; ?></td>
    <td class="item_input"><?php echo ($userInfo["realName"]); ?></td>
</tr>
<tr>
<tr>
    <td class="item_title"><?php if($isEnterprise == 1): ?>企业证件号码<?php else: ?>身份证号<?php endif; ?></td>
    <td class="item_input"><?php echo ($userInfo["cardNo"]); ?></td>
</tr>
<tr>
    <td class="item_title"><?php if($isEnterprise == 1): ?>企业账户负责人手机号码<?php else: ?>手机号<?php endif; ?></td>
    <td class="item_input"><?php echo ($userInfo["phone"]); ?></td>
</tr>
<tr>
    <td class="item_title">是否设置交易密码</td>
    <td class="item_input"><?php echo ($userInfo["isSetTransPWD"]); ?></td>
</tr>
<tr>
    <td colspan=2 class="bottomTd"></td>
</tr>
</table>

<div class="blank5"></div>
<div class="main_title">银行卡绑定</div>
<div class="blank5"></div>

<?php if(is_array($bankInfo["list"])): foreach($bankInfo["list"] as $key=>$item): ?><table class="form" cellpadding=0 cellspacing=0>
<tr>
    <td colspan=2 class="topTd"></td>
</tr>
<tr>
<tr>
    <td class="item_title">卡号</td>
    <td class="item_input"><?php echo ($item["cardNo"]); ?></td>
</tr>
<tr>
    <td class="item_title">卡类型</td>
    <td class="item_input"><?php echo ($item["cardType"]); ?></td>
</tr>
<tr>
    <td class="item_title">银行编码</td>
    <td class="item_input"><?php echo ($item["bankCode"]); ?></td>
</tr>
<tr>
    <td class="item_title">银行名称</td>
    <td class="item_input"><?php echo ($item["bankName"]); ?></td>
</tr>
<tr>
    <td class="item_title">联行号</td>
    <td class="item_input"><?php echo ($item["branchBankId"]); ?></td>
</tr>
<tr>
    <td class="item_title">省</td>
    <td class="item_input"><?php echo ($item["province"]); ?></td>
</tr>
<tr>
    <td class="item_title">市</td>
    <td class="item_input"><?php echo ($item["city"]); ?></td>
</tr>
<tr>
    <td class="item_title">卡状态</td>
    <td class="item_input"><?php echo ($item["status"]); ?> (S 成功 F 失败 I处理中)</td>
</tr>
<tr>
    <td class="item_title">唯一标识</td>
    <td class="item_input"><?php echo ($item["bankCardId"]); ?></td>
</tr>
<tr>
    <td class="item_title">业务类型</td>
    <td class="item_input"><?php echo ($item["bankCardType"]); ?> (0主卡 1充值卡)</td>
</tr>
<tr>
    <td class="item_title">预留手机号</td>
    <td class="item_input"><?php echo ($item["phone"]); ?></td>
</tr>
<tr>
    <td class="item_title">认证类型</td>
    <td class="item_input">
        <?php
            if ($item['certStatus'] == 'EXTERNAL_CERT') {
                echo 'IVR语音认证';
            } else if ($item['certStatus'] == 'FASTPAY_CERT') {
                echo '快捷认证(四要素认证)';
            } else if ($item['certStatus'] == 'TRANSFER_CERT') {
                echo '转账认证';
            } else if ($item['certStatus'] == 'WHITELIST_CERT') {
                echo '白名单';
            } else if ($item['certStatus'] == 'REMIT_CERT') {
                echo '打款认证';
            } else if ($item['certStatus'] == 'ONLY_CARD') {
                echo '卡密认证';
            } else if ($item['certStatus'] == 'AUDIT_CERT') {
                echo '人工认证';
            } else if ($item['certStatus'] == 'NO_CERT') {
                echo '未认证';
            } else if ($item['certStatus'] == 'MER_WHIT_CERT') {
                echo '商户白名单认证';
            } else {
                echo $item['certStatus'];
            }
        ?>
        <input type="hidden" id="_js_bankinfo_uid" value="<?php echo ($userInfo["userId"]); ?>">
        <?php if ($item['bankCardType'] == 0) { ?>
        <button id="_js_sync_cert_status">同步认证类型</button>
        <?php }?>
    </td>
</tr>
<tr>
    <td colspan=2 class="bottomTd"></td>
</tr>
</table><?php endforeach; endif; ?>

<div class="blank5"></div>
<?php echo ($showCreateAccount); ?>
</div>

<?php if (!empty($userInfo['userId']) && empty($p2pUserInfo['payment_user_id'])) { ?>
    <a class="button" id="button" href="/m.php?m=User&a=updatePaymentUserId&id=<?php echo $p2pUserInfo['id']; ?>" >更新payment_user_id</a>
<?php } ?>
<?php if (!empty($p2pUserInfo['payment_user_id'])) { ?>
    <a class="button" id="button" href="/m.php?m=User&a=resetPaymentUserId&id=<?php echo $p2pUserInfo['id']; ?>" >删除payment_user_id</a>
<?php } ?>

<?php if (!empty($p2pUserInfo['mobile']) && !empty($p2pUserInfo['payment_user_id']) && $p2pUserInfo['mobile'] != $userInfo['phone']) { ?>
    <a class="button" id="button" href="/m.php?m=User&a=updatePaymentPhone&id=<?php echo $p2pUserInfo['id']; ?>" >更新用户手机号</a>
<?php } ?>

<?php if (!empty($p2pUserInfo['supervision_user_id']) && $p2pUserInfo['supervision_user_id'] == app_conf('SUPERVISION_ADVANCE_ACCOUNT')) { ?>
    <a class="button" id="button" href="/m.php?m=User&a=syncUserBalance&id=<?php echo $p2pUserInfo['id']; ?>" >同步垫资金额</a>
<?php } ?>



    <a class="button" id="button" href="/m.php?m=User&a=clearUserSupervisionCache&id=<?php echo $p2pUserInfo['id']; ?>" >清理用户存管缓存</a>
<script type="text/javascript">
jQuery(function() {
    //同步认证类型
    $("#_js_sync_cert_status").click(function () {
        var uid = $('#_js_bankinfo_uid').val();
        if (uid == 0) {
            alert("没有用户信息！");
            return false;
        }
        if (uid > 0) {
            $.ajax({
                type: "POST",
                url: ROOT + '?m=User&a=syncCertStatus',
                data: "uid=" + uid,
                dataType: "json",
                success: function (msg) {
                    if (msg.code !== '0000') {
                        return alert(msg.msg);
                    }
                }
            });
        }
        return false;
    });
})
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