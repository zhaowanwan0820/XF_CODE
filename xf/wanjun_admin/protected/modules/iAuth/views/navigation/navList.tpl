<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>权限管理-</title>
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
                <a><cite>导航栏管理</cite></a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8"
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
                            <select name="top_id" lay-filter="top_id">
                                <option selected = "selected" value="0" >是否顶级导航栏</option>
                                <option value="1">是</option>
                                <option value="2">否</option>
                            </select>
                        </div>
                        <div class="layui-inline layui-show-xs-block" id="nav_parent_id">
                            <select name="parent_id" lay-filter="parent_id">
                                <option value="0"  selected = "selected">所属导航栏</option>
                                <{foreach $top as $k => $v}>
                                <option value="<{$v.id}>"><{$v.n_name}></option>
                                <{/foreach}>
                            </select>
                        </div>
                        <div class="layui-inline layui-show-xs-block" id = "nav_code">
                            <input type="text" name="code" id="L_username" placeholder="导航栏规则 /admin/user/user"
                                   autocomplete="off" class="layui-input">
                        </div>
                        <div class="layui-inline layui-show-xs-block">
                            <input type="text" name="n_name" id="L_username" placeholder="导航栏名称" autocomplete="off"
                                   class="layui-input">
                        </div>
                        <div class="layui-inline layui-show-xs-block">
                            <input type="text" name="icon" id="icon" placeholder="导航栏图标" onclick="xadmin.open('编辑','/iauth/navigation/NavIconEdit')" autocomplete="off"
                                   class="layui-input" value="<{$icon}>">
                        </div>
                        <div class="layui-inline layui-show-xs-block">
                            <button class="layui-btn" lay-submit="" lay-filter="add" >增加</button>
                        </div>
                    </form>
                </div>

                <div class="layui-card-body ">
                    <table class="layui-table layui-form">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>导航栏名称</th>
                            <th>导航栏规则</th>
                            <th>操作</th>
                        </thead>
                        <tbody>
                        <{foreach $ret as $key => $val}>
                            <tr>
                                <td><{$val['id']}></td>
                                <td><{$val['n_name']}></td>
                                <td><{$val['code']}></td>
                                <td class="td-manage">
                                    <a title="编辑"
                                       onclick="xadmin.open('编辑','/iauth/Navigation/NavEdit?id=<{$val['id']}>')"
                                       href="javascript:;">
                                        <i class="layui-icon">&#xe642;</i>
                                    </a>
                                    <a title="删除" onclick="member_del(this,<{$val['id']}>)" href="javascript:;">
                                        <i class="layui-icon">&#xe640;</i>
                                    </a>
                                </td>
                            </tr>
                            <{if !empty($val.children) }>
                                <{foreach $val.children as $k => $value}>
                                <tr>
                                    <td><{$value['id']}></td>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|--<{$value['n_name']}></td>
                                    <td><{$value['code']}></td>
                                    <td class="td-manage">
                                        <a title="编辑"
                                           onclick="xadmin.open('编辑','/iauth/Navigation/NavEdit?id=<{$value['id']}>')"
                                           href="javascript:;">
                                            <i class="layui-icon">&#xe642;</i>
                                        </a>
                                        <a title="删除" onclick="member_del(this,<{$value['id']}>)" href="javascript:;">
                                            <i class="layui-icon">&#xe640;</i>
                                        </a>
                                    </td>
                                </tr>
                                <{/foreach}>
                            <{/if}>
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
            var form = data.field;
            if(form.n_name == ''){
                layer.alert("请填写导航栏名称");
                return false;
            }
            if(form.top_id == 0){
                layer.alert("请选择是否为顶级栏目");
                return false;
            }
            if(form.top_id == 2){
                if(form.code == ''){
                    layer.alert("请填写导航栏规则");
                    return false;
                }
            }
            $.ajax({
                url: '/iauth/Navigation/NavAdd',
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
                url: '/iauth/Navigation/NavDel?id=' + id,
                type: "GET",
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert("删除成功",
                                function (data, item) {
                                    location.reload();
                                });
                    } else {
                        layer.alert(res.info,function (data, item) {
                                    location.reload();
                                });
                    }
                }
            })
        });
    }
</script>
<script>
    layui.use('form', function(){
        var form = layui.form;
        /*隐藏还是显示*/
        form.on('select(top_id)', function(data){
            if(data.value == 1){
                $("#nav_code").hide(500);
                $("#nav_parent_id").hide(500);
                $("#icon").show(500);
            }else{
                $("#nav_code").show(500);
                $("#nav_parent_id").show(500);
                $("#icon").hide(500);
            }
        });
    });

</script>
</html>