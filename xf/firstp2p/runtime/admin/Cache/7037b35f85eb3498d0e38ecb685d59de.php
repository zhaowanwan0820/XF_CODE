<?php if (!defined('THINK_PATH')) exit();?><!-- 加入富文本 -->

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

<!-- 加入富文本 -->
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/style.css" />
<link href="__ROOT__/static/admin/easyui/themes/default/easyui.css" rel="stylesheet" type="text/css" />
<link href="__ROOT__/static/admin/easyui/themes/icon.css" rel="stylesheet" type="text/css" />
<link href="__ROOT__/static/admin/easyui/demo.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="__ROOT__/static/admin/easyui/jquery_1.10.js"></script>
<script type="text/javascript" src="__ROOT__/static/admin/easyui/jquery.form.js"></script>
<script type="text/javascript" src="__ROOT__/static/admin/easyui/jquery.json-2.3.min.js"></script>
<script type="text/javascript" src="__ROOT__/static/admin/easyui/jquery.easyui.min.js"></script>
<script type="text/javascript" src="__ROOT__/static/admin/easyui/ajaxfileupload.js"></script>
<script type="text/javascript" src="__ROOT__/static/admin/Common/js/company.js"></script>
<div class="main">
<div class="main_title"><?php echo L("ADD");?> <a href="<?php echo u("User/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form id="companyForm"  method="post" action="/m.php?m=Public&a=m.php&m=UserCompany&a=saveCompany">
    <input type='hidden' name='image_ids' value='' id ='image_ids'>
    <input type="hidden" name='id' value='<?php echo ($company["id"]); ?>' id="company_id" >
    <input type="hidden" name='user_id' value='<?php echo ($company["user_id"]); ?>'>
    <input type="hidden" id='image_count' value='<?php echo ($image_count); ?>'>
    <div class="blank5"></div>
    <table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1" id="companyTab">
        <tr>
            <td colspan=2 class="topTd"></td>
        </tr>
        <tr>
            <td class="item_title">用户名:</td>
            <td class=""><?php echo ($company["user_name"]); ?>（<?php echo ($company["real_name"]); ?>）Uid：<?php echo ($company["user_id"]); ?></td>
        </tr>
        <tr>
            <td class="item_title">机构名称:</td>
            <td class="item_input"><input size="100" type="text" class="textbox require" name="name" value="<?php echo ($company["name"]); ?>" /></td>
        </tr>
        <tr>
            <td class="item_title">注册地址:</td>
            <td class="item_input"><input size="100" type="text" class="textbox require" name="address" value="<?php echo ($company["address"]); ?>"/></td>
        </tr>
        <tr>
            <td class="item_title">法定代表人:</td>
            <td class="item_input"><input type="text" class="textbox require" name="legal_person" value="<?php echo ($company["legal_person"]); ?>" /></td>
        </tr>
        <tr>
            <td class="item_title">电话:</td>
            <td class="item_input"><input type="text" id = 'tel_mobile' class="textbox require" name="tel" value="<?php echo ($company["tel"]); ?>" /></td>
        </tr>
        <tr>
            <td class="item_title">营业执照号:</td>
            <td class="item_input"><input type="text" class="textbox require" name="license" value="<?php echo ($company["license"]); ?>"  /></td>
        </tr>
        <tr>
            <td class="item_title">项目区域位置:</td>
            <td class="item_input"><input size="100" type="text" class="textbox " name="project_area"  value="<?php echo ($company["project_area"]); ?>"/></td>
        </tr>
        <tr>
            <td class="item_title">住所地:</td>
            <td class="item_input"><input size="100" type="text" class="textbox " name="domicile"  value="<?php echo ($company["domicile"]); ?>"/>&nbsp;&nbsp;&nbsp;<span style='background-color:#b4eeb4;'>企业现在的办公地点</span></td>
        </tr>
        <tr>
            <td class="item_title">企业简介:</td>
            <td class="item_input">
                <script type='text/javascript'> var eid = 'content';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='content' name='description' style=' height:350px;width:750px;' ><?php echo ($company["description"]); ?></textarea> </div>
            <!--
            <textarea  class="textarea" name="description" /><?php echo ($company["description"]); ?></textarea>
            -->
            </td>
        </tr>
        <tr>
            <td class="item_title">最高授信额度:</td>
            <td class="item_input"><input size="100" type="text" class="textbox " name="top_credit" value="<?php echo ($company["top_credit"]); ?>" />元</td>
        </tr>

        <tr>
            <td class="item_title">是否地区重点企业:</td>
            <td class="item_input"><input size="100" type="text" class="textbox " name="is_important_enterprise" value="<?php echo ($company["is_important_enterprise"]); ?>" /></td>
        </tr>
        <tr>
            <td class="item_title">经营情况:</td>
            <td class="item_input"><textarea style=" width:750px;" class="textarea" name="mangage_condition"  /><?php echo ($company["mangage_condition"]); ?></textarea></td>
        </tr>
        <tr>
            <td class="item_title">涉诉情况:</td>
            <td class="item_input"><input size="100" type="text" class="textbox " name="complain_condition"  value="<?php echo ($company["complain_condition"]); ?>"/></td>
        </tr>


        <tr>
            <td class="item_title">征信记录:</td>
            <td class="item_input"><textarea style=" width:750px;" class="textarea" name="trustworthiness"  /><?php echo ($company["trustworthiness"]); ?></textarea></td>
        </tr>
        <tr>
            <td class="item_title">还款来源:</td>
            <td class="item_input"><textarea style=" width:750px;" class="textarea" name="repayment_source"  /><?php echo ($company["repayment_source"]); ?></textarea></td>
        </tr>

        <tr>
            <td class="item_title">政策支持情况:</td>
            <td class="item_input"><textarea style=" width:750px;" class="textarea" name="policy"  /><?php echo ($company["policy"]); ?></textarea></td>
        </tr>

        <tr >
            <td class="item_title">市场分析:</td>
            <td class="item_input"><textarea style=" width:750px;" class="textarea" name="marketplace"  /><?php echo ($company["marketplace"]); ?></textarea></td>
        </tr>
        <!-- add cl2014-1-6 -->
        <tr>
            <td class="item_title">企业法人营业执照:</td>
            <td class="item_input">
                <input type="hidden"  value="<?php echo ($company["licence_image"]); ?>" name='licence_image' id="id_qy" >
                <input id="fileToUpload_qy" type="file" size="5"  name="fileToUpload_qy" onchange="uploadOne(this.id)"><br/>
                <span id="span_qy"><?php echo ($company["licence_image_url"]); ?></span>
                <input type="button" class="textbox" <?php if(empty($company['licence_image_url'])) echo 'style="display:none;"'; ?>   id= "del_qy" onclick="del_Image(this.id)" value="删除" />
            </td>
        </tr>

         <tr>
            <td class="item_title">组织机构代码证:</td>
            <td class="item_input">
                <input type="hidden"  value="<?php echo ($company["organization_iamge"]); ?>" name='organization_iamge' id="id_zz" >
                <input id="fileToUpload_zz" type="file" size="5"  name="fileToUpload_zz" onchange="uploadOne(this.id)"><br/>
                <span id="span_zz"><?php echo ($company["organization_iamge_url"]); ?></span>
                <input type="button" class="textbox" <?php if(empty($company['organization_iamge_url'])) echo 'style="display:none;"'; ?>  id= "del_zz" onclick="del_Image(this.id)" value="删除" />
            </td>
        </tr>

         <tr>
            <td class="item_title">税务登记证:</td>
            <td class="item_input">
                <input type="hidden"  value="<?php echo ($company["taxation_image"]); ?>" name='taxation_image' id="id_sw" >
                <input id="fileToUpload_sw" type="file" size="5"  name="fileToUpload_sw" onchange="uploadOne(this.id)"><br/>
                <span id="span_sw"><?php echo ($company["taxation_image_url"]); ?></span>
                <input type="button" class="textbox" <?php if(empty($company['taxation_image_url'])) echo 'style="display:none;"'; ?>   id= "del_sw" onclick="del_Image(this.id)" value="删除" />
            </td>
        </tr>

         <tr class ="companyTr">
            <td class="item_title">银行许可证:</td>
            <td class="item_input">
                <input type="hidden"  value="<?php echo ($company["bank_iamge"]); ?>" name='bank_iamge' id="id_bk" >
                <input id="fileToUpload_bk" type="file" size="5"  name="fileToUpload_bk" onchange="uploadOne(this.id)"><br/>
                <span id="span_bk"><?php echo ($company["bank_iamge_url"]); ?></span>
                <input type="button" class="textbox" <?php if(empty($company['bank_iamge_url'])) echo 'style="display:none;"'; ?> id= "del_bk" onclick="del_Image(this.id)" value="删除" />
            </td>
        </tr>
        <!-- end -->

        <?php
            if(!empty($company['image_data'])) {
                foreach($company['image_data'] as $key=>$val) {
                    echo '<tr  id="tr_'.$val['id'].'" class ="companyTr">
            <td class="item_title">其他图片资料:</td>
            <td class="item_input">
                  <p ><span style="background-color:#b4eeb4;">其他可以展示借款方实力的图片资料，例如其他国家机关颁发的证书、办公环境实际照片等  &nbsp;&nbsp;&nbsp;&nbsp;</span></p>
                                                图片标题<input type="text" name="image_name['.$val['id'].']"  value="'.$val['name'].'" id="name_'.$val['id'].'" class="textbox">
                <input type="hidden"  value="'.$val['id'].'" id="id_'.$val['id'].'" class="textbox">
                <input id="fileToUpload_'.$val['id'].'" type="file" size="5" name="fileToUpload_'.$val['id'].'" onchange="upload(this.id)"><br/>
                <span id="span_'.$val['id'].'">'.get_attr($val['pic_path'],1).'</span>

                <input type="button" class="textbox"  id= "d_'.$val['id'].'" onclick="del_name(this.id)" value="删除" />
            </td>
        </tr>';
                }
                echo '<tr><td colspan="2" class="item_input"><input type="button" onclick="addImg()" value="新增一条图片资料"></td></tr>';
            }else{
        ?>
        <tr id="tr_0" class ="companyTr">
            <td class="item_title">其他图片资料:</td>
            <td class="item_input">
                <p ><span style='background-color:#b4eeb4;'>其他可以展示借款方实力的图片资料，例如其他国家机关颁发的证书、办公环境实际照片等&nbsp;&nbsp;&nbsp;&nbsp;</span></p>
                   图片标题<input type="text"  value="" id="name_0" name="image_name[]" class="textbox">
                <input type="hidden"  value="" id="id_0" class="textbox">
                <input id="fileToUpload_0" type="file" size="5"  name="fileToUpload_0" onchange="upload(this.id)"><br/>
                <span id="span_0"></span>
                <input type="button" class="textbox" style="display:none;"  id= "d_0" onclick="del_name(this.id)" value="删除" />
            </td>
        </tr>
        <tr>
            <td colspan="2" class="item_input"><input type="button" onclick="addImg()" value="新增一条图片资料"></td>
        </tr>
        <?php }?>

        <tr>
            <td class="item_title"> &nbsp;</td>
            <td><input type='submit' onclick='return check_moile()' value='提交'>
                <input type='reset'  value='重置'>
            </td>
        </tr>
    </table>

