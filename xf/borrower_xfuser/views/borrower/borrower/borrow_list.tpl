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
                    <cite>借款明细</cite>
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
        var user_id = <{$user_id}>;
        var deal_id = <{$deal_id}>;
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'deal_status_cn', title: '还款状态', width: 140},

            {field: 'number', title: '订单编号', width: 150},
            {field: 'deal_name', title: '借款标题', width: 140},
            {field: 'loan_amount', title: '借款金额', width: 120},
            {field: 'rate', title: '利率', width: 60},
            {field: 'repay_type', title: '借款期限', width: 80},
            {field: 'un_puy_num', title: '未还期数', width: 120},

            {field: 'principal', title: '原待还本金和', width: 120},
            {field: 'interest', title: '原待还利息和', width: 120},
            // {field: 'o_create_time', title: '借款时间', width: 150},
            
            {field: 'organization_name', title: '咨询方', width: 150},
            {field: 'product_name', title: '产品名称', width: 80},
            // {field: 'transaction_number', title: '交易流水号', width: 140},
            {field: 'customer_name', title: '借款人姓名', width: 120},
            {field: 'id_number', title: '借款人证件号', width: 160},
            {field: 'phone', title: '借款人手机号', width: 120}, 
            // {field: 'deal_src_cn', title: '数据来源', width: 120},
            {field: 'data_src_cn', title: '借款来源', width: 120},
            {field: 'deal_status_cn', title: '还款状态', width: 80},
            {title: '操作', fixed: 'right',toolbar: '#operate',width: 80},
        ];

        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],
            where: {
                user_id: user_id,
                deal_id: deal_id,
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
                        auth_status: obj.field.auth_status,
                        number: obj.field.number.trim(),
                        deal_name:obj.field.deal_name.trim(),
                        //deal_id:obj.field.deal_id.trim(),
                        customer_name: obj.field.customer_name.trim(),
                        phone: obj.field.phone.trim(),
                        id_number: obj.field.id_number.trim(),
                        loan_amount_min: obj.field.loan_amount_min.trim(),
                        loan_amount_max: obj.field.loan_amount_max,
                        organization_name: obj.field.organization_name.trim(),
                        user_id:  <{$user_id}>,
                        deal_id:  <{$deal_id}>,

                        

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
                xadmin.open('原还款计划', '/borrower/DealOrder/repayPlan?deal_id=' + data.id );
            } else if (layEvent === 'cancel_file') {
                cancel_file(data);
            }
            else if (layEvent === 'auth_file') {
                auth_file(data);
            }
        });

    });


   



</script>

<script type="text/html" id="operate">
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>

</script>
</html>