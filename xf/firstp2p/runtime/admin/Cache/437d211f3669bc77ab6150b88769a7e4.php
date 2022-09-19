<?php if (!defined('THINK_PATH')) exit();?><div class="main">
<div class="blank5"></div>
<form name="edit" id="_js_form" action="/m.php?m=List&a=nodeedit" method="post" enctype="multipart/form-data">
<table class="form" cellpadding=0 cellspacing=0>
	<input type="hidden" name="nid" value="<?php echo ($node["id"]); ?>" >
	<input type="hidden" name="mid" value="<?php echo ($module["id"]); ?>" >
	<input type="hidden" name="gid" value="<?php echo ($group["id"]); ?>" >
	<input type="hidden" name="navid" value="<?php echo ($nav["id"]); ?>" >
	<?php if($node): ?><tr class="_jsedit">
	        <td class="item_title">模块名：</td>
	        <td class=""><?php echo ($module["name"]); ?></td>
	    </tr>
	    <tr class="_jsedit">
	        <td class="item_title">模块：</td>
	        <td class=""><?php echo ($module["module"]); ?></td>
	    </tr>
	    <tr class="_jsedit">
	        <td class="item_title">节点name</td>
	        <td class="item_input"><input type="text" class="textbox require" name="name" value="<?php echo ($node["name"]); ?>"/></td>
	    </tr>
	    <tr class="_jsedit">
	        <td class="item_title">节点Action</td>
	        <td class="item_input"><input type="text" class="textbox require" name="action" value="<?php echo ($node["action"]); ?>"/></td>
	    </tr>
	    <tr class="_jsedit">
	        <td class="item_title">是否显示:</td>
	        <td class="item_input">
		        <?php if($node['group_id']): ?><lable><input type="radio" name="is_show" value="<?php echo ($group["id"]); ?>" checked="checked" />是</lable>
			    	<lable><input type="radio" name="is_show" value="0" />否</lable>
			    <?php else: ?>
			    	<lable><input type="radio" name="is_show" value="<?php echo ($group["id"]); ?>" />是</lable>
			    	<lable><input type="radio" name="is_show" value="0" checked="checked" />否</lable><?php endif; ?>
	        </td>
   		</tr>
    <?php else: ?>
    	<?php if($group): ?><tr class="_jsedit">
		        <td class="item_title">组name</td>
		        <td class="item_input"><input type="text" class="textbox require" name="name" value="<?php echo ($group["name"]); ?>""/> &nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" id="_jsnewnode">新建节点</a></td>
		    </tr>
	    <?php else: ?>
	    	<tr class="_jsedit">
		        <td class="item_title">标签name</td>
		        <td class="item_input"><input type="text" class="textbox require" name="name" value="<?php echo ($nav["name"]); ?>" />&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" id="_jsnewnode">新建节点</a></td>
		    </tr><?php endif; ?><?php endif; ?>


    <?php if($group && !$node): ?><tr class="_jsnew">
       		<td class="item_title">现有 module</td>
       		<td class="item_input">
       		<select name="module_id" id="_jsmodule">
       		  <option value ="-1">新建module</option>
			  <?php if(is_array($modulelist)): foreach($modulelist as $key=>$list): ?><option id="_js_module_id_<?php echo ($list["id"]); ?>" data-module="<?php echo ($list["module"]); ?>" data-name="<?php echo ($list["name"]); ?>" value ="<?php echo ($list["id"]); ?>"><?php echo ($list["name"]); ?></option><?php endforeach; endif; ?>
			</select>
			&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" id="_jseditnode">编辑节点</a>
       		</td>
   		</tr>
   		<tr class="_jsnew _jsmodule_name" style="display: none;">
   			<td class="item_title">新建module</td>
   			<td class="item_input">
   			module:<input type="text" class="textbox require" id="module_input" name="module" value=""/>&nbsp;&nbsp;
       		name:<input type="text" class="textbox require" id="module_name_input" name="module_name" value=""/>
   			</td>
   		</tr>
  		<tr class="_jsnew">
       		<td class="item_title">节点name</td>
       		<td class="item_input"><input type="text" class="textbox require" name="node_name" value="""/></td>
   		</tr>
  		<tr class="_jsnew">
       		<td class="item_title">节点action</td>
       		<td class="item_input"><input type="text" class="textbox require" name="action" value="""/></td>
   		</tr>
   		<tr class="_jsnew">
	        <td class="item_title">是否显示:</td>
	        <td class="item_input">
		    	<lable><input type="radio" name="is_show" value="<?php echo ($group["id"]); ?>" checked="checked" />是</lable>
		    	<lable><input type="radio" name="is_show" value="0" />否</lable>
				<span class="tip_span">若不显示，请选择现有module</span>
	        </td>
   		</tr>
  	 <?php else: ?>
  		<tr class="_jsnew">
       		<td class="item_title">组name</td>
       		<td class="item_input"><input type="text" class="textbox require" name="group_name" value="" />&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" id="_jseditnode">编辑节点</a></td>
   		</tr><?php endif; ?>
  	<?php if(!$node): ?><tr>
        <td class="item_title">排序</td>
        <td class="item_input">
			<input type="text" class="textbox require" name="sort" value="<?php echo ($sort); ?>" />
        </td>
    </tr><?php endif; ?>
    <tr>
        <td class="item_title">是否删除:</td>
        <td class="item_input">
        	<?php if($is_delete): ?><lable><input type="radio" name="is_delete" value="1" checked="checked" />是</lable>
	            <lable><input type="radio" id="is_delete_radio" name="is_delete" value="0" />否</lable>
	        <?php else: ?>
	        	<lable><input type="radio" name="is_delete" value="1" />是</lable>
	            <lable><input type="radio" id="is_delete_radio" name="is_delete" value="0" checked="checked" />否</lable><?php endif; ?>    
        </td>
    </tr>
    <tr>
        <td class="item_title">是否有效:</td>
        <td class="item_input">
        	<?php if($is_effect): ?><lable><input type="radio" id="is_effect_radio" name="is_effect" value="1" checked="checked" />是</lable>
	            <lable><input type="radio" name="is_effect" value="0" />否</lable>
	        <?php else: ?>
	        	<lable><input type="radio" id="is_effect_radio" name="is_effect" value="1" />是</lable>
	            <lable><input type="radio" name="is_effect" value="0" checked="checked" />否</lable><?php endif; ?>  
        </td>
    </tr>
	<tr>
		<td class="item_title"></td>
		<td class="item_input">
			<input type="button" class="button" id="_js_submit" value="保存" />
			<input type="reset" class="button" value="重置" />
			<?php if($del_node): ?><input type="button" class="button" data-nid="<?php echo ($del_node); ?>" data-type="<?php echo ($type); ?>" id="_js_del_node" value="删除节点" /><?php endif; ?>
		</td>
	</tr>