</form>
</div>
<script>
//添加图片
var i = $('#image_count').val();
function addImg() {
    i++;
    var str_img = '<tr  id="tr_'+i+'" class ="companyTr">\
                        <td class="item_title">其他图片资料:</td>\
                        <td class="item_input">\
                            <p ><span style="background-color:#b4eeb4;">其他可以展示借款方实力的图片资料，例如其他国家机关颁发的证书、办公环境实际照片等&nbsp;&nbsp;&nbsp;&nbsp;</span></p>\
                                图片标题<input type="text"  value="" name="image_name[]" id="name_'+i+'" class="textbox">\
                            <input type="hidden"  value="" id="id_'+i+'" class="textbox">\
                            <input id="fileToUpload_'+i+'" type="file" size="5" name="fileToUpload_'+i+'" onchange="upload(this.id)"><br/>\
                            <span id="span_'+i+'"></span>\
                            <input type="button" class="textbox" style="display:none;"  id= "d_'+i+'" onclick="del_name(this.id)" value="删除" />\
                        </td>\
                    </tr>';
    $('.companyTr').last().after(str_img);
    $('#image_count').val(i);
}


//easyui 插件
$(document).ready(function() {
    //ajaxForm
    /* $('#companyForm').ajaxForm({
        url: '/m.php?m=UserCompany&a=saveCompany',
        dataType:'json',
        success: function(data) {
               if(data.code == '0000') {
                   alert('保存成功!');
                   window.location.href="/m.php?m=UserCompany&a=companyShow&user_id=<?php echo ($company["user_id"]); ?>#tt";
                   window.location.reload();
               }else{
                   alert(data.message);
               }
        }
    });   */
    var companyId = $('#companyId').val();
    var tips = companyId ? '' : '(<font style="color:red;">填写财务情况前，请先提交上述公司信息</font>)';
    //datagrid
    $('#tt').datagrid({
        title:'财务情况'+tips,
        iconCls:'icon-edit',
        url:'/m.php?m=UserCompany&a=getCompanyFinance&cid='+companyId,
        width:980,
        height:'auto',
        idField:'id',
        sortName: 'id',
        sortOrder: 'asc',
        remoteSort: true,
        columns:[[
                {field:'year',title:'年份',width:90,sortable:true,editor:'text'},
                {field:'master_income',title:'主营业收入',width:120,sortable:true,editor:'text'},
                {field:'gross_profit',title:'毛利润',width:120,sortable:true,editor:'text'},
                {field:'total_assets',title:'总资产',width:120,sortable:true,editor:'text'},
                {field:'net_asset',title:'净资产',width:120,sortable:true,editor:'text'},
                {field:'remarks',title:'备注',width:250,sortable:true,editor:'text'},
                {field:'edit',title:'操作',width:100,align:'center',sortable:false,formatter:function(value,row,index){
                        if(!row.id) {
                            row.id = 99999999;}
                        //按钮点击操作
                        if (row.editing){
                            var s = '<a href="#tt" onclick="saverow('+index+')">保存</a> ';
                            var c = '<a href="#tt" onclick="cancelrow('+index+')">取消</a>';
                            return s+c;
                        } else {
                            var e = '<a href="#tt" onclick="editrow('+index+')">修改</a> ';
                            var d = '<a href="#tt" onclick="deleterow('+index+')">删除</a>';
                            return e+d;
                        }
                }}
        ]],
        onBeforeEdit:function(index,row){
            row.editing = true;
            $('#tt').datagrid('refreshRow', index);
            editcount++;
        },
        onAfterEdit:function(index,row){
            row.editing = false;
            $('#tt').datagrid('refreshRow', index);
            editcount--;
        },
        onCancelEdit:function(index,row){
            row.editing = false;
            $('#tt').datagrid('refreshRow', index);
            editcount--;
        },
        //工具栏
        toolbar:[
            {
                text:'增加',
                iconCls:'icon-add',
                handler:addrow
            }
        ]
    }).datagrid('acceptChanges');
});
var editcount = 0;  //记录行数
//添加一行
function addrow(){
  if (editcount > 0){
      $.messager.alert('警告','当前还有'+editcount+'记录正在编辑，不能增加记录。');
      return;
  }
  $('#tt').datagrid('appendRow',{
        year:'',
        master_income:'',
        gross_profit:'',
        total_assets:'',
        net_asset:'',
        remarks:''
  });
  var lastIndex = $('#tt').datagrid('getRows').length - 1;
  $('#tt').datagrid('selectRow', lastIndex);
  $('#tt').datagrid('beginEdit', lastIndex);
}

