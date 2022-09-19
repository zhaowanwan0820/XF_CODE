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
                                            <label class="layui-form-label">状态：</label>
                                            <div class="layui-input-inline">
                                                <input type="radio" class="status" name="status" value="1" title="成交">
                                                <input type="radio" class="status" name="status" value="2" title="待处理">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">姓名：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="real_name" id="real_name" placeholder="请输入出让人姓名" autocomplete="off"  class="layui-input" value="<{$_GET['real_name']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">银行卡号：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="bank_num" id="bank_num" placeholder="请输入银行卡号" autocomplete="off"  class="layui-input" value="<{$_GET['bank_num']}>">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">出让人ID</label>
                                            <div class="layui-input-inline">
                                                <input  name="user_id" id="user_id" placeholder="请输入出让人ID"  class="layui-input" <{if $_GET['user_id']}> value="<{$_GET['deal_id']}>" <{/if}>>
                                            </div>
                                        </div>

                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <input type="hidden" name = "id" value="<{$_GET['id']}>">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button class="layui-btn layui-btn-primary" onclick="resetSearch()">重置</button>
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
        var id = <{$_GET['id']}>;
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'user_id', title: '出让人ID', width: 100},
            {field: 'real_name', title: '姓名', width: 120},
            {field: 'payee_bankcard', title: '银行卡号', width: 150},
            {field: 'status_cn', title: '状态', width: 80},
            {field: 'money', title: '出让金额', width: 120},
            {field: 'name', title: '借款标题', width: 200},
            {field: 'addtime', title: '出让时间', width: 180},
            {field: 'serial_number', title: '债转编号', width: 200},
            {title: '操作', toolbar: '#operate',fixed: 'right',width: 150},
           
        ];

        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],
            where: {
                id: id,
            },
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/debtMarket/debtPurchase/detail',
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


        laydate.render({
            elem: '#order_start'
        });

        laydate.render({
            elem: '#order_end'
        });

        laydate.render({
            elem: '#send_start'
        });

        laydate.render({
            elem: '#send_end'
        });


        form.on('submit(search)', function (obj) {
            table.reload('list', {
                where:
                    {
                        user_id: obj.field.user_id,
                        real_name: obj.field.real_name,
                        bank_num: obj.field.bank_num,
                        status: obj.field.status,
                        id:  <{$_GET['id']}>,

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

            if (layEvent === 'view_credentials') {
                xadmin.open('付款凭证', '/debtMarket/debtPurchase/viewCredentials?id=' + data.id );

            } else if (layEvent === 'upload_credentials') {
                xadmin.open('付款凭证', '/debtMarket/debtPurchase/uploadCredentials?id=' + data.id );
            }
            else if (layEvent === 'auth_file') {
                auth_file(data);
            }
        });

    });

    function resetSearch() {
        $("#user_id").val("");
        $("#bank_num").val("");
        $("#real_name").val("");
        $(".status").val("");
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
    {{# if(d.status == 5 ){ }}
    <button class="layui-btn" title="" lay-event="upload_credentials">上传付款凭证</button>
    {{# } }}
    {{# if(d.status == 6 || d.status == 2){ }}
    <button id="doh"  class="layui-btn" title="审核" lay-event="view_credentials">查看付款凭证</button>
    {{# } }}
   
</script>
</html>