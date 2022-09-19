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
                <a href="">消费信贷标的管理</a>
                <a>
                    <cite>凭证补录审核</cite>
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


                                        <!-- <div class="layui-inline">
                                            <label class="layui-form-label">审核状态</label>
                                            <div class="layui-input-inline">
                                                <select name="auth_status" id="auth_status" lay-search="">
                                                  <option value="0">全部</option>
                                                  <option value="1">待确认</option>
                                                  <option value="2">待审核</option>
                                                  <option value="3">审核拒绝</option>
                                                </select>
                                              </div>
                                        </div> -->
                                      
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
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                                <{if $can_export == 1 }>
                                                <!-- <button type="button" class="layui-btn layui-btn-danger" lay-submit="export" lay-filter="export">导出</button> -->
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
            // {field: 'id', title: '序号', fixed: 'left', width: 80},
           
            {field: 'deal_name', title: '借款标题', width: 140},
            {field: 'deal_id', title: '借款编号', width: 140},
            {field: 'borrow_amount', title: '借款金额', width: 120},
            //{field: 'organization_name', title: '咨询方', width: 150},
            {field: 'user_id', title: '借款人ID', width: 120},
            {field: 'customer_name', title: '借款人姓名', width: 120},
            {field: 'id_number', title: '借款人证件号' },
            {field: 'phone', title: '借款人手机号', width: 120},
             {field: 'status_cn', title: '状态', width: 80},
            {title: '操作', fixed: 'right',toolbar: '#operate',width: 100},
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
            url: '/borrower/DealOrder/auditClearIndex',
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
                        // auth_status: obj.field.auth_status,
                        // number: obj.field.number.trim(),
                        deal_name:obj.field.deal_name.trim(),
                        deal_id:obj.field.deal_id.trim(),
                        user_id: obj.field.user_id.trim(),
                        customer_name: obj.field.customer_name.trim(),
                        phone: obj.field.phone.trim(),
                        id_number: obj.field.id_number.trim(),

                        // product_name: obj.field.product_name.trim(),

                        // deal_status: obj.field.deal_status,

                    },
                page: {
                    curr: 1
                },
            });
            return false;
        });

        form.on('submit(export)', function (where) {
            where = where.field
            var type = $("#type_list li.layui-this ").attr('data-status')
            layer.confirm('确认要根据当前筛选条件导出吗？',
                function (index) {
                    layer.close(index);
                    location.href = "/borrower/DealOrder/index?execl=1" +
                         "&deal_name=" + where.deal_name +
                          "&deal_id=" + where.deal_id +
                        "&user_id=" + where.user_id +
                        "&customer_name=" + where.customer_name +
                          "&phone=" + where.phone + 
                          "&id_number=" + where.id_number
                })
        });

     
        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;
            if (layEvent === 'audit_repay_plan') {
                xadmin.open('审核', '/borrower/DealOrder/Voucher?deal_id=' + data.deal_id +"&is_show_audit=1");
            } else
            if (layEvent === 'detail') {
                xadmin.open('详情', '/borrower/DealOrder/Voucher?deal_id=' + data.deal_id +"&is_show_audit=1");
            }
        });

    });


   



</script>

<script type="text/html" id="operate">
    {{# if(d.status == 0){ }}
    <button class="layui-btn layui-btn-danger" title="" lay-event="audit_repay_plan">审核</button>
    {{# } }}
    {{# if(d.status >= 1 ){ }}
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
    {{# } }}
</script>
</html>