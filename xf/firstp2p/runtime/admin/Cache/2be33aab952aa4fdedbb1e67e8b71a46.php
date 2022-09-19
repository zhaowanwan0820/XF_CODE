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
<div class="main_title">第三方余额汇总 <a href="#" onclick="history.go(-1); return false;" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<?php if(is_array($balanceEnum)): foreach($balanceEnum as $balanceType=>$balanceDetail): ?><div class="blank5"></div>
<div class="main_title">
    <?php echo ($typeDesc["$balanceType"]); ?> &nbsp;&nbsp;&nbsp;&nbsp;
    <a class="button" href="/m.php?m=UserThirdBalance&a=syncBalance&userId=<?php echo ($_REQUEST['userId']); ?>&type=<?php echo ($balanceType); ?>">同步<?php echo ($typeDesc["$balanceType"]); ?>余额</a>
</div>
<div class="blank5"></div>

<table class="form" cellpadding=0 cellspacing=0>
<tr>
    <td colspan=3 class="topTd"></td>
</tr>
<tr>
    <th>金额类类型</th>
    <th>资产中心</th>
    <th><?php echo ($typeDesc["$balanceType"]); ?></th>
</tr>
<?php if(is_array($balanceDetail)): foreach($balanceDetail as $key=>$item): ?><tr id="<?php echo ($key); ?>">
    <td class="item_title"><?php echo ($item["desc"]); ?></td>
    <td class="item_input"><?php echo ($balance["$balanceType"]["$key"]); ?></td>
    <td class="item_input"><?php echo ($realBalance["$balanceType"]["$key"]); ?></td>
</tr><?php endforeach; endif; ?>
<tr>
    <td class="item_title">待收本金</td>
    <td class="item_input"><?php echo ($summary["corpus"]); ?></td>
    <td class="item_input"><?php echo ($summary["cg_principal"]); ?></td>
</tr>
<tr>
    <td class="item_title">待收利息</td>
    <td class="item_input"><?php echo ($summary["income"]); ?></td>
    <td class="item_input"><?php echo ($summary["cg_income"]); ?></td>
</tr>
<tr>
    <td colspan=3 class="bottomTd"></td>
</tr>
</table><?php endforeach; endif; ?>

<?php if (!empty($userInfo['userId']) && empty($p2pUserInfo['payment_user_id'])) { ?>
    <a class="button" id="button" href="/m.php?m=User&a=updatePaymentUserId&id=<?php echo $p2pUserInfo['id']; ?>" >更新payment_user_id</a>
<?php } ?>
<?php if (!empty($p2pUserInfo['payment_user_id'])) { ?>
    <a class="button" id="button" href="/m.php?m=User&a=resetPaymentUserId&id=<?php echo $p2pUserInfo['id']; ?>" >删除payment_user_id</a>
<?php } ?>

<?php if (!empty($p2pUserInfo['mobile']) && !empty($p2pUserInfo['payment_user_id']) && $p2pUserInfo['mobile'] != $userInfo['phone']) { ?>
    <a class="button" id="button" href="/m.php?m=User&a=updatePaymentPhone&id=<?php echo $p2pUserInfo['id']; ?>" >更新用户手机号</a>
<?php } ?>

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