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
                    <cite>用户白名单录入</cite>
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
                    <div class="layui-colla-item">
                        <button class="layui-btn" lay-submit="" lay-filter="user_condition_upload">用户白名单录入</button>
                        <i class="layui-icon layui-icon-about"
                           style="font-size: 14px; color: #9F9F9F; margin-left: 20px;vertical-align:bottom;">&nbsp;流程：录入（待审核、已撤回
                            终）->审核（审核已通过、审核未通过 终）->完成</i>

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
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'name', title: '商城名称', width: 120},
            {field: 'total_num', title: '录入总数量', width: 120},
            {field: 'success_num', title: '录入成功条数', width: 120},
            {field: 'fail_num', title: '录入失败条数', width: 120},
            {field: 'action_user_name', title: '录入人', width: 120},
            {field: 'addtime', title: '录入时间', width: 150},
            {field: 'auth_user_name', title: '审核人', width: 120},
            {field: 'auth_time', title: '审核时间', width: 150},
            {field: 'status_cn', title: '状态', width: 120},
            {title: '操作', toolbar: '#operate', width: 190},
        ];

        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],

            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/shop/UserManage/index',
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

        form.on('submit(sreach)', function (obj) {
            table.reload('list', {
                page: {
                    curr: 1
                },
            });
            return false;
        });

        /**
         * 触发上传文件功能
         */
        form.on('submit(user_condition_upload)', function (obj) {
            xadmin.open('用户白名单录入', '/shop/UserManage/Upload');
            return false;
        });

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'detail') {
                xadmin.open('详情', '/shop/UserManage/Detail?upload_id=' + data.id );
            } else if (layEvent === 'cancel_file') {
                cancel_file(data);
            }
            else if (layEvent === 'auth_file') {
                auth_file(data);
            }
        });

    });


    function cancel_file(data) {
        layer.confirm('确认要撤回吗？', function (index) {
            $.ajax({
                url: '/shop/UserManage/cancel',
                data: {id:data.id},
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

    function auth_file(data) {
        layer.confirm('确认要审核通过吗？', function (index) {
            $.ajax({
                url: '/shop/UserManage/auth',
                data: {id:data.id},
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
    {{# if(d.status == 0 ){ }}
    <button class="layui-btn" title="撤销" lay-event="cancel_file">撤回</button>
    {{# } }}
    {{# if(d.status == 0 && <{$can_auth}> == 1){ }}
    <button class="layui-btn" title="审核" lay-event="auth_file">审核</button>
    {{# } }}
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>