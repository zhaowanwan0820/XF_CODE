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
                                                <button  type="reset" class="layui-btn layui-btn-primary"  >重置</button>
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
        var purchase_id = <{$purchase_id}>;
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'deal_id', title: '项目id', width: 180},
            // {field: 'type_cn', title: '项目类型', width: 180},
            {field: 'name', title: '项目名称', width: 220},
            {field: 'status_cn', title: '状态', width: 180},
            {title: '操作', toolbar: '#operate',  width: 220},

        ];

        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],
            where: {
                purchase_id: purchase_id,
            },
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/debtMarket/debtPurchase/DealList',
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
                        purchase_id:  <{$purchase_id}>,

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

            if (layEvent === 'status_stop') {
                status_stop(data);
            }
            else if (layEvent === 'status_access') {
                status_access(data);
            }
        });

    });

    


    function status_stop(data) {
        layer.confirm('确认要停用吗？', function (index) {
            $.ajax({
                url: '/debtMarket/debtPurchase/PurchaseDealStatus',
                data: {id:data.id,type:0},
                type: "POST",
                dataType:'json',
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

    function status_access(data) {
        layer.confirm('确认要启用吗？', function (index) {
            $.ajax({
                url: '/debtMarket/debtPurchase/PurchaseDealStatus',
                data: {id:data.id,type:1},
                type: "POST",
                dataType:'json',
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
    {{# if(d.status == 1){ }}
        <button class="layui-btn" title="暂停" lay-event="status_stop">暂停</button>
    {{# } }}
    {{# if(d.status == 4){ }}
        <button class="layui-btn" title="启用" lay-event="status_access">启用</button>
    {{# } }}
</script>
</html>