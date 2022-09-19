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
                    <cite>成功划扣记录</cite>
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
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" >
                                    <div class="layui-form-item">

                                        <div class="layui-inline">
                                            <label class="layui-form-label">订单编号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="number"  placeholder="" autocomplete="off" id="number" class="layui-input" >
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款标题</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_name" id="deal_name" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款编号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_id" id="deal_id" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>
                                        <!-- <div class="layui-inline">
                                            <label class="layui-form-label">借款金额</label>
                                            <div class="layui-input-inline" style="width: 85px;">
                                              <input type="text" name="loan_amount_min" placeholder="￥" autocomplete="off" class="layui-input">
                                            </div>
                                            <div class="layui-form-mid">-</div>
                                            <div class="layui-input-inline" style="width: 85px;">
                                              <input type="text" name="loan_amount_max" placeholder="￥" autocomplete="off" class="layui-input">
                                            </div>
                                        </div> -->
                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款人姓名</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="customer_name" id="customer_name" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款人手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="phone" id="phone" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款人证件号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="id_number" id="id_number" placeholder="" autocomplete="off"  class="layui-input">
                                            </div>
                                        </div>
                                       
                                        <div class="layui-inline">
                                            <label class="layui-form-label">划扣时间</label>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                              <input class="layui-input" placeholder="开始时间" name="repay_start" id="repay_start" readonly>
                                            </div>
                                            <div class="layui-form-mid">-</div>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                              <input class="layui-input" placeholder="截止时间" name="repay_end" id="repay_end" readonly>
                                            </div>
                                        </div>
                                        <!-- <div class="layui-inline">
                                            <label class="layui-form-label">咨询方</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="organization_name" id="organization_name" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div> -->
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
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
        var join = [
            {field: 'id', title: '序号',  },
            {field: 'number', title: '订单编号', width: 140},
            {field: 'deal_name', title: '借款标题', width: 140},
            {field: 'deal_id', title: '借款编号', width: 120},
            // {field: 'rate', title: '利率', width: 60},
            // {field: 'repay_type', title: '借款期限', width: 80},
            {field: 'principal', title: '应还本金',},
            {field: 'paid_principal', title: '已还本金',},
            {field: 'paid_principal_time', title: '本金还款时间', width: 150}, 
            {field: 'interest', title: '应还利息', },
            {field: 'paid_interest', title: '已还利息', },
            {field: 'paid_interest_time', title: '利息还款时间', width: 150}, 
            // {field: 'o_create_time', title: '借款时间', width: 150},
            
            // {field: 'deal_loantype', title: '还款方式', width: 140},
            {field: 'organization_name', title: '咨询方', width: 140},
            {field: 'product_name', title: '产品名称',width: 140},
            // {field: 'transaction_number', title: '交易流水号', width: 140},
            {field: 'customer_name', title: '借款人姓名', width: 120},
            {field: 'id_number', title: '借款人证件号', width: 140},
            {field: 'phone', title: '借款人手机号', width: 140}, 
            
            // {field: 'deal_src_cn', title: '数据来源', width: 80},
            {title: '操作', fixed: 'right',toolbar: '#operate',width: 80},
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
            url: '/borrower/DealOrder/repaySuccess',
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
            elem: '#repay_start'
        });

        laydate.render({
            elem: '#repay_end'
        });

        form.on('submit(search)', function (obj) {
            table.reload('list', {
                where:
                    {
                        number: obj.field.number,
                        deal_name:obj.field.deal_name,
                        deal_id:obj.field.deal_id,
                        customer_name: obj.field.customer_name,
                        phone: obj.field.phone,
                        id_number: obj.field.id_number,
                        repay_end: obj.field.repay_end,
                        repay_start: obj.field.repay_start,
                        organization_name: obj.field.organization_name,
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
                xadmin.open('借款详情', '/borrower/borrower/detail?deal_id=' + data.deal_id );
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
    <button class="layui-btn" title="借款详情" lay-event="detail">借款详情</button>

</script>
</html>