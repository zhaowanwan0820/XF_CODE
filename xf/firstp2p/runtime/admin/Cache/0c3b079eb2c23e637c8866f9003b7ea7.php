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

<div class="main">
<div class="main_title">费用明细 <a href="<?php echo u("OexchangeBatch/index?pro_id=". $project['id']);?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<div class="button_row">
    <a href="/m.php?m=OexchangeBatch&a=fee&id=<?php echo ($batch["id"]); ?>&export=1';" class="button">导出</a>
</div>
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">项目名称:</td>
        <td class="item_input"><?php echo ($project['name']); ?></td>
    </tr>
    <tr>
        <td class="item_title">交易所备案产品编号:</td>
        <td class="item_input"><?php echo ($project['jys_number']); ?></td>
    </tr>
    <tr>
        <td class="item_title">发行人信息:</td>
        <td class="item_input"> <input type="hidden" id="fx_uid" value="<?php echo ($project['fx_uid']); ?>" />
        <?php echo ($project['fx_uid']); ?> <span id="user_name"></span></td>
    </tr>
    <tr>
        <td class="item_title">期数:</td>
        <td class="item_input">
            <?php echo ($batch['batch_number']); ?> 期
        </td>
    </tr>
    <tr>
        <td class="item_title">批次id:</td>
        <td class="item_input">
            <?php echo ($batch['id']); ?>
        </td>
    </tr>
    <tr>
        <td class="item_title">批次金额:</td>
        <td class="item_input">
            <?php echo ($batch['amount']); ?> 元
        </td>
    </tr>
    <tr>
        <td class="item_title">期限:</td>
        <td class="item_input">
            <?php echo ($project['repay_time']); ?> <?php if(1 == $project['repay_type']): ?>天<?php else: ?>月<?php endif; ?>
        </td>
    </tr>
    <tr>
        <td class="item_title">借款咨询费率:</td>
        <td class="item_input">
            <?php echo ($batch['consult_rate']); ?> % &nbsp;&nbsp;&nbsp;&nbsp; <?php echo ($batch['consult_fee']); ?> 元
        </td>
    </tr>
    <tr>
        <td class="item_title">借款咨询费收取方式:</td>
        <td class="item_input">
            <input type="radio" disabled="disabled" <?php if(1 == $project['consult_type']): ?>checked=""<?php endif; ?> /> 前收
            <input type="radio" disabled="disabled" <?php if(2 == $project['consult_type']): ?>checked=""<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">借款担保费率:</td>
        <td class="item_input">
            <?php echo ($batch['guarantee_rate']); ?> % &nbsp;&nbsp;&nbsp;&nbsp; <?php echo ($batch['guarantee_fee']); ?> 元
        </td>
    </tr>
    <tr>
        <td class="item_title">借款担保费收取方式:</td>
        <td class="item_input">
            <input type="radio" disabled="disabled" <?php if(1 == $project['guarantee_type']): ?>checked=""<?php endif; ?> /> 前收
            <input type="radio" disabled="disabled" <?php if(2 == $project['guarantee_type']): ?>checked=""<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">投资顾问费率:</td>
        <td class="item_input">
            <?php echo ($batch['invest_adviser_rate']); ?> % &nbsp;&nbsp;&nbsp;&nbsp; <?php echo ($batch['invest_adviser_fee']); ?> 元
        </td>
    </tr>
    <tr>
        <td class="item_title">投资顾问费收取方式:</td>
        <td class="item_input">
            <input type="radio" disabled="disabled" <?php if(1 == $project['invest_adviser_type']): ?>checked=""<?php endif; ?> /> 前收
            <input type="radio" disabled="disabled" <?php if(2 == $project['invest_adviser_type']): ?>checked=""<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">发行服务费率:</td>
        <td class="item_input">
            <?php echo ($batch['publish_server_rate']); ?> % &nbsp;&nbsp;&nbsp;&nbsp; <?php echo ($batch['publish_server_fee']); ?> 元
        </td>
    </tr>
    <tr>
        <td class="item_title">发行服务费收取方式:</td>
        <td class="item_input">
            <input type="radio" disabled="disabled" <?php if(1 == $project['publish_server_type']): ?>checked=""<?php endif; ?> /> 前收
            <input type="radio" disabled="disabled" <?php if(2 == $project['publish_server_type']): ?>checked=""<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td class="item_title">挂牌服务费率:</td>
        <td class="item_input">
            <?php echo ($batch['hang_server_rate']); ?> % &nbsp;&nbsp;&nbsp;&nbsp; <?php echo ($batch['hang_server_fee']); ?> 元
        </td>
    </tr>
    <tr>
        <td class="item_title">挂牌服务费收取方式:</td>
        <td class="item_input">
            <input type="radio" disabled="disabled" <?php if(1 == $project['hang_server_type']): ?>checked=""<?php endif; ?> /> 前收
            <input type="radio" disabled="disabled" <?php if(2 == $project['hang_server_type']): ?>checked=""<?php endif; ?> /> 后收
        </td>
    </tr>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>
</form>
</div>
<script type="text/javascript">
$(document).ready(function(){
    var user_id = $("#fx_uid").val();
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
        }
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