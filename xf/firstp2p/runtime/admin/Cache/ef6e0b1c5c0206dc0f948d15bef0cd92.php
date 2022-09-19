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

<script type="text/javascript">
    function cont_type_add(){
        $.weeboxs.open(ROOT+'?m=ContractService&a=contTypeAdd', {contentType:'ajax',showButton:false,title:'添加分类',width:420,height:210});
    }
    function cont_type_edit(id){
        $.weeboxs.open(ROOT+'?m=ContractService&a=contTypeEdit&id='+id, {contentType:'ajax',showButton:false,title:'修改分类',width:420,height:210});
    }
    function cont_type_copy(id,typeName){
        $.weeboxs.open(ROOT+'?m=ContractService&a=contTypeCopy&id='+id, {contentType:'ajax',showButton:false,title:'将复制“'+typeName+'”下的所有模板',width:450,height:150});
    }

</script>
<div class="main">
    <div class="main_title">合同模板分类</div>
    <div class="blank5"></div>
    <div class="button_row">
        <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="cont_type_add();" />
    </div>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            &nbsp;分类名称：<input type="text" class="textbox" name="type_name" value="<?php echo trim($_REQUEST['type_name']);?>" />
            &nbsp;借款类型：
            <select name="contract_type">
                <option value="" <?php if(!is_numeric($_REQUEST['contract_type']) and $_REQUEST['contract_type'] == ''): ?>selected="selected"<?php endif; ?>>全部</option>
                <option value="0" <?php if(is_numeric($_REQUEST['contract_type']) and $_REQUEST['contract_type'] == 0): ?>selected="selected"<?php endif; ?>>个人借款</option>
                <option value="1" <?php if($_REQUEST['contract_type'] == 1): ?>selected="selected"<?php endif; ?>>公司借款</option>
            </select>
            &nbsp;状态：
            <select name="use_status">
                <option value="" <?php if(!is_numeric($_REQUEST['use_status']) and $_REQUEST['use_status'] == ''): ?>selected="selected"<?php endif; ?>>全部</option>
                <option value="1" <?php if($_REQUEST['use_status'] == 1): ?>selected="selected"<?php endif; ?>>当下使用</option>
                <option value="0" <?php if(is_numeric($_REQUEST['use_status']) and $_REQUEST['use_status'] == 0): ?>selected="selected"<?php endif; ?>>历史使用</option>
            </select>
            &nbsp;合同分类：
            <select name="source_type">
                <option value="" <?php if(!is_numeric($_REQUEST['source_type']) and $_REQUEST['source_type'] == ''): ?>selected="selected"<?php endif; ?>>全部</option>
                <option value="0" <?php if(is_numeric($_REQUEST['source_type']) and $_REQUEST['source_type'] == 0): ?>selected="selected"<?php endif; ?>>网贷</option>
                <option value="2" <?php if($_REQUEST['source_type'] == 2): ?>selected="selected"<?php endif; ?>>交易所</option>
                <option value="3" <?php if($_REQUEST['source_type'] == 3): ?>selected="selected"<?php endif; ?>>专享</option>
                <option value="5" <?php if($_REQUEST['source_type'] == 5): ?>selected="selected"<?php endif; ?>>小贷</option>
                <option value="200" <?php if($_REQUEST['source_type'] == 200): ?>selected="selected"<?php endif; ?>>线下交易所</option>
                <option value="102" <?php if($_REQUEST['source_type'] == 102): ?>selected="selected"<?php endif; ?>>随心约普惠</option>
                <option value="103" <?php if($_REQUEST['source_type'] == 103): ?>selected="selected"<?php endif; ?>>随心约尊享</option>
            </select>
            <input type="hidden" value="ContractService" name="m" />
            <input type="hidden" value="getCategory" name="a" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        </form>
    </div>
    <div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan="14" class="topTd">&nbsp;</td>
        </tr>
        <tr class="row">
            <th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th>
            <th width="50px">编号</th>
            <th>分类名称</th>
            <th>分类标识</th>
            <th>借款类型</th>
            <th>状态</th>
            <th>当前合同版本</th>
            <?php if($isCn != true): ?><th>分类类型</th><?php endif; ?>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
                <td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td>
                <td><?php echo ($item["id"]); ?></td>
                <td><?php echo ($item["typeName"]); ?></td>
                <td><?php echo ($item["typeTag"]); ?></td>
                <td>
                    <?php if($item["typeTag"] != ""): ?><?php if($item["contractType"] == 0): ?>个人借款<?php else: ?>公司借款<?php endif; ?><?php endif; ?>
                </td>
                <td><?php if($item["useStatus"] == 1): ?>当下使用<?php else: ?>历史使用<?php endif; ?></td>
                <td><?php echo ($item["contractVersion"]); ?></td>
                <?php if($isCn != true): ?><?php if(is_array($dealType)): foreach($dealType as $key=>$type_item): ?><?php if($item["sourceType"] == $type_item['id']): ?><td><?php echo ($type_item["name"]); ?></td><?php endif; ?><?php endforeach; endif; ?><?php endif; ?>
                <td><?php echo (to_date($item["createTime"])); ?></td>
                <td>
                    <a href="javascript:void(0)" onclick="javascript:cont_type_edit('<?php echo ($item["id"]); ?>')">修改</a> &nbsp;
                    <a href="/m.php?m=ContractService&a=showTemplates&typeId=<?php echo ($item["id"]); ?>&contractVersion=<?php echo ($item["contractVersion"]); ?>">查看模板</a> &nbsp;
                    <a href="/m.php?m=ContractService&a=preview&typeId=<?php echo ($item["id"]); ?>&contractVersion=<?php echo ($item["contractVersion"]); ?>">预览合同</a> &nbsp;
                    <a href="javascript:void(0)" onclick="javascript:cont_type_copy(<?php echo ($item["id"]); ?>,'<?php echo ($item["typeName"]); ?>')">复制模板</a> &nbsp;
                    <a href="/m.php?m=ContractService&a=export&id=<?php echo ($item["id"]); ?>&version=<?php echo ($item["contractVersion"]); ?>">导出</a> &nbsp;
                    <a href="/m.php?m=ContractService&a=delCategory&id=<?php echo ($item["id"]); ?>" onclick="return confirm('确定要删除吗?')">删除</a>
                </td>
            </tr><?php endforeach; endif; ?>
        <tr>
            <td colspan="20" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
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