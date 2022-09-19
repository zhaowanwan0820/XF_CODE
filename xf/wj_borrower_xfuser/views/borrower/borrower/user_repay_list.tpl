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
                <a href="">个人借款人管理</a>
                <a>
                    <cite>个人借款人回款记录</cite>
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
                                            <label class="layui-form-label">借款人ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" id="user_id" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款人姓名</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="customer_name" id="customer_name" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">回款时间</label>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                                <input class="layui-input" name="repay_time_start" id="repay_time_start" readonly>
                                            </div>
                                            <div class="layui-form-mid">-</div>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                                <input class="layui-input"   name="repay_time_end" id="repay_time_end" readonly>
                                            </div>
                                        </div>
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                                <{if $can_export == 1 }>
                                                <button type="button" class="layui-btn layui-btn-danger"  onclick="UserRepayList2Excel()" >导出</button>
                                                <{/if}>
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
            {field: 'offline_repay_id', title: '还款记录ID', width: 140},
            {field: 'user_id', title: '借款人ID', width: 120},
            {field: 'customer_name', title: '借款人姓名', width: 120},
            {field: 'repay_amount', title: '本次还款金额', width: 120},
            {field: 'repay_content', title: '还款内容', width: 120},
            {field: 'total_repay_capital', title: '累计还款金额', width: 120},
            {field: 'surplus_repay_capital', title: '剩余还款本金', width: 120},
            {field: 'product_name', title: '产品名称', width: 120},
            {field: 'number', title: '订单编号', width: 160},
            {field: 'deal_name', title: '借款标题', width: 140},
            {field: 'deal_id', title: '借款编号', width: 140},
            {field: 'repay_time_cn', title: '回款时间' },
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
            url: '/borrower/borrower/userRepayList',
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
            elem: '#repay_time_start'
        });

        laydate.render({
            elem: '#repay_time_end'
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
                        repay_time_start:obj.field.repay_time_start.trim(),
                        repay_time_end:obj.field.repay_time_end.trim(),
                        user_id: obj.field.user_id.trim(),
                        customer_name: obj.field.customer_name.trim(),

                        //loan_amount_max: obj.field.loan_amount_max,

                    },
                page: {
                    curr: 1
                },
            });
            return false;
        });

    });


    function UserRepayList2Excel()
    {
        var user_id = $("#user_id").val();
        var customer_name = $("#customer_name").val();
        var repay_time_start = $("#repay_time_start").val();
        var repay_time_end = $("#repay_time_end").val();
        if (  user_id == '' && customer_name == '' && repay_time_start == '' && repay_time_end == '') {
            layer.msg('请输入至少一个查询条件');
        } else {
            layer.confirm('确认要根据当前筛选条件导出吗？',
                function(index) {
                    layer.close(index);
                    window.open("/borrower/borrower/UserRepayList2Excel?user_id="+user_id+"&customer_name="+customer_name+"&repay_time_start="+repay_time_start+"&repay_time_end="+repay_time_end , "_blank");
                });
        }
    }
</script>
</html>