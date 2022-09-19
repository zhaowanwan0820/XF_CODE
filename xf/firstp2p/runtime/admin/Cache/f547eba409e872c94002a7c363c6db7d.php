<?php if (!defined('THINK_PATH')) exit();?>
<div class="main">

<table id="dataTable" class="dataTable" cellpadding="0" cellspacing="0">
                <tr class="row">
                    <th style="width:100px">投资人</th>
                    <th style="width:100px">顾问</th>
                    <th style="width:130px">投资时间</th>
                </tr>
                <?php if(is_array($list)): foreach($list as $key=>$info): ?><tr>
                    <td><?php echo ($info['real_name']); ?></td>
                    <td><?php echo ($info['adviser_name']); ?></td>
                    <td align="center"><?php echo to_date($info['create_time'],"Y-m-d H:i");?></td>
                </tr><?php endforeach; endif; ?>
</table>

</div>