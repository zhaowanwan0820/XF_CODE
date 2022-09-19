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
<script type="text/javascript" src="__TMPL__Common/js/user.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<div class="main">
    <div class="main_title"><?php echo ($main_title); ?>客服查询</div>
    <div class="blank5"></div>
    <div class="search_row">
    <form name="search" action="__APP__" method="get">
        <?php echo L("USER_MOBILE");?>：<input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>" style="width:100px;" />
                         会员编号：<input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" style="width:100px;" />
                          证件号码：<input type="text" class="textbox" name="idno" value="<?php echo trim($_REQUEST['idno']);?>" style="width:100px;" />
                           银行卡号：<input type="text" class="textbox" name="bankcard" value="<?php echo trim($_REQUEST['bankcard']);?>" style="width:100px;" />

        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="custServInquir" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>
    </div>

<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="18" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('idnum','<?php echo ($sort); ?>','User','custServInquir')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "idnum"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','custServInquir')" title="按照会员编号<?php echo ($sortType); ?> ">会员编号<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_name','<?php echo ($sort); ?>','User','custServInquir')" title="按照<?php echo L("USER_NAME");?><?php echo ($sortType); ?> "><?php echo L("USER_NAME");?><?php if(($order)  ==  "user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('real_name','<?php echo ($sort); ?>','User','custServInquir')" title="按照姓名<?php echo ($sortType); ?> ">姓名<?php if(($order)  ==  "real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('mobile','<?php echo ($sort); ?>','User','custServInquir')" title="按照<?php echo L("USER_MOBILE");?><?php echo ($sortType); ?> "><?php echo L("USER_MOBILE");?><?php if(($order)  ==  "mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','User','custServInquir')" title="按照注册时间<?php echo ($sortType); ?> ">注册时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('idno','<?php echo ($sort); ?>','User','custServInquir')" title="按照证件号码<?php echo ($sortType); ?> ">证件号码<?php if(($order)  ==  "idno"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('bankcard','<?php echo ($sort); ?>','User','custServInquir')" title="按照银行卡号<?php echo ($sortType); ?> ">银行卡号<?php if(($order)  ==  "bankcard"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('principal','<?php echo ($sort); ?>','User','custServInquir')" title="按照待还本金<?php echo ($sortType); ?> ">待还本金<?php if(($order)  ==  "principal"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('ph_norepay_principal','<?php echo ($sort); ?>','User','custServInquir')" title="按照网贷代还本金<?php echo ($sortType); ?> ">网贷代还本金<?php if(($order)  ==  "ph_norepay_principal"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('wx_norepay_principal','<?php echo ($sort); ?>','User','custServInquir')" title="按照网信代还本金<?php echo ($sortType); ?> ">网信代还本金<?php if(($order)  ==  "wx_norepay_principal"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('duotou_norepay_principal','<?php echo ($sort); ?>','User','custServInquir')" title="按照智多新待还本金<?php echo ($sortType); ?> ">智多新待还本金<?php if(($order)  ==  "duotou_norepay_principal"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','User','custServInquir')" title="按照资产总额<?php echo ($sortType); ?> ">资产总额<?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('visit_time','<?php echo ($sort); ?>','User','custServInquir')" title="按照查询时间<?php echo ($sortType); ?> ">查询时间<?php if(($order)  ==  "visit_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','User','custServInquir')" title="按照状态<?php echo ($sortType); ?> ">状态<?php if(($order)  ==  "is_effect"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user','<?php echo ($sort); ?>','User','custServInquir')" title="按照操作人<?php echo ($sortType); ?> ">操作人<?php if(($order)  ==  "user"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($user_info)): $i = 0; $__LIST__ = $user_info;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($user["id"]); ?>"></td><td>&nbsp;<?php echo ($user["idnum"]); ?></td><td>&nbsp;<?php echo (numTo32($user["id"])); ?></td><td>&nbsp;<?php echo ($user["user_name"]); ?></td><td>&nbsp;<?php echo ($user["real_name"]); ?></td><td>&nbsp;<?php echo ($user["mobile"]); ?></td><td>&nbsp;<?php echo ($user["create_time"]); ?></td><td>&nbsp;<?php echo ($user["idno"]); ?></td><td>&nbsp;<?php echo ($user["bankcard"]); ?></td><td>&nbsp;<?php echo ($user["principal"]); ?></td><td>&nbsp;<?php echo ($user["ph_norepay_principal"]); ?></td><td>&nbsp;<?php echo ($user["wx_norepay_principal"]); ?></td><td>&nbsp;<?php echo ($user["duotou_norepay_principal"]); ?></td><td>&nbsp;<?php echo ($user["money"]); ?></td><td>&nbsp;<?php echo ($user["visit_time"]); ?></td><td>&nbsp;<?php echo ($user["is_effect"]); ?></td><td>&nbsp;<?php echo ($user["user"]); ?></td><td><a href="javascript:custServInquir_detail('<?php echo ($user["id"]); ?>')"><?php echo L("USER_ACCOUNT_DETAIL_SHORT");?></a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="18" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->

<div class="blank5"></div>
<div class="blank5"></div>
    <div>
    <?php if(count($page_flag) == 1 ): ?><table style="width:100%;">
        <tr >
        <td cellspacing="1" style="text-align:center;">没有查到相关记录</td>
        </tr>
        </table><?php endif; ?>
    </div>
</div>

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