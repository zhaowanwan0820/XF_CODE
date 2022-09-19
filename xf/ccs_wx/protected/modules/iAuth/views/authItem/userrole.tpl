<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>角色管理-</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
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
                    <cite>角色管理</cite></a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right" onclick="location.reload()" title="刷新">
        <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i></a>
</div>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body ">
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" >
                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label">角色名</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name"  placeholder="角色名" autocomplete="off" id="username" class="layui-input" value="<{$_GET['name']}>">
                                            </div>
                                        </div>
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="demo1">立即搜索</button>
                                                <button type="button" class="layui-btn layui-btn-primary"  onclick="resett()">重置</button>
                                                <button type="button" class="layui-btn layui-btn-warm" onclick="xadmin.open('添加角色','/iauth/AuthItem/UserRoleAdd',600,400)"><i class="layui-icon"></i>添加</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-card-header">

                </div>
                <div class="layui-card-body ">
                    <table class="layui-table layui-form">
                        <thead>
                        <tr>
                            <th>
                                <input type="checkbox" name=""  lay-skin="primary">
                            </th>
                            <th>ID</th>
                            <th>角色名</th>
                            <th>拥有权限规则</th>
                            <th>描述</th>
                            <th>状态</th>
                            <th>操作</th>
                        </thead>
                        <tbody>
                        <{foreach $ret as $key => $val}>
                        <tr>
                            <td>
                                <input type="checkbox" name=""  lay-skin="primary">
                            </td>
                            <td><{$val['id']}></td>
                            <td><{$val['name']}></td>
                            <td><{$val['rolename']|truncate:30}></td>
                            <td><{$val['remark']}></td>
                            <td class="td-status">
                                <span class="layui-btn layui-btn-normal layui-btn-mini <{if $val['status'] != '已启用'}>layui-btn-disabled<{/if}>"><{$val['status']}></span></td>
                            <td class="td-manage">
                                <a onclick="member_stop(this,<{$val['id']}>)" href="javascript:;"  title="启用">
                                    <i class="layui-icon">&#xe601;</i>
                                </a>
                                <a title="编辑"  onclick="xadmin.open('编辑','/iauth/AuthItem/UserRoleEdit?itemId=<{$val['id']}>')" href="javascript:;">
                                    <i class="layui-icon">&#xe642;</i>
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
    layui.use(['laydate','form'], function(){
        var laydate = layui.laydate;
        var form = layui.form;

        //执行一个laydate实例
        laydate.render({
            elem: '#start' //指定元素
        });

        //执行一个laydate实例
        laydate.render({
            elem: '#end' //指定元素
        });
    });
    function resett(){
        $("#username").val("");
    }
    /*用户-停用*/
    function member_stop(obj,id){
        if($(obj).attr('title')=='启用'){var str = "停用";}else{var str = "启用";}
        layer.confirm('确认要'+str+'吗？',function(index){
            if($(obj).attr('title')=='启用'){
                //发异步把用户状态进行更改
                $.ajax({
                    url: '/iauth/AuthItem/Disable?id='+id,
                    type:"GET",
                    success: function (res) {
                        if(res.code == 0){
                            $(obj).attr('title','停用')
                            $(obj).find('i').html('&#xe62f;');
                            $(obj).parents("tr").find(".td-status").find('span').addClass('layui-btn-disabled').html('已停用');
                            layer.alert('已停用!',{icon: 5,time:1000});
                        }
                    }
                })
            }else{
                $.ajax({
                    url: '/iauth/AuthItem/Enable?id='+id,
                    type:"GET",
                    success: function (res) {
                        if(res.code == 0){
                            $(obj).attr('title','启用')
                            $(obj).find('i').html('&#xe601;');
                            $(obj).parents("tr").find(".td-status").find('span').removeClass('layui-btn-disabled').html('已启用');
                            layer.alert('已启用!',{icon: 5,time:1000});
                        }
                    }
                })
            }
        });
    }
</script>
</html>