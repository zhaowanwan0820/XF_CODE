<?php if (!defined('THINK_PATH')) exit();?>
<script type="text/javascript">
    function checkform(){
        if($('#typeName').val().length == 0 || $('#typeTag').val().length == 0){
            alert('分类名称和标识不能为空！');
            return false;
        }
        return true;
    }
</script>
<div class="main">

    <form name="search" action="__APP__" method="post">
        <table class="form conf_tab" cellpadding=0 cellspacing=0 rel="3">
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>
            <tr>
                <td class="item_title">分类名称:</td>
                <td class="item_input">
                    <input type='text' name='typeName' id='typeName' value='<?php echo ($type_info["typeName"]); ?>'>
                </td>
            </tr>

            <tr>
                <td class="item_title">分类标识:</td>
                <td class="item_input">
                    <input type='text' name='typeTag' id='typeTag' value='<?php echo ($type_info["typeTag"]); ?>'>
                </td>
            </tr>
            <tr>
                <td class="item_title">借款类型:</td>
                <td class="item_input">
                    <input type='radio' name='contractType' value='0' <?php if($type_info['contractType'] == 0): ?>checked<?php endif; ?>>个人借款
                    <input type='radio' name='contractType' value='1' <?php if($type_info['contractType'] == 1): ?>checked<?php endif; ?>>公司借款
                </td>
            </tr>
            <tr>
                <td class="item_title">使用状态:</td>
                <td class="item_input">
                    <input type='radio' name='useStatus' value='1' <?php if($type_info['useStatus'] == 1): ?>checked<?php endif; ?>>当下使用
                    <input type='radio' name='useStatus' value='0' <?php if($type_info['useStatus'] == 0): ?>checked<?php endif; ?>>历史使用
                </td>
            </tr>
            <tr>
                <td class="item_title">合同版本号:</td>
                <td class="item_input">
                    <input type='text' name='contractVersion' id='contractVersion' value='<?php echo ($type_info["contractVersion"]); ?>'>
                </td>
            </tr>

            <?php if($isCn != true): ?><tr>
                    <td class="item_title">标的类型:</td>
                    <td class="item_input">
                        <select name="dealType" class="require">
                            <?php if(is_array($dealType)): foreach($dealType as $key=>$type_item): ?><option value="<?php echo ($type_item["id"]); ?>" <?php if($type_info["sourceType"] == $type_item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item["name"]); ?></option><?php endforeach; endif; ?>
                        </select>
                    </td>
                </tr><?php endif; ?>
            <tr>
                <td class="item_input" colspan=2 style="text-align:center;">
                    <input type="hidden" value="true" name="update" />
                    <input type="hidden" value="ContractService" name="m" />
                    <input type="hidden" value="contTypeEdit" name="a" />
                    <input type="hidden" value="<?php echo ($type_info["id"]); ?>" name="id" />
                    <input type="submit" class="button" onclick="return checkform();" value="修改" />
                </td>
            </tr>
            <tr>
                <td colspan=2 class="bottomTd">
                </td>
            </tr>
        </table>
    </form>
</div>