<?php if (!defined('THINK_PATH')) exit();?>
<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/user.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__searchselect/jquery.searchableselect.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__searchselect/searchableselect.css" />
<style>
     .alarm { padding-top:100px; text-align:center; color:#F00; font-size:14px;}
     .right{text-align:right;}
     .left {text-align:left;}
     .center{text-align:center;}
</style>

<?php  ?>
<div class="main center">
    <?php if($errorMsg != ''): ?><p class="alarm"><?php echo ($errorMsg); ?></p>
    <?php else: ?>
    <form name="userForm" action="__APP__" method="get">
        <table class="center" width="100%">
        <tr><td class="right">用户名</td><td class="left"><input type="text" class="textbox" style="width:200px;" name="payerName"  value="<?php echo ($userInfo['username']); ?>" readonly="true"/></td></tr>
        <tr><td class="right">姓名</td><td class="left"><input type="text" class="textbox" style="width:200px;"  value="<?php echo ($userInfo['realname']); ?>" readonly="true"/></td></tr>
        <tr><td class="right">网贷P2P账户可用余额</td><td class="left"><input type="text" class="textbox" style="width:200px;"  value="<?php echo ($userInfo['svBalance']); ?>" readonly="true"/></td></tr>
        <tr><td class="right">网贷P2P账户类型</td><td class="left"><input type="text" class="textbox" style="width:200px;"  value="<?php echo ($userInfo['account']); ?>" readonly="true"/></td></tr>
        <tr><td class="right">划转类型</td><td class="left"><select name="type">
            <option value="1">营销补贴</option>
            <option value="2">补息</option>
            <!-- <option value="3">还代偿款</option> -->
        </select></td></tr>
        <tr><td class="right">金额</td><td class="left"><input type="text" style="width:200px;" id="amount" class="textbox" name="money" value="" /><span style="color:gray;">单位元</span></td></tr>
        <tr><td class="right">转入用户名</td><td class="left"><input type="text" class="textbox" id="receiverName" style="width:200px;"  name="receiverName" value="" /></td></tr>
        <tr><td class="right">备注</td><td class="left"><input type="text" class="textbox" style="width:200px;"  name="memo" value="" /></td></tr>
        <input type="hidden" value="Nongdan" name="m" />
        <input type="hidden" value="doAddPromotions" name="a" />
        <!-- <input type="button" class="button" value="<?php echo L("EXPORT");?>" onclick="export_csv();" /> -->
        <tr><td colspan="2" style="text-align:center;"><input type="button" class="button" value="修改" id="submitBtn" /></td></tr>
    </form><?php endif; ?>
<div class="blank5"></div>
<script>

    $('#submitBtn').click(function(){
        if (confirm(' 确认向用户'+$('#receiverName').val()+'划转'+$('#amount').val()+'元么？')) {
            var data = $('form[name=userForm]').serialize();
            $.post('/m.php?m=Nongdan&a=doAddPromotions', data, function(resp){
                if (resp.errCode != 0)
                {
                    alert(resp.errMsg);
                } else {
                    alert('申请成功');
                    window.location.href=window.location.href;
                }
                console.log(resp);
            }, 'json');
        };
    })
</script>