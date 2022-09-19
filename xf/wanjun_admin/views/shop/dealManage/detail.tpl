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
                    <cite>项目白名单录入</cite>
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
                                            <label class="layui-form-label">项目ID</label>
                                            <div class="layui-input-inline">
                                                <input  name="deal_id" id="deal_id" placeholder="请输入项目ID"  class="layui-input" <{if $_GET['deal_id']}> value="<{$_GET['deal_id']}>" <{/if}>>
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">项目名称</label>
                                            <div class="layui-input-inline">
                                                <input  name="name" id="name" placeholder="请输入项目名称"  class="layui-input" <{if $_GET['name']}> value="<{$_GET['name']}>" <{/if}>>
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
        var upload_id = <{$upload_id}>;
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'deal_id', title: '项目id', width: 180},
            {field: 'type_cn', title: '项目类型', width: 180},
            {field: 'name', title: '项目名称', width: 180},
            {field: 'status_cn', title: '状态', width: 180},
        ];

        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],
            where: {
                upload_id: upload_id,
            },
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/shop/DealManage/detail',
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
                        deal_id: obj.field.deal_id,
                        name: obj.field.name,
                        upload_id:  <{$upload_id}>,

                    },
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
            xadmin.open('用户白名单录入', '/shop/DealManage/Upload');
            return false;
        });

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'detail') {
                xadmin.open('详情', '/shop/DealManage/detail?upload_id=' + data.id );
            } else if (layEvent === 'cancel_file') {
                cancel_file(data);
            }
            else if (layEvent === 'auth_file') {
                auth_file(data);
            }
        });

    });

    function resetSearch() {
        $("#user_id").val("");
        $("#mobile").val("");
    }


    function cancel_file(data) {
        layer.confirm('确认要撤回吗？', function (index) {
            $.ajax({
                url: '/offline/importFile/cancelP',
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
        layer.confirm('确认要撤回吗？', function (index) {
            $.ajax({
                url: '/offline/importFile/cancelP',
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

    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>