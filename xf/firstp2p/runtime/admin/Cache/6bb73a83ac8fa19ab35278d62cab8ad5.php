<?php if (!defined('THINK_PATH')) exit();?>
<div class="main">
    <table class="form conf_tab" cellpadding="0" cellspacing="0" rel="3">
        <tr>
            <td colspan="2" class="topTd">
            </td>
        </tr>
        <tr>
            <td class="item_title" style="width: 100px;">
                <?php echo L("DEAL_NAME");?>:
            </td>
            <td class="item_input">
                <span title="<?php echo ($deal_info["name"]); ?>"><?php echo (msubstr($deal_info["name"],0,20)); ?></span>
            </td>
        </tr>
        <tr>
            <td class="item_title">
                总借款:
            </td>
            <td class="item_input">
                <?php echo (format_price($deal_info["borrow_amount"])); ?>
            </td>
        </tr>
        <tr>
            <td class="item_title">
                筹得款项:
            </td>
            <td class="item_input">
                <?php echo (format_price($deal_info["load_money"])); ?>
            </td>
        </tr>
        <tr>
            <td class="item_title">
                还需款多少:
            </td>
            <td class="item_input">
                <?php echo format_price($deal_info['borrow_amount']-$deal_info['load_money']);?>
            </td>
        </tr>
        <tr>
            <td class="item_title">
                多少人投资:
            </td>
            <td class="item_input">
                <?php echo ($deal_info["buy_count"]); ?>
            </td>
        </tr>
        <?php if($loan_list): ?><tr>
        <td class="item_title">投资列表:</td>
        <td class="item_input">
            <table id="dataTable" class="dataTable" cellpadding="0" cellspacing="0">
                <tr class="row">
                    <th style="width:100px">投资人</th>
                    <th>投资金额</th>
                    <th style="width:130px">投资时间</th>
                    <?php if($deal_info['deal_type'] == 1): ?><th style="width:130px">申请赎回时间</th><?php endif; ?>
                    <?php if($deal_info['parent_id'] != 0): ?><th style="width:130px">推广渠道</th><?php endif; ?>
                </tr>
                <?php if(is_array($loan_list)): foreach($loan_list as $key=>$loan): ?><tr>
                    <td><?php echo get_user_name($loan['user_id']);?></td>
                    <td align="center"><?php echo (format_price($loan["money"])); ?></td>
                    <td align="center"><?php echo to_date($loan['create_time'],"Y-m-d H:i");?></td>
                    <?php if($deal_info['deal_type'] == 1): ?><td align="center"><?php echo ($loan["redemption_time"]); ?></td><?php endif; ?>
                    <?php if($deal_info['parent_id'] != 0): ?><td align="center"><?php echo ($loan["opt_add_channel"]); ?></td><?php endif; ?>
                </tr><?php endforeach; endif; ?>
            </table>
        </td>
    </tr><?php endif; ?>
        <?php if($deal_info['parent_id'] <= 0): ?><tr>
        <td class="item_title">提前放款</td>
        <td class="item_input">
            <font style="color:red;">操作后总借款额将会变为<?php echo (format_price($deal_info["load_money"])); ?>，该标状态会改为满标，该操作不可撤销请慎重！</font><br/>
            <input type="button" class="button" onclick="updatemoney(this)" value="提前放款" />
        </td>
    </tr><?php endif; ?>
        <tr>
            <td colspan="2" class="bottomTd">
            </td>
        </tr>
    </table>
    <script>
    function updatemoney(btn) {
        $(btn).css({ "color": "gray", "background": "#ccc" }).attr("disabled", "disabled");
        if (confirm('确认提交？')) {
            window.location.href='__APP__?m=Deal&a=updatemoney&id='+<?php echo ($deal_info["id"]); ?>
        }
        $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
    }

    function weebox_add_channel(id){
    $.weeboxs.open(ROOT+'?m=DealChannelLog&a=add&deal_load_id='+id, {contentType:'ajax',showButton:false,title:LANG['DEALCHANNEL_INDEX'],width:500,height:200});
}
    </script>
</div>