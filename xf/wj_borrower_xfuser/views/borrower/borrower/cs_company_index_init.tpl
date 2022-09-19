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
<div class="x-nav">
    <span class="layui-breadcrumb">
                <a href="">催收管理</a>
                <a>
                    <cite>企业借款人列表</cite>
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
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" id="user_id" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>
    
                                        <div class="layui-inline">
                                            <label class="layui-form-label">企业名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="real_name" id="real_name" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">联系电话</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="phone" id="phone" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">统一识别码</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="id_number" id="id_number" placeholder="" autocomplete="off"  class="layui-input">
                                            </div>
                                        </div>
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
            {field: 'user_id', title: '用户ID', fixed: 'left', width: 120},
            {field: 'real_name', title: '企业名称', width: 260},
            {field: 'mobile', title: '联系电话', width: 130},
            {field: 'idno', title: '统一识别码', width: 180},
            {field: 'bankcard', title: '银行卡号', width: 180},

            {field: 'count_deal', title: '借款标的数量', width: 130},
            {field: 'borrow_amount', title: '原始借款本金', width: 130},
            {field: 'borrow_interest', title: '原始借款利息', width: 130},
            {field: 'wait_capital', title: '逾期借款本金', width: 130},
            {field: 'wait_interest', title: '逾期借款利息', width: 130},
            {field: 'paid_capital', title: '已还借款本金', width: 130},
            {field: 'paid_interest', title: '已还借款利息', width: 130},
            {field: 'wait_capital', title: '剩余借款本金', width: 130},
            {field: 'wait_interest', title: '剩余借款利息', width: 130},

            {field: 'company_user_status', title: '企业状态', width: 100},
            {field: 'company_name', title: '接案公司' , width: 180},
            {field: 'cuishou_status_cn', title: '催收状态', width: 100},
            {title: '操作', fixed: 'right',toolbar: '#operate',width: 220},
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
            url: '/borrower/borrower/csCompanyIndex',
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

                        real_name: obj.field.real_name.trim(),
                        user_id: obj.field.user_id.trim(),
                        phone: obj.field.phone.trim(),
                        id_number: obj.field.id_number.trim(),
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
                xadmin.open('借款详情', '/borrower/DealOrder/Index02?user_id=' + data.user_id );
            } else if (layEvent === 'cs_company_policy_making') {
                xadmin.open('法诉状态维护', '/borrower/DealOrder/csCompanyPolicyMaking?user_id=' + data.user_id );
            }
        });

    });

</script>

<script type="text/html" id="operate">
    <button class="layui-btn" title="借款详情" lay-event="detail">借款详情</button>
    <button class="layui-btn" title="法诉状态维护" lay-event="cs_company_policy_making">法诉状态维护</button>
</script>
</html>