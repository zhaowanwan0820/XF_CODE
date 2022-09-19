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
                    <cite>特殊还款协议标的列表</cite>
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

                                        <!-- <div class="layui-inline">
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
                                        <div class="layui-inline">
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
                                            <label class="layui-form-label">最近新还款日期</label>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                              <input class="layui-input" placeholder="开始时间" name="last_repay_start" id="last_repay_start" readonly>
                                            </div>
                                            <div class="layui-form-mid">-</div>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                              <input class="layui-input" placeholder="截止时间" name="last_repay_end" id="last_repay_end" readonly>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">创建人账号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="add_admin_name" id="add_admin_name" placeholder="" autocomplete="off"  class="layui-input" >
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款来源</label>
                                            <div class="layui-input-inline">
                                                <select name="data_src" id="data_src" lay-search="">
                                                  <option value="0">全部</option>
                                                  <option value="1">L库</option>
                                                  <option value="2">C库</option>                                               
                                                </select>
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
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'customer_name', title: '借款人姓名', width: 120},
            {field: 'id_number', title: '借款人证件号', width: 160},
            {field: 'phone', title: '借款人手机号', width: 120}, 
            {field: 'deal_name', title: '原借款标题', width: 140},
            {field: 'approve_number', title: '原订单编号', width: 150},
            {field: 'data_src_cn', title: '借款来源', width: 150},
            {field: 'borrow_amount', title: '原借款金额', width: 120},
            {field: 'rate', title: '原借款利率', width: 120},
            {field: 'repay_type', title: '原借款期限', width: 120},
            {field: 'un_puy_num', title: '原未还期数', width: 120},
            {field: 'principal', title: '原待还本金和', width: 120},
            {field: 'interest', title: '原待还利息和', width: 120},
            {field: 'new_principal', title: '新待还本金和', width: 120},
            {field: 'new_interest', title: '新待还利息和', width: 120},  
            {field: 'jianmian', title: '合计减免金额', width: 120},  
            {field: 'new_plan_num', title: '新还款计划期数', width: 120}, 
            {field: 'xf_last_repay_time', title: '最近新还款日期', width: 120}, 
            {field: 'add_user_name', title: '创建账号', width: 120},
            {field: 'company_name', title: '第三方公司', width: 120},
            {field: 'auth_user_name', title: '审核人', width: 120},  
            {field: 'auth_time', title: '审核时间', width: 120}, 
            {field: 'status_cn', title: '审核状态', width: 120}, 
           
           
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
            url: '/borrower/DealOrder/NewRepayPlanAuditList',
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
            elem: '#last_repay_start'
        });

        laydate.render({
            elem: '#last_repay_end'
        });

        form.on('submit(search)', function (obj) {
            table.reload('list', {
                where:
                    {
                        // number: obj.field.number.trim(),
                        // deal_name:obj.field.deal_name.trim(),
                        // deal_id:obj.field.deal_id.trim(),
                        customer_name: obj.field.customer_name.trim(),
                        phone: obj.field.phone.trim(),
                        id_number: obj.field.id_number.trim(),
                        // loan_amount_min: obj.field.loan_amount_min.trim(),
                        // loan_amount_max: obj.field.loan_amount_max,
                        last_repay_end: obj.field.last_repay_end,
                        last_repay_start: obj.field.last_repay_start,
                        data_src: obj.field.data_src,
                        // organization_name: obj.field.organization_name.trim(),
                        add_admin_name: obj.field.add_admin_name.trim(),
                        

                    },
                page: {
                    curr: 1
                },
            });
            return false;
        });

        /**
         * 触发上传文件功能
         */
        form.on('submit(user_condition_upload)', function (obj) {
            xadmin.open('订单导入', '/shop/ShopOrder/Upload');
            return false;
        });

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'audit_repay_plan') {
                xadmin.open('审核新还款计划', '/borrower/DealOrder/AuditNewRepayPlan?log_id=' + data.log_id );
            } else if (layEvent === 'repay_plan_detail') {
                xadmin.open('新还款计划', '/borrower/DealOrder/AuditNewRepayPlan?log_id=' + data.log_id );
            }
            else if (layEvent === 'auth_file') {
                auth_file(data);
            }
        });

    });


    function cancel_file(data) {
        layer.confirm('确认要撤回吗？', function (index) {
            $.ajax({
                url: '/shop/ShopOrder/cancel',
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
        layer.confirm('确认要审核通过吗？（请勿重复提交！！！）', function (index) {
            if ($("#doh").hasClass("disabled")) {
               return;
            }
            $("#doh").addClass("disabled")
            $("#doh").html("处理中...")

            $.ajax({
                url: '/shop/ShopOrder/auth',
                data: {id:data.id},
                type: "POST",
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert(res.info);
                        location.reload()
                    } else {
                        layer.alert(res.info,function () {
                            $("#doh").html("审核")
                            location.reload()
                        });

                    }
                }
            });
        })
    }



</script>

<script type="text/html" id="operate">
    {{# if(d.status == 0){ }}
    <{if $can_auth == 1}>
    <button class="layui-btn" title="" lay-event="audit_repay_plan">审核</button>
    <{/if}>
    <{if $can_auth != 1}>
    <button class="layui-btn" title="" lay-event="repay_plan_detail">详情</button>
    <{/if}>
    {{# } }}
    {{# if(d.status != 0){ }}
    <button class="layui-btn" title="" lay-event="repay_plan_detail">详情</button>
    {{# } }}

</script>
</html>