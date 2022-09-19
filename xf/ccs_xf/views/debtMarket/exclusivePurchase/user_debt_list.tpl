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
                <a href="">债转市场管理</a>
                <a>
                    <cite>定向收购-债权求购</cite>
                </a>
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
            {field: 'user_id', title: '出借人ID',  width: 80},
            {field: 'real_name', title: '姓名', width: 80},
            {field: 'mobile_phone', title: '电话', width: 120},
            {field: 'idno', title: '身份证号', width: 180},
            {field: 'bank_card', title: '银行卡号', width: 180},
            {field: 'wait_capital', title: '在途本金（元）', width: 120},
            {field: 'purchase_amount', title: '收购金额（元）', width: 120},
            {field: 'discount', title: '收购折扣', width: 80},
            {field: 'trading_num', title: '待处理单数', width: 80},
            {field: 'start_time', title: '发布时间', width: 150},
            {field: 'end_time', title: '失效时间', width: 150},
            {field: 'add_user_name', title: '录入人', width: 150},
            {field: 'status_cn', title: '状态', width: 80},
            {title: '操作', toolbar: '#operate',width: 320},
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
            url: '/debtMarket/ExclusivePurchase/index',
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
            elem: '#auth_start'
        });

        laydate.render({
            elem: '#auth_end'
        });

        laydate.render({
            elem: '#action_start'
        });

        laydate.render({
            elem: '#action_end'
        });

        form.on('submit(search)', function (obj) {
            table.reload('list', {
                where:
                    {
                        user_id: obj.field.user_id.trim(),
                        real_name: obj.field.real_name.trim(),
                        mobile_phone: obj.field.mobile_phone.trim(),
                        status: obj.field.status,                        
                    },
                page: {
                    curr: 1
                },
            });
            return false;
        });



        /**
         * 触发发布
         */
        form.on('submit(create_purchase)', function (obj) {
            xadmin.open('发布求购信息', '/debtMarket/exclusivePurchase/create?area_id=1',800,580);
            return false;
        });

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'detail') {
                xadmin.open('详情', '/debtMarket/exclusivePurchase/Detail?id=' + data.id );
            } else if (layEvent === 'cancel_file') {
                cancel_file(data);
            }
            else if (layEvent === 'auth_access') {
                auth_access(data);
            }
            else if (layEvent === 'auth_fail') {
                auth_fail(data);
            }
            else if (layEvent === 'deal_list') {
                xadmin.open('求购标的信息', '/debtMarket/exclusivePurchase/DealList?purchase_id=' + data.id );
            }
        });

    });

    function resetSearch() {
       
        $(".to_be_processed").val("0");
        $(".purchase_status").val("0");
        $("#action_start").val("");
        $("#action_end").val("0");
        $("#release_people").val("");
        $("#buyer_people").val("");
        $("#discount").val("");
        $("#area_id").val("0");
        
    }


    function cancel_file(data) {
        layer.confirm('确认要终止吗？', function (index) {
            $.ajax({
                url: '/debtMarket/exclusivePurchase/stop',
                data: {id:data.id},
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
    function auth_access(data) {
        layer.confirm('确认要审核通过吗？', function (index) {
            $.ajax({
                url: '/debtMarket/exclusivePurchase/auth',
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
    function auth_fail(data) {
        layer.confirm('确认要拒绝吗？', function (index) {
            $.ajax({
                url: '/debtMarket/exclusivePurchase/auth',
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




</script>

<script type="text/html" id="operate">
    <button class="layui-btn" title="详情" lay-event="deal_list">详情</button>
    {{# if(d.status == 1){ }}
        <button class="layui-btn" title="付款审核" lay-event="auth_access">付款审核</button>
    {{# } }}
  
  

</script>
</html>