<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>权限编辑-</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi"/>
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
                    <span class="x-red">*</span>权限名称
                </label>

                <div class="layui-input-inline">
                    <input type="text" id="name" name="username" required="" lay-verify="required"
                           autocomplete="off" class="layui-input" value="<{$assignment['name']}>">
                </div>
            </div>
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">
                    <span class="x-red">*</span>权限规则
                </label>

                <div class="layui-input-inline">
                    <input type="hidden" name="itemId" value="<{$assignment['id']}>">
                    <input type="text" id="name" name="code" required="" lay-verify="required"
                           autocomplete="off" class="layui-input" value="<{$assignment['code']}>">
                </div>
            </div>
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">
                    <span class="x-red">*</span>所属权限
                </label>

                <div class="layui-input-inline">
                    <div class="layui-inline layui-show-xs-block">
                        <select name="parent">
                            <{foreach $assignTop as $key => $val}>
                                <option value="<{$val['id']}>"  <{if $val['id'] == $data['id']}>selected = "selected" <{/if}>><{$val['name']}></option>
                                <{/foreach}>
                        </select>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <button class="layui-btn" lay-submit="" lay-filter="add">编辑</button>
            </div>
        </form>
    </div>
</div>
<script>
    layui.use(['form', 'layer'], function () {
        $ = layui.jquery;
        var form = layui.form
                , layer = layui.layer;
        //监听提交
        form.on('submit(add)', function (data) {
            console.log(data);
            //发异步，把数据提交给php
            $.ajax({
                url: '/iauth/AuthAssignment/RoleEdit',
                data: data.field,
                type: "POST",
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert("编辑成功",
                                function (data, item) {
                                    location.reload();
                                });
                    } else {
                        layer.msg(res.info);
                    }
                }
            })
            return false;
        });
        form.on('checkbox(father)', function (data) {

            if (data.elem.checked) {
                $(data.elem).parent().siblings('td').find('input').prop("checked", true);
                form.render();
            } else {
                $(data.elem).parent().siblings('td').find('input').prop("checked", false);
                form.render();
            }
        });
    });
</script>
</body>

</html>