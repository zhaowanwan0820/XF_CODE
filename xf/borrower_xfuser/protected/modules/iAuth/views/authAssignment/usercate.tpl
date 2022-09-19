<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>权限分类</title>
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
<div class="x-nav">
     <span class="layui-breadcrumb">
                <a href="">首页</a>
                <a href="">管理员管理</a>
                <a>
                    <cite>权限分类</cite></a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38"
       onclick="location.reload()" title="刷新">
        <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i></a>
</div>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body ">
                    <form class="layui-form layui-col-space5">

                        <div class="layui-inline layui-show-xs-block">
                            <input type="text" name="username" id="L_username" placeholder="分类名" autocomplete="off"
                                   class="layui-input">
                        </div>
                        <div class="layui-inline layui-show-xs-block">
                            <button class="layui-btn" lay-submit="" lay-filter="add">增加</button>
                        </div>
                    </form>
                </div>

                <div class="layui-card-body ">
                    <table class="layui-table layui-form">
                        <thead>
                        <tr>
                            <th>
                                <input type="checkbox" name="" lay-skin="primary">
                            </th>
                            <th>ID</th>
                            <th>分类名</th>
                            <th>操作</th>
                        </thead>
                        <tbody>
                        <{foreach $authitem as $key => $val}>
                            <tr>
                                <td>
                                    <input type="checkbox" name="" lay-skin="primary">
                                </td>
                                <td><{$val['id']}></td>
                                <td><{$val['name']}></td>
                                <td class="td-manage">
                                    <a title="编辑"
                                       onclick="xadmin.open('编辑','/iauth/AuthAssignment/AuthEdit?itemId=<{$val['id']}>')"
                                       href="javascript:;">
                                        <i class="layui-icon">&#xe642;</i>
                                    </a>
                                    <a title="删除" onclick="member_del(this,<{$val['id']}>)" href="javascript:;">
                                        <i class="layui-icon">&#xe640;</i>
                                    </a>
                                </td>
                            </tr>
                            <{/foreach}>
                        </tbody>
                    </table>
                </div>
                <div class="layui-card-body ">
                    <div class="page">
                        <div>
                            <{$pages}>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    layui.use(['laydate', 'form'], function () {
        var laydate = layui.laydate;
        var form = layui.form;
        //监听提交
        form.on('submit(add)', function (data) {
            //发异步，把数据提交给php
            $.ajax({
                url: '/iauth/AuthAssignment/UserCate',
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
    /*用户-删除*/
    function member_del(obj, id) {
        layer.confirm('确认要删除吗？', function (index) {
            //发异步删除数据
            $.ajax({
                url: '/iauth/AuthAssignment/userroledel?itemId=' + id,
                type: "GET",
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert("删除成功");
                    } else {
                        layer.alert(res.info);
                    }
                }
            })
            $(obj).parents("tr").remove();
            layer.alert('已删除!', {icon:1,time:1000});
        });
    }
</script>

</html>