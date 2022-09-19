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

<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<script type="text/javascript" src="__TMPL__Common/js/jquery.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/ztree/jquery.ztree.all-3.5.min.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/ztree/jquery.ztree.core-3.5.min.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/ztree/demo.css" />
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/ztree/zTreeStyle.css" />
<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<SCRIPT type="text/javascript">
	<!--
	var setting = {
			data: {
				key: {
					title:"t"
				},
				simpleData: {
					enable: true
				}
			},
            view:{
                fontCss:getFont,
                showIcon: false
            },
			callback: {
				onClick: onClick
			}
		};

	var zNodes = <?php echo ($tree); ?>;
    
    function getFont(treeId, node) {
        return node.font ? node.font : {};}

	$(document).ready(function(){
		$.fn.zTree.init($("#treeDemo"), setting, zNodes);
		$("#info").remove();//去掉提示
	});


	
	function onClick(event, treeId, treeNode, clickFlag) {
        $("#selected_id").val(treeNode.id);
        $("#layer").val(treeNode.layer);
        $("#name").val(treeNode.name);
	}

    function action(a) {
        id=$("#selected_id").val();
        layer=$("#layer").val();
        if(a=='addChild'|| layer==0){
            layer = parseInt(layer)+1;
        }

        if((a=='edit'||a=='del')&&id==0){
            $.weeboxs.open('<center><h2>请选择节点</h1></center>',{showButton:false,title:"提示",width:250,height:100});
            return false;
        }

        if(a=='del'){
            del(id);
            return false;
        }

        if(layer == 4){
            $.weeboxs.open('<center><h2>现阶段只支持三级分类</h1></center>',{showButton:false,title:"提示",width:250,height:100});
        }else{
            $.weeboxs.open(ROOT+"?m=DealTypeGrade&a="+a+"&id="+id, {contentType:'ajax',onok:save,showButton:true,title:layer+"级分类",width:650,height:160});
        }
        return false;

    }

    function del(id){

        name = $("#name").val();
        layer = $("#layer").val();
        if(!confirm("确定要删除"+layer+"级分类"+name+"？"))
        {
           return false;
        }

        $.post("/m.php?m=DealTypeGrade&a=del","id="+id,function(rs){
            var rs = $.parseJSON(rs);
            if(rs.status){
                alert("操作成功！");
                window.location.href = "/m.php?m=DealTypeGrade&a=index";
            }else{
                alert(rs.info);
            }
        });
        return false;
    }

    function save(){
        data = $("#_js_form").serialize();
        jsonData = urlParamToJson(data);
        if(jsonData.name == ''){
            alert('名称不能为空');
            return false;
        }
        score = jsonData.score;
        if(score != ''){
            if(!score.match('^(?!^(0+(\.0)?|5\.[1-9])$)[0-5](\.[0-9])?$')){
                alert('产品风险评分数值区间为0<x≤5，支持1位小数，输入可以为空');
                return false;
            }
        }

        $.post("/m.php?m=DealTypeGrade&a=check",data,function(rs){
            rs = $.parseJSON(rs);
            if(rs.code == 9){
                if(confirm(rs.msg)){
                    rs.code = 0;
                }else{
                    return false;
                }
            }

            if(rs.code != 0){
                alert(rs.msg);return false;
            }else{
                $.post("/m.php?m=DealTypeGrade&a=save",data,function(rs){
                    var rs = $.parseJSON(rs);
                    if(rs.status){
                        alert("操作成功！");
                        $.weeboxs.close();
                        window.location.href = "/m.php?m=DealTypeGrade&a=index";
                    }else{
                        alert(rs.info);
                    }
                });
            }
        });

        return false;
    }


    function urlParamToJson(urlParam){
        var string = urlParam.split('&');
        var res = {};
        for(var i = 0;i<string.length;i++){
            var str = string[i].split('=');
            res[str[0]]=str[1];}
        return res;
    }

	//-->
</SCRIPT>
</HEAD>
<BODY>

<div class="main">
    <div class="main_title">贷款产品管理</div>
    <div class="blank5"></div>
    <div class="button_row">
        <input type="hidden" id="selected_id" name="id" value="0">
        <input type="hidden" id="layer" name="layer" value="0">
        <input type="hidden" id="name" name="name" value="">
        <input type="button" class="button" value="添加同级分类" onclick="action('addBrother');" />
        <input type="button" class="button" value="添加子级分类" onclick="action('addChild');" />
        <input type="button" class="button" value="编辑分类" onclick="action('edit');" />
        <input type="button" class="button" value="删除分类" onclick="action('del');" />
    </div>
    <div class="blank5"></div>
    <div class="content_wrap">
        <div class="zTreeDemoBackground left" style="width:700px">
            <ul id="treeDemo" class="ztree" style="float:left"></ul>
            <ul style="float:left;width:300px;height:200px;margin-top:10px; margin-left: 20px">
                <li>
                    已启用<!--<span style = "color :red" >不可</span>删除分类和编辑名称-->：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;无底纹黑色字
                </li>
                <li>
                    启用后被停用<!--<span style = "color :red" >不可</span>删除分类和编辑名称-->：&nbsp;<span style="background-color:#808080;color:#000000;" >浅灰色底纹黑色字</span>
                </li>
                <li>
                    未被启用过：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="background-color:#808080;color:#FFFFFF" >浅灰色底纹白色字</span>
                </li>
               <!-- <li>
                    已启用可删除分类和编辑名称：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="background-color:#000000;color:#FFFFFF" >黑色底纹白色字</span>
                </li>-->
            </ul>
        </div>
    </div>
</div>
</BODY>
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