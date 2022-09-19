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
function msg_type_add(){
    $.weeboxs.open(ROOT+'?m=MsgTemplate&a=msg_type_add', {contentType:'ajax',showButton:false,title:'添加分类',width:420,height:150});
}
function msg_type_edit(id){
    $.weeboxs.open(ROOT+'?m=MsgTemplate&a=msg_type_edit&id='+id, {contentType:'ajax',showButton:false,title:'修改分类',width:420,height:150});
}
function msg_type_copy(id,type_name){
    $.weeboxs.open(ROOT+'?m=MsgTemplate&a=msg_type_copy&id='+id, {contentType:'ajax',showButton:false,title:'将复制“'+type_name+'”下的所有模板',width:450,height:150});
}

function msg_type_del()
{
    idBox = $(".key:checked");
    if(idBox.length == 0)
    {
        alert(LANG['DELETE_EMPTY_WARNING']);
        return;
    }
    idArray = new Array();
    $.each( idBox, function(i, n){
        idArray.push($(n).val());
    });
    id = idArray.join(",");
    if(confirm(LANG['CONFIRM_DELETE'])){
        $.ajax({ 
             url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=msg_type_del&id="+id, 
             dataType: "json",
             success: function(obj){
                 $("#info").html(obj.info);
                 if(obj.status==1){
                     alert('删除成功');
                     location.href=location.href;
                 }
             }
        });
    }
}
</script>
<div class="main">
    <div class="main_title">短信、邮件模板分类</div>
    <div class="blank5"></div>
    <div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="msg_type_add();" />
    <input type="button" class="button" value="批量删除" onclick="msg_type_del();" />
    </div>
    <div class="blank5"></div>

    <!-- Think 系统列表组件开始 -->
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan="14" class="topTd">&nbsp;</td>
        </tr>
        <tr class="row">
            <th width="8"><input type="checkbox" id="check"
                onclick="CheckAll('dataTable')"></th>
            <th width="50px">编号</th>
            <th>分类名称</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
            <td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td>
            <td><?php echo ($item["id"]); ?></td>
            <td><?php echo ($item["type_name"]); ?></td>
            <td><?php echo (to_date($item["create_time"])); ?></td>
            <td>
            <a href="javascript:void(0)" onclick="javascript:msg_type_edit('<?php echo ($item["id"]); ?>')">修改</a> &nbsp;
            <a href="/m.php?m=MsgTemplate&a=index&id=<?php echo ($item["id"]); ?>&from=msg_type">查看模板</a> &nbsp;
            <a href="/m.php?m=MsgTemplate&a=msg_type_del&id=<?php echo ($item["id"]); ?>" onclick="return confirm('确定要删除吗?')">删除</a>
        </td>
        </tr><?php endforeach; endif; ?>
        <tr>
            <td colspan="20" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
    <!-- Think 系统列表组件结束 --> 
    <div class="blank5"></div>
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