//开始编辑
function editrow(index){
    $('#tt').datagrid('beginEdit', index);
}
//取消编辑
function cancelrow(index){
    $('#tt').datagrid('cancelEdit', index);
    $('#tt').datagrid('reload');
}

/**
* 编辑表格 提交数据处理
*/
function saverow(index){
    $('#tt').datagrid('endEdit', index);
    _do_au(index,'1');
}

//更新数据
function _do_au(index,type){
    var type = type || '1';
    var row  = get_row_by_index(index);
    if(row ){
        row.cid = $('#companyId').val();
        if(row.cid) {
            var saveData = $.toJSON(row);
            $.ajax({
                    type: "POST",
                    url: "/m.php?m=UserCompany&a=saveData",
                    data:{data:saveData,r:Math.random()},
                    dataType:"json",
                    success: function(msg){
                        if(msg.code == '0000') {
                           $('#tt').datagrid('reload');
                        }else{
                            alert(msg.message);
                            $('#tt').datagrid('reload');
                        }
                   }
            });
        }else {
            alert('请先添加用户关联信息!');
            $('#tt').datagrid('reload');
        }
    }
}

//获取指定行内的数据
function get_row_by_index(index){
    var obj  = '';
    var rows = $('#tt').datagrid('getRows');
    $.each(rows,function(k,v){
        if($('#tt').datagrid('getRowIndex',v) == index){
            obj = v;
            return false
        }
    })
    return obj;
}

//删除
function deleterow(index) {
    var row  = get_row_by_index(index);
    if(confirm('确认删除?')) {
        if(row.id) {
               $.ajax({
                   type: "POST",
                   url: "/m.php?m=UserCompany&a=delCompanyFinance",
                   data: "id="+row.id,
                   dataType:"json",
                   success: function(msg){
                        if (msg.code == '0000'){
                            $('#tt').datagrid('deleteRow', index);
                            $('#tt').datagrid('reload');
                        }else{
                            alert(msg.message);
                        }
                   }
           });
        }else{
            alert('参数id丢失');
            return false;
        }
    }
}

</script>
<div style='margin:20px 0px 50px 10px;float:left;'>
    <input type='hidden' name='cid' value='<?php echo ($company["id"]); ?>' id ='companyId'>
    <table id="tt" ></table>
</div>
<!-- 插件模块 -->

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