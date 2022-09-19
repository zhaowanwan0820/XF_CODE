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
<script type="text/javascript" src="__TMPL__Common/js/Chart.min.js"></script>

<div class="main">
    <div class="main_title">活动组列表</div>
    <div class="blank5"></div>

    <a class="button" href="/m.php?m=XinChatPromotion&a=showGroup">新增活动组</a>
    <br/>
    <br/>

    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <th>活动组id</th>
            <th>有效期</th>
            <th>活动</th>
            <th>操作</th>
        </tr>
        <?php if(is_array($groupList)): $i = 0; $__LIST__ = $groupList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$group): ++$i;$mod = ($i % 2 )?><tr class="row">
                <td><?php echo ($group['promotionGroupId']); ?></td>
                <td><?php echo ($group['validityStart']); ?> 至 <?php echo ($group['validityEnd']); ?></td>
                <td>
                    <table frame="void" width="400" border="1" class="dataTable" cellpadding=0 cellspacing=0>
                        <tr>
                            <th width="200">标题</th>
                            <th width="200">封面图</th>
                            <th width="400">链接</th>
                        </tr>

                        <?php if(is_array($group['voPromotions'])): $i = 0; $__LIST__ = $group['voPromotions'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$promotion): ++$i;$mod = ($i % 2 )?><tr class="row">
                                <td><?php echo ($promotion['title']); ?></td>
                                <td>
                                    <?php if(!empty($promotion['imageUrl'])): ?><img src="<?php echo ($promotion['imageUrl']); ?>" width="250" height="100"/><?php endif; ?>
                                </td>
                                <td><?php echo ($promotion['url']); ?></td>
                            </tr><?php endforeach; endif; else: echo "" ;endif; ?>

                    </table>
                </td>
                <td>
                    <a href="/m.php?m=XinChatPromotion&a=showGroup&id=<?php echo ($group['promotionGroupId']); ?>">编辑</a>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
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