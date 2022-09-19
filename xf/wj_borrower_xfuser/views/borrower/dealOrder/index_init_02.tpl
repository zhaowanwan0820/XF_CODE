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
            {field: 'id', title: '借款编号', fixed: 'left', width: 80},
            {field: 'deal_name', title: '借款标题', width: 180},
            //{field: 'id', title: '借款编号', width: 140},
            {field: 'loan_amount', title: '借款金额', width: 120},
            // {field: 'repay_type', title: '借款期限', width: 80},
            //{field: 'un_puy_num', title: '原先锋待还期数', width: 130},
            {field: 'principal', title: '待还本金和', width: 150},
            {field: 'interest', title: '待还利息和', width: 150},
            //{field: 'user_id', title: '借款人ID', width: 120},
            {field: 'agency_name', title: '核心担保企业名称 ', width: 240},
            {field: 'customer_name', title: '借款方名称 ', width: 240},
            {field: 'id_number', title: '统一识别码', width: 180},
            {field: 'phone', title: '联系电话', width: 120 },
            {field: 'voucher_url_html', title: '放款凭证', width: 120 },
            {title: '操作', fixed: 'right',toolbar: '#operate',width: 200},
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
            url: '/borrower/DealOrder/index02',
            method: 'post',
            where: {user_id:<{$_GET['user_id']}>},
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

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'detail') {
                xadmin.open('详情', '/borrower/DealOrder/repayPlan?deal_id=' + data.id );
            }
        });

    });


</script>

<script type="text/html" id="operate">
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>