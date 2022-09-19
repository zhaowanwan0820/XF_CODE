<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>拒绝原因</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
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
                    请填写拒绝原因：
                </label>
                <div class="layui-input-block">
                    <textarea placeholder="100字以内" id="remark" lay-verify="remark" name="remark" class="layui-textarea"></textarea>
                </div>
                <input type="hidden"  name="partial_repayment_id" lay-skin="primary" lay-filter="father" value="<{$_GET['partial_repayment_id']}>">
                <input type="hidden"  name="status" lay-skin="primary" lay-filter="father" value="3">
            </div>
            <div class="layui-form-item">
                <button class="layui-btn" lay-submit="" lay-filter="add">确认</button>
                <button class="layui-btn" lay-submit="" lay-filter="del">取消</button>
            </div>
        </form>
    </div>
</div>
<script>
    layui.use(['laydate','form'], function(){
        var laydate = layui.laydate;
        var form = layui.form;
        form.on('submit(del)', function(data){
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
        })
        //监听提交
        form.on('submit(add)', function(data){
            if(!$("#remark").val()){
                layer.alert("请填写拒绝原因");
                return false;
            }
            if(getByteLen($("#remark").val()) > 200){
                layer.alert("输入文字超过限制");
                return false;
            }
            //发异步，把数据提交给php
            $.ajax({
                url: '/user/PartialRepay/RefuseEdit',
                data: data.field,
                type:"POST",
                success: function (res) {
                    if(res.code == 0){
                        layer.alert("拒绝成功",
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
    function getByteLen(val) {
        var len = 0;
        for (var i = 0; i < val.length; i++) {
            var patt = new RegExp(/[^\x00-\xff]/ig);
            var a = val[i];
            if (patt.test(a))
            {
                len += 2;
            }
            else
            {
                len += 1;
            }
        }
        return len;
    }
</script>
</body>

</html>