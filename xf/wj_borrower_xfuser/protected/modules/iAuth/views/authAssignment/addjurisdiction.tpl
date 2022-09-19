<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>权限添加-</title>
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
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">
                    <span class="x-red">*</span>权限分类
                </label>
                <div class="layui-input-inline">
                    <select name="parent">
                        <{foreach $assignTop as $key => $val}>
                                <option value="<{$val['id']}>"  <{if $key == 0}>selected = "selected" <{/if}> ><{$val['name']}></option>
                                <{/foreach}>
                    </select>
                </div>
            </div>
            <div class="layui-form-item layui-form-text">
                <div class="layui-input-block">
                    <textarea id="code_content" name="code_content" placeholder="权限名称,权限规则 | 权限名称2,权限规则2 | 权限名称3,权限规则3 "  autocomplete="on" class="layui-textarea"></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <button class="layui-btn" lay-submit="" lay-filter="add">添加权限</button>
            </div>
        </form>
    </div>
</div>
<script>
    layui.use(['laydate', 'form'], function () {
        var laydate = layui.laydate;
        var form = layui.form;
        //监听提交
        form.on('submit(add)', function (data) {
            //发异步，把数据提交给php
            $.ajax({
                url: '/iauth/AuthAssignment/AddJurisdiction',
                data: data.field,
                type: "POST",
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert("添加成功",
                                function (data, item) {
                                    location.reload();
                                });
                    } else {
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