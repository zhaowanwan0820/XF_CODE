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
    function gotoUrl(str){
        var type_id = <?php echo ($type_id); ?>;
        var url ="m.php?m=ContractService&a=showTemplates&editId="+str+"&contractVersion="+<?php echo ($contract_version); ?>;
        if(type_id){
            url += "&typeId="+type_id;
        }
        location.href=url;
    }

    function copy_tpl(id){
        $.weeboxs.open(ROOT+'?m=MsgTemplate&a=copy_tpl&id='+id, {contentType:'ajax',showButton:false,title:'复制模板',width:550,height:265});
    }

    function add()
    {
        location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=add&typeId=<?php echo ($type_id); ?>&contractVersion=<?php echo ($contract_version); ?>";
    }
</script>
<div class="main">
    <div class="main_title">
        <?php if($typeName != ''): ?><?php echo ($typeName); ?>分类 &gt;<?php endif; ?>
        消息模板管理
        <?php if($typeName != ''): ?>&nbsp;&nbsp;<a href='m.php?m=ContractService&a=getCategory' class="back_list">返回分类列表</a><?php endif; ?>
    </div>
    <div class="blank5"></div>
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    选择模板进行编辑：
    <select name="name" onChange="javascript:gotoUrl(this.value);">
        <option value=""><?php echo L("SELECT_MSG_TPL");?></option>
        <?php if(is_array($tpl_list)): foreach($tpl_list as $key=>$tpl_item): ?><option value="<?php echo ($tpl_item["id"]); ?>" <?php if($tpl['id'] == $tpl_item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($tpl_item["contractTitle"]); ?></option><?php endforeach; endif; ?>
    </select>
    <div class="blank5"></div>
    <form name="edit" action="__APP__" method="post" enctype="multipart/form-data">
        <table class="form" cellpadding=0 cellspacing=0>
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>
            <tr>
                <td class="item_title">合同标题:</td>
                <td class="item_input">
                    <input type='text' id='contractTitle' name='contractTitle' value='<?php echo ($tpl["contractTitle"]); ?>' size='30' />
                </td>
            </tr>
            <tr>
                <td class="item_title">模板标识:</td>
                <td class="item_input">
                标识前缀：
                <select name="tplIdentifierId" id="tplIdentifierId" >
                    <?php if(is_array($tpl_identifier_list)): foreach($tpl_identifier_list as $key=>$tpl_identifier): ?><option value="<?php echo ($tpl_identifier["id"]); ?>" <?php if($tpl['tplIdentifierId'] == $tpl_identifier['id']): ?>selected="selected"<?php endif; ?>><?php echo ($tpl_identifier["name"]); ?>(<?php echo ($tpl_identifier["title"]); ?>)</option><?php endforeach; endif; ?>
                </select>
                标识后缀：
                    <input type='text' id='name' name='name' value='<?php echo ($tpl["name"]); ?>' size='30' >
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("CONTENT");?>:</td>
                <td class="item_input">

                    <table class="two-columns" style='width:100%;'>
                        <tr>
                            <td>
                                <script type='text/javascript'> var eid = 'editor';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='editor' name='content' style='width:700px; height:450px;' ><?php echo ($tpl["content"]); ?></textarea> </div>
                            </td>
                            <td style='font-size:13px;'><?php if($tpl['id'] > 0): ?><?php if(is_array($param)): foreach($param as $key=>$lang): ?><?php echo ($key); ?>: <?php echo ($lang); ?><br /><?php endforeach; endif; ?><?php endif; ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr id="content_tip">
                <td colspan="2">

                </td>
            </tr>
            <tr>
                <td class="item_title"></td>
                <td class="item_input">
                    <!--隐藏元素-->
                    <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="ContractService" />
                    <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="updateTpl" />
                    <input type="hidden" name="id" value="<?php echo ($tpl["id"]); ?>">
                    <input type="hidden" name="type" value="0">
                    <input type="hidden" name='isHtml' value="1">
                    <input type="hidden" name="typeId" value="<?php echo ($type_id); ?>">
                    <input type="hidden" name="version" id="version" value="<?php echo ($contract_version); ?>">
                    <input type="submit" class="button" value="更新"/>
                </td>
            </tr>
            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr>
        </table>
    </form>
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