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
    <div class="main_title">合同管理
    </div>
    <div class="blank5"></div>
    <!-- Think 系统列表组件开始 -->
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan="20" class="topTd">&nbsp;</td>
        </tr>
        <tr class="row">
            <th width="20px">序号</th>
            <th>交易所备案编号</th>
            <th><a href="javascript:sortBy('number','1','Contract','index')">合同标题 </a></th>
            <th>借款人</th>
            <th>投资人</th>
            <th>合同编号</th>
            <th>借款人签署状态</th>
            <th>投资人签署状态</th>
            <th>创建时间</th>
            <th>借款人签署时间</th>
            <th>投资人签署时间</th>
            <th width='148px'>操作</th>
        </tr>
        <?php if(is_array($list)): foreach($list as $key=>$item): ?><?php if(is_array($item['tpls'])): foreach($item['tpls'] as $key=>$tpl): ?><?php if($key == 0): ?><tr class="row">
            <td><?php echo ($item["id"]); ?></td>
            <td><?php echo ($item["dealName"]); ?></td>
            <td><a href='javascript:void(0)' onclick='opencontract(<?php echo ($tpl["id"]); ?>,<?php echo ($item["id"]); ?>,<?php echo ($item["status"]); ?>,"<?php echo ($tpl["isTpl"]); ?>");'><?php echo ($tpl["title"]); ?></a>
            <td><?php echo ($item["borrowUserName"]); ?>/<?php echo ($item["borrowMobile"]); ?></td>
            <td><?php echo ($item["real_name"]); ?>/<?php echo ($item["userMobile"]); ?></td>
            <td><?php echo ($tpl["number"]); ?></td>
            <td><?php echo ($item["borrowUserSignStatus"]); ?></td>
            <td><?php echo ($item["userSignStatus"]); ?></td>
            <td><?php echo ($item["createTime"]); ?></td>
            <td><?php echo ($item["borrowUserSignTime"]); ?></td>
            <td><?php echo ($item["userSignTime"]); ?></td>
            <td>
                <?php if($item["status"] == 2 && $tpl["isTpl"] != 1): ?><a href="/m.php?m=DarkMoonContract&a=download&id=<?php echo ($item["id"]); ?>&cId=<?php echo ($tpl["id"]); ?>">下载pdf</a>
                <?php else: ?>
                <a href="/m.php?m=DarkMoonContract&a=download&id=<?php echo ($item["id"]); ?>&tplId=<?php echo ($tpl["id"]); ?>">下载pdf</a><?php endif; ?>
                <?php if($item["dealStatus"] == 4 && $item["status"] == 2): ?><a href="/m.php?m=DarkMoonContract&a=downloadTsa&id=<?php echo ($item["id"]); ?>&cId=<?php echo ($tpl["id"]); ?>">下载TSApdf</a><?php endif; ?>
            </td>
            </tr>
            <?php else: ?>
             <tr class="row">
            <td></td>
            <td></td>
            <td><a href='javascript:void(0)' onclick='opencontract(<?php echo ($tpl["id"]); ?>,<?php echo ($item["id"]); ?>,<?php echo ($item["status"]); ?>,"<?php echo ($tpl["isTpl"]); ?>");'><?php echo ($tpl["title"]); ?></a></td>
            <td></td>
            <td></td>
            <td><?php echo ($tpl["number"]); ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>
            <?php if($item["status"] == 2 && $tpl["isTpl"] != 1): ?><a href="/m.php?m=DarkMoonContract&a=download&id=<?php echo ($item["id"]); ?>&cId=<?php echo ($tpl["id"]); ?>">下载pdf</a>
                <?php else: ?>
                <a href="/m.php?m=DarkMoonContract&a=download&id=<?php echo ($item["id"]); ?>&tplId=<?php echo ($tpl["id"]); ?>">下载pdf</a><?php endif; ?>
             <?php if($item["dealStatus"] == 4 && $item["status"] == 2): ?><a href="/m.php?m=DarkMoonContract&a=downloadTsa&id=<?php echo ($item["id"]); ?>&cId=<?php echo ($tpl["id"]); ?>">下载TSApdf</a><?php endif; ?>
            </td>
            </tr><?php endif; ?><?php endforeach; endif; ?><?php endforeach; endif; ?>
        <tr>
            <td colspan="20" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
    <!-- Think 系统列表组件结束 -->
    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
<script>
function opencontract(tplId,id,status,isTpl){
    if(status == 2 && isTpl != '1'){
        $.weeboxs.open(ROOT+'?m=DarkMoonContract&a=opencontract&id='+id+"&cId="+tplId, {contentType:'ajax',showButton:false,title:'合同内容',width:650,height:500});
    }else{
        $.weeboxs.open(ROOT+'?m=DarkMoonContract&a=opencontract&id='+id+"&tplId="+tplId, {contentType:'ajax',showButton:false,title:'合同内容',width:650,height:500});
    }
    
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