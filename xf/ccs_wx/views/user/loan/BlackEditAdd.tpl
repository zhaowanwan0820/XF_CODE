<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>加入黑名单编辑-</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
</head>
<body>
<div class="layui-fluid">
    <div class="layui-row">
        <form action="" method="post" class="layui-form layui-form-pane">
            <div class="layui-form-item layui-form-text">
                <label for="desc" class="layui-form-label">
                    加入黑名单理由
                </label>
                <div class="layui-input-block">
                    <textarea placeholder="请输入内容" id="join_reason" lay-verify="join_reason" name="join_reason" class="layui-textarea"><{$join_reason}></textarea>
                </div>
                <input type="hidden"  name="loan_id" lay-skin="primary" lay-filter="father" value="<{$_GET['loan_id']}>">
                <input type="hidden"  name="deal_type" lay-skin="primary" lay-filter="father" value="<{$_GET['deal_type']}>">
                <input type="hidden"  name="status" lay-skin="primary" lay-filter="father" value="<{$_GET['status']}>">
            </div>
            <div class="layui-form-item">
                <button class="layui-btn" lay-submit="" lay-filter="add">编辑</button>
            </div>
        </form>
    </div>
</div>
<script>
    layui.use(['laydate','form'], function(){
        var laydate = layui.laydate;
        var form = layui.form;
        if(form.join_reason == ''){
            layer.alert("请填写加入黑名单理由");
            return false;
        }
        //监听提交
        form.on('submit(add)', function(data){
            //发异步，把数据提交给php
            $.ajax({
                url: '/user/Loan/EditLoad',
                data: data.field,
                type:"POST",
                success: function (res) {
                    if(res.code == 0){
                        layer.msg("加入黑名单成功", {time:1000,icon:1} ,
                                function(data,item){
                                    parent.layer.close(data);
                                    parent.location.reload();
                                });
                    }else{
                        layer.alert(res.info);
                    }
                }
            })
            return false;
        });
    });
</script>
</body>

</html>