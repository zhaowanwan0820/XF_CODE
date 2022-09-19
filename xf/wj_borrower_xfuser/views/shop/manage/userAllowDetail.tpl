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
                <a href="">商城管理</a>
                <a>
                    <cite>用户白名单</cite>
                </a>
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
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" >
                                    <div class="layui-form-item">

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input  name="user_id" id="user_id" placeholder="请输入用户ID"  class="layui-input" <{if $_GET['user_id']}> value="<{$_GET['user_id']}>" <{/if}>>
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">手机号码</label>
                                            <div class="layui-input-inline">
                                                <input  name="mobile" id="mobile" placeholder="请输入手机号码"  class="layui-input" <{if $_GET['mobile']}> value="<{$_GET['mobile']}>" <{/if}>>
                                            </div>
                                        </div>


                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <input type="hidden" name = "upload_id" value="<{$upload_id}>">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button  type="button" class="layui-btn layui-btn-primary" onclick="resetSearch()" >重置</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-card-body ">
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    layui.use(['laydate', 'table', 'layer', 'form'], function () {
        var laydate = layui.laydate;
        var form = layui.form;
        var table = layui.table;
        var appid = <{$appid}>;
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'user_id', title: '用户id', width: 180},
            {field: 'real_name', title: '用户姓名', width: 220},
            {field: 'mobile', title: '手机号码', width: 260},
            {field: 'status_cn', title: '状态', width: 120},
            {title: '操作', toolbar: '#operate',  width: 220},

        ];

        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],
            where: {
                appid: appid,
            },
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/shop/Manage/userAllowDetail',
            method: 'post',
            response:
                {
                    statusName: 'code',
                    statusCode: 0,
                    msgName: 'info',
                    countName: 'countNum',
                    dataName: 'list'
                }
        });


        form.on('submit(search)', function (obj) {
            table.reload('list', {
                where:
                    {
                        user_id: obj.field.user_id,
                        mobile: obj.field.mobile,
                        appid:  <{$appid}>,

                    },
                page: {
                    curr: 1
                },
            });
            return false;
        });


        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

           if (layEvent === 'user_open') {
               user_open(data);
            }
            else if (layEvent === 'user_close') {
               user_close(data);
            }
        });

    });

    function resetSearch() {
        $("#user_id").val("");
        $("#mobile").val("");
    }


    function user_close(data) {
        layer.confirm('确认要移除吗？', function (index) {
            $.ajax({
                url: '/shop/Manage/editUserStatus',
                data: {id:data.id,status:2},
                type: "POST",
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert(res.info);
                        location.reload()
                    } else {
                        layer.alert(res.info);
                    }
                }
            });
        })
    }

    function user_open(data) {
        layer.confirm('确认要启用吗？', function (index) {
            $.ajax({
                url: '/shop/Manage/editUserStatus',
                data: {id:data.id,status:1},
                type: "POST",
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert(res.info);
                        location.reload()
                    } else {
                        layer.alert(res.info);
                    }
                }
            });
        })
    }

</script>

<script type="text/html" id="operate">
    {{# if(<{$can_edit}> == 1 && d.status == 2){ }}
    <button class="layui-btn" title="启用" lay-event="user_open">启用</button>
    {{# } }}
    {{# if(<{$can_edit}> == 1&& d.status == 1){ }}
    <button class="layui-btn" title="移除" lay-event="user_close">移除</button>
    {{# } }}
</script>
</html>