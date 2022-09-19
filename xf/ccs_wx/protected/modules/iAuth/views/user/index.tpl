<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>欢迎页面-</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!--datatables-->
    <link rel="stylesheet" href="<{$CONST.cssPath}>/jquery.dataTables.min.css">
    <script src="<{$CONST.jsPath}>/jquery-2.1.4.min.js"></script>
    <script src="<{$CONST.jsPath}>/jquery.dataTables.min.js"></script>
</head>
<body>
<div class="x-nav">
    <span class="layui-breadcrumb">
                <a href="">首页</a>
                <a href="">管理员管理</a>
                <a>
                    <cite>管理员列表</cite></a>
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
                                            <label class="layui-form-label">登录名</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="username" placeholder="登录名" autocomplete="off" id="username" class="layui-input" value="<{$_GET['username']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">真实姓名</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="realname" placeholder="真实姓名" autocomplete="off" id="realname" class="layui-input" value="<{$_GET['realname']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="phone" placeholder="手机号" autocomplete="off" id="phone" class="layui-input" value="<{$_GET['phone']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">账号类型</label>
                                            <div class="layui-inline layui-show-xs-block">
                                                <select name="user_type" id="user_type">
                                                    <option value="0" >全部</option>
                                                    <option value="1" <{if $_GET['user_type'] == 1}>selected = "selected"<{/if}>>普通用户</option>
                                                    <option value="2" <{if $_GET['user_type'] == 2}>selected = "selected"<{/if}>>咨询方用户</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="demo1">立即搜索</button>
                                                <button type="button" class="layui-btn layui-btn-primary"  onclick="resett()">重置</button>
                                                <button type="button" class="layui-btn layui-btn-warm" onclick="xadmin.open('添加用户','/iauth/user/UserAdd',600,400)"><i class="layui-icon"></i>添加</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-card-header">
                    <input type="hidden" value="<{$pageSize}>" id="pageSize">
                </div>
                <div class="layui-card-body ">
                    <table class="layui-table layui-form" id="myTable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>登录名</th>
                            <th>真实姓名</th>
                            <th>手机号</th>
                            <th>邮箱</th>
                            <th>角色</th>
                            <th>账号类型</th>
                            <th>加入时间</th>
                            <th>状态</th>
                            <th>操作</th>
                        </thead>
                        <tbody>
                        <{foreach $brand as $key => $val}>
                            <tr>
                                <td><{$val['id']}></td>
                                <td><{$val['username']}></td>
                                <td><{$val['realname']}></td>
                                <td><{$val['phone']}></td>
                                <td><{$val['email']}></td>
                                <td><{$val['rolename']}></td>
                                <td><{if $val['user_type'] == 1}>普通用户<{else}>咨询方用户<{/if}></td>
                                <td><{$val['addtime']}></td>
                                <td class="td-status">

                                    <span class="layui-btn layui-btn-normal layui-btn-mini <{if $val['status_info'] != '已启用'}>layui-btn-disabled<{/if}>"><{$val['status_info']}></span></td>
                                <td class="td-manage">
                                    <a onclick="member_stop(this,<{$val['id']}>)" href="javascript:;"  title="<{$val['status_info']}>">
                                        <i class="layui-icon">&#xe601;</i>
                                    </a>
                                    <a title="编辑"  onclick="xadmin.open('编辑','/iauth/user/useredit?id=<{$val['id']}>')" href="javascript:;">
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
                            <{$pages}>
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
    });
    function resett(){
        $("#phone").val("");
        $("#username").val("");
        $("#realname").val("");
    }
    /*用户-停用*/
    function member_stop(obj,id){
        if($(obj).attr('title')=='已启用'){var str = "注销";}else{var str = "启用";}
        layer.confirm('确认要'+str+'吗？',function(index){
            if($(obj).attr('title')=='已启用'){
                //发异步把用户状态进行更改
                $.ajax({
                    url: '/iauth/user/UpdateStatus?status=2&pkId='+id,
                    type:"GET",
                    success: function (res) {
                        if(res.code == 0){
                            $(obj).attr('title','已注销')
                            $(obj).find('i').html('&#xe62f;');
                            $(obj).parents("tr").find(".td-status").find('span').addClass('layui-btn-disabled').html('已注销');
                            layer.alert('已注销!',{icon: 5,time:1000});
                        }
                    }
                })
            }else{
                $.ajax({
                    url: '/iauth/user/UpdateStatus?status=1&pkId='+id,
                    type:"GET",
                    success: function (res) {
                        if(res.code == 0){
                            $(obj).attr('title','已启用');
                            $(obj).find('i').html('&#xe601;');
                            $(obj).parents("tr").find(".td-status").find('span').removeClass('layui-btn-disabled').html('已启用');
                            layer.alert('已启动',{icon: 1,time:1000});
                        }
                    }
                })
            }
        });
    }
</script>
</html>