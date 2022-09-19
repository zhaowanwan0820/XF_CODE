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
        <a href="">借款人还款管理</a>
        <a>
            <cite>借款信息</cite>
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
        var user_id = <{$user_id}>;
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'user_id', title: '用户id', width: 80},
            {field: 'order_no', title: '购物订单号', width: 180},
            {field: 'order_time', title: '下单时间', width: 180},
            {field: 'order_amount', title: '订单金额', width: 180},
            {field: 'debt_integral_amount', title: '使用债权积分', width: 180},
            {field: 'shop_integral_amount', title: '使用商城积分', width: 180},
            {field: 'goods_name', title: '商品名称', width: 180},
            {field: 'goods_price', title: '商品价格', width: 180},
            {field: 'goods_use_integral', title: '商品可使用积分', width: 180},
            {field: 'exchange_no', title: '兑换积分流水号', width: 180},
            {field: 'delivery_name', title: '快递公司', width: 180},
            {field: 'send_time', title: '发货时间', width: 180},
            {field: 'delivery_no', title: '物流单号', width: 180},
            {field: 'status_cn', title: '状态', width: 180},
           
        ];

        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],
            where: {
                user_id: user_id,
            },
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/borrower/borrower/detail',
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
                        
                        order_no: obj.field.order_no,
                        order_start: obj.field.order_start,
                        order_end: obj.field.order_end,
                        order_amount: obj.field.order_amount,
                        delivery_no: obj.field.delivery_no,
                        goods_name: obj.field.goods_name,
                        goods_price: obj.field.goods_price,
                        send_start: obj.field.send_start,
                        send_end: obj.field.send_end,
                        exchange_no: obj.field.exchange_no,
                        user_id:  <{$user_id}>,

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

            if (layEvent === 'detail') {
                xadmin.open('详情', '/shop/ShopOrder/detail?upload_id=' + data.id );
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