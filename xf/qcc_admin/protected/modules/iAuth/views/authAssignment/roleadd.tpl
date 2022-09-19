<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>权限管理-</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/jquery.min.js"></script>
</head>
<body>
<div class="x-nav">
     <span class="layui-breadcrumb">
                <a href="">首页</a>
                <a href="">管理员管理</a>
                <a>
                    <cite>权限管理</cite></a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8"
       onclick="location.reload()" title="刷新">
        <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i></a>
</div>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">

            <div class="layui-card">
                <div class="layui-card-body">
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" >
                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label">权限名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name" placeholder="权限名称" autocomplete="off" id="name_han" class="layui-input" value="<{$_GET['name']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">所属分类</label>
                                            <div class="layui-inline layui-show-xs-block">
                                                <select name="parent">
                                                    <option value="0" selected = "selected">权限分类</option>
                                                    <{foreach $assignTop as $key => $val}>
                                                    <option value="<{$val['id']}>" <{if $_GET['parent'] == $val['id']}>selected = "selected"<{/if}>><{$val['name']}></option>
                                                    <{/foreach}>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="demo1">立即搜索</button>
                                                <button type="button" class="layui-btn layui-btn-primary" lay-filter="apk_reset_btn" onclick="resett()" id="apk_reset_btn">重置</button>
                                                <button type="button" class="layui-btn layui-btn-warm" onclick="xadmin.open('添加权限','/iauth/AuthAssignment/AddJurisdiction')">添加权限</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-card-body ">
                    <table class="layui-table">
                        <thead>
                        <tr>
                            <th>
                                <input type="checkbox" name="" lay-skin="primary">
                            </th>
                            <th>ID</th>
                            <th>权限规则</th>
                            <th>权限名称</th>
                            <th>所属分类</th>
                            <th>操作</th>
                        </thead>
                        <tbody>
                        <{foreach $authitem as $key => $val}>
                            <tr>
                                <td>
                                    <input type="checkbox" name="" lay-skin="primary">
                                </td>
                                <td><{$val['id']}></td>
                                <td><{$val['code']}></td>
                                <td><{$val['name']}></td>
                                <td><{$val['pname']}></td>
                                <td class="td-manage">
                                    <a title="编辑"
                                       onclick="xadmin.open('编辑','/iauth/AuthAssignment/roleedit?itemId=<{$val['id']}>')"
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
    layui.use(['form', 'layer'],
            function() {
                $ = layui.jquery;
                var form = layui.form,
                    layer = layui.layer;
            });
    function resett(){
       $("#name_han").val("");
    }
    /*用户-删除*/
    function member_del(obj, id) {
        layer.confirm('确认要删除吗？', function (index) {
            //发异步删除数据
            $.ajax({
                url: '/iauth/AuthAssignment/RoleDel?itemId=' + id,
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


    function delAll(argument) {

        var data = tableCheck.getData();

        layer.confirm('确认要删除吗？' + data, function (index) {
            //捉到所有被选中的，发异步进行删除
            layer.alert('删除成功', {icon: 1});
            $(".layui-form-checked").not('.header').parents('tr').remove();
        });
    }
</script>
</html>