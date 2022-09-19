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
    <div class="main_title">
        合同模板预览<a href='m.php?m=ContractService&a=getCategory' class="back_list">返回分类列表</a>
    </div>
    <div class="blank5"></div>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            &nbsp;取值标的编号：<input type="text" class="textbox" name="deal_id" id="deal_id" value="<?php echo trim($_REQUEST['deal_id']);?>" />
            &nbsp;虚拟投资人编号：<input type="text" class="textbox" name="user_id"  id ="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" />
            &nbsp;虚拟投资金额：<input type="text" class="textbox" name="money" id="money"  value="<?php echo trim($_REQUEST['money']);?>" />
            <input type="hidden" value="ContractService" name="m" />
            <input type="hidden" value="preview" name="a" />
            <input type="hidden" value="<?php echo trim($_REQUEST['typeId']);?>" name="typeId" />
            <input type="hidden" value="<?php echo trim($_REQUEST['contractVersion']);?>" name="contractVersion" />
            <input type="hidden" value="preview" name="a" />
            <input type="submit" class="button" value="生成预览合同" />
        </form>
    </div>
    <div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan="14" class="topTd">&nbsp;</td>
        </tr>
        <tr class="row">
            <th width="50px">编号</th>
            <th>合同标题</th>
            <th>操作</th>
        </tr>
        <?php if(is_array($tpl_list)): foreach($tpl_list as $key=>$item): ?><tr class="row">
                <td><?php echo ($item["id"]); ?></td>
                <td><?php echo ($item["contractTitle"]); ?></td>
                <td>
                     <a href='javascript:void(0)' onclick="opencontract(<?php echo ($item["id"]); ?>,<?php echo ($_REQUEST['deal_id']); ?>,<?php echo ($_REQUEST['user_id']); ?>,<?php echo ($_REQUEST['money']); ?>);">预览</a>
                    <a href="/m.php?m=ContractService&a=download&id=<?php echo ($item["id"]); ?>&user_id=<?php echo ($_REQUEST['user_id']); ?>&deal_id=<?php echo ($_REQUEST['deal_id']); ?>&money=<?php echo ($_REQUEST['money']); ?>">下载</a> &nbsp;
                </td>
            </tr><?php endforeach; endif; ?>
        <tr>
            <td colspan="20" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
    <div class="blank5"></div>
</div>
<script>
    function opencontract(id,deal_id,user_id,money){
        $.weeboxs.open(ROOT+'?m=ContractService&a=opencontract&id='+id+'&user_id='+user_id+'&money='+money+'&deal_id='+deal_id, {contentType:'ajax',showButton:false,title:'合同内容',width:650,height:500});
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