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
<div class="main">
<div class="main_title">优惠码返利明细</div>
<div class="blank5"></div>


<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="10" class="topTd" >&nbsp; </td></tr><tr class="row" ><th><a href="javascript:sortBy('seq','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照序号         <?php echo ($sortType); ?> ">序号         <?php if(($order)  ==  "seq"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('pay_day','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照结算日期         <?php echo ($sortType); ?> ">结算日期         <?php if(($order)  ==  "pay_day"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('rebate_days','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照返利天数         <?php echo ($sortType); ?> ">返利天数         <?php if(($order)  ==  "rebate_days"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('rebate_amount','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照投资人返点金额         <?php echo ($sortType); ?> ">投资人返点金额         <?php if(($order)  ==  "rebate_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('rebate_ratio_amount','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照投资人返点比例金额         <?php echo ($sortType); ?> ">投资人返点比例金额         <?php if(($order)  ==  "rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('referer_rebate_amount','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照推荐人返点金额         <?php echo ($sortType); ?> ">推荐人返点金额         <?php if(($order)  ==  "referer_rebate_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('referer_rebate_ratio_amount','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照推荐人返点比例金额         <?php echo ($sortType); ?> ">推荐人返点比例金额         <?php if(($order)  ==  "referer_rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_rebate_amount','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照机构返点金额         <?php echo ($sortType); ?> ">机构返点金额         <?php if(($order)  ==  "agency_rebate_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('agency_rebate_ratio_amount','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照机构返点比例金额         <?php echo ($sortType); ?> ">机构返点比例金额         <?php if(($order)  ==  "agency_rebate_ratio_amount"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','CouponPayLog','index')" title="按照结算时间         <?php echo ($sortType); ?> ">结算时间         <?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><tr class="row" ><td>&nbsp;<?php echo ($item["seq"]); ?></td><td>&nbsp;<?php echo ($item["pay_day"]); ?></td><td>&nbsp;<?php echo ($item["rebate_days"]); ?></td><td>&nbsp;<?php echo ($item["rebate_amount"]); ?></td><td>&nbsp;<?php echo ($item["rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo ($item["referer_rebate_amount"]); ?></td><td>&nbsp;<?php echo ($item["referer_rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo ($item["agency_rebate_amount"]); ?></td><td>&nbsp;<?php echo ($item["agency_rebate_ratio_amount"]); ?></td><td>&nbsp;<?php echo (to_date($item["create_time"])); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="10" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
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