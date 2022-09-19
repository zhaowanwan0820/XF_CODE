<?php if (!defined('THINK_PATH')) exit();?>
<script type="text/javascript">

</script>
<div class="main">
<?php if(is_array($authList)): foreach($authList as $val=>$item): ?><div class="main_title"><?php echo ($item["accountPurposeName"]); ?></div>
<div class="blank5"></div>
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=4 class="topTd"></td>
    </tr>
    <tr>
        <td>类型</td>
        <td>期限</td>
        <td>金额</td>
        <td>状态</td>
    </tr>
    <?php if(is_array($item["authList"])): foreach($item["authList"] as $key=>$value): ?><tr>
        <td class="item_input"><?php echo ($value["grantName"]); ?></td>
        <td class="item_input"><?php echo ($value["grantTimeFormat"]); ?></td>
        <td class="item_input"><?php echo ($value["grantAmountFormat"]); ?></td>
        <td class="item_input"><?php if($value["isOpen"] == 1): ?>已开通<?php else: ?>未开通<?php endif; ?></td>
    </tr><?php endforeach; endif; ?>

    <tr>
        <td colspan=4 class="bottomTd"></td>
    </tr>
</table>
<div class="blank5"></div>
<div class="blank5"></div>
<div class="blank5"></div><?php endforeach; endif; ?>
</div>