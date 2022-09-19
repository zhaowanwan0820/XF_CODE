<?php if (!defined('THINK_PATH')) exit();?><div class="main">
<div class="blank5"></div>
<div class="main_title">编辑</div>
<div class="blank5"></div>
<form name="edit" action="__APP__" method="post" enctype="multipart/form-data">
<div class="blank5"></div>
<table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
	<tr>
		<td colspan=2 class="topTd"></td>
	</tr>
    <tr>
        <td class="item_title">备注:</td>
        <td class="item_input"><textarea class="textarea" name="note" ><?php echo ($vo["note"]); ?></textarea></td>
    </tr>
	<input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>" />
	<input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="Deal" />
	<input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="edit_note" />
	<tr>
	<td class="item_title"></td>
	<td>
		<input type="submit" class="button" value="<?php echo L("EDIT");?>" />
		<input type="reset" class="button" value="<?php echo L("RESET");?>" />
	</td>
	</tr>
	<tr>
		<td colspan=2 class="bottomTd"></td>
	</tr>
</table>
</form>
</div>
<script>
  $(document).ready(function(){	
		
  });
</script>