</table>	 
</form>
</div>
<SCRIPT type="text/javascript">
	<!--
	$(document).ready(function(){
		$("._jsnew").hide();
		//新建节点
		$("#_jsnewnode").click(function(){
			$("._jsedit").hide();
			$("._jsnew").show();
			//设置 radio 为正常状态
			$("#is_delete_radio").get(0).checked = "checked";
			$("#is_effect_radio").get(0).checked = "checked";
		});
		//编辑节点
		$("#_jseditnode").click(function(){
			$("._jsnew").hide();
			$("._jsedit").show();
			//设置 radio 为正常状态
			$("#is_delete_radio").get(0).checked = "checked";
			$("#is_effect_radio").get(0).checked = "checked";
		});
		//编辑节点
		$("#_jsmodule").change(function(){
			var id = $("#_jsmodule").val();
			if(id == -1){
				$("#module_name_input").val("");
				$("#module_input").val("");
			}else{
				//$("._jsmodule_name").hide();
				$("#module_name_input").val($("#_js_module_id_"+id).attr("data-name"));
				$("#module_input").val($("#_js_module_id_"+id).attr("data-module"));
			}
		});
		
		$("#_js_submit").click(function(){
			$.post("/m.php?m=List&a=nodeedit", $("#_js_form").serialize(),function(rs){
				var rs = $.parseJSON(rs);
				if(rs.status){
					alert("操作成功！");
					$.weeboxs.close();
					window.location.reload();
				}else{
					alert("操作失败！"+rs.info);
				}
			});
			return false;
		});
		
		//删除节点
		$("#_js_del_node").click(function(){
			var nid = $(this).attr("data-nid");
			var type = $(this).attr("data-type");
			if(!confirm("是否删除节点?")){
				return false;
			}
			$.post("/m.php?m=List&a=nodedel", {nid:nid,type:type},function(rs){
				var rs = $.parseJSON(rs);
				if(rs.status){
					alert("操作成功！");
					$.weeboxs.close();
					window.location.reload();
				}else{
					alert("操作失败！"+rs.info);
				}
			});
		});
	});
	//-->
</SCRIPT>