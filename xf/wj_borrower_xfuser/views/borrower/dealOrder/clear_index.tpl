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
                <a href="">出清管理</a>
                <a>
                    <cite>还款凭证补录</cite>
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
                                            <label class="layui-form-label">产品名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="product_name"  placeholder="" autocomplete="off" class="layui-input" >
                                            </div>
                                        </div>
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
                                        <div class="layui-inline">
                                            <label class="layui-form-label">UID</label>
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
                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款金额</label>
                                            <div class="layui-input-inline" style="width: 83px;">
                                              <input type="text" name="loan_amount_min" placeholder="￥" autocomplete="off" class="layui-input">
                                            </div>
                                            <div class="layui-form-mid">-</div>
                                            <div class="layui-input-inline" style="width: 83px;">
                                              <input type="text" name="loan_amount_max" placeholder="￥" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">咨询方</label>
                                            <div class="layui-input-inline">
                                                <select name="organization_type" id="organization_type" lay-search="">
                                                  <option value="0">全部</option>
                                                  <option value="1">北京掌众金融信息服务有限公司</option>
                                                  <option value="2">悠融资产管理（上海）有限公司</option>
                                                  <option value="3">杭州大树网络技术有限公司（功夫贷）</option>
                                               
                                                </select>
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
                                        <div class="layui-inline">
                                            <label class="layui-form-label">还款状态</label>
                                            <div class="layui-input-inline">
                                                <select name="deal_status" id="deal_status" lay-search="">
                                                  <option value="0">全部</option>
                                                  <option value="4">还款中</option>
                                                  <option value="5">已出清</option>                                               
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
                                                <{if $can_export == 1 }>
                                                <button type="button" class="layui-btn layui-btn-danger" lay-submit="export" lay-filter="export">导出</button>
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
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'product_name', title: '产品名称', width: 80},
            {field: 'number', title: '订单编号', width: 150},
            {field: 'deal_name', title: '借款标题', width: 140},
            {field: 'id', title: '借款编号', width: 140},
            {field: 'loan_amount', title: '借款金额', width: 120},
            {field: 'rate', title: '利率', width: 60},
            {field: 'repay_type', title: '借款期限', width: 80},
            {field: 'un_puy_num', title: '未还期数', width: 120},
            {field: 'principal', title: '原待还本金和', width: 120},
            {field: 'interest', title: '原待还利息和', width: 120},
           
            // {field: 'o_create_time', title: '借款时间', width: 150},
            
            // {field: 'deal_loantype', title: '还款方式', width: 140},
            {field: 'organization_name', title: '咨询方', width: 150},
           
            // {field: 'transaction_number', title: '交易流水号', width: 140},
            {field: 'customer_name', title: '借款人姓名', width: 120},
            {field: 'id_number', title: '借款人证件号', width: 160},
            {field: 'phone', title: '借款人手机号', width: 120}, 
            // {field: 'deal_src_cn', title: '数据来源', width: 100},
            {field: 'data_src_cn', title: '借款来源', width: 100},
            {field: 'deal_status_cn', title: '还款状态', width: 100},
            // {field: 'auth_status_cn', title: '审核状态', width: 80},
            {title: '操作', fixed: 'right',toolbar: '#operate',width: 120},
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
            url: '/borrower/DealOrder/index',
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
                        number: obj.field.number.trim(),
                        deal_name:obj.field.deal_name.trim(),
                        deal_id:obj.field.deal_id.trim(),
                        user_id: obj.field.user_id.trim(),
                        customer_name: obj.field.customer_name.trim(),
                        phone: obj.field.phone.trim(),
                        id_number: obj.field.id_number.trim(),
                        loan_amount_min: obj.field.loan_amount_min.trim(),
                        loan_amount_max: obj.field.loan_amount_max,
                        product_name: obj.field.product_name.trim(),
                        organization_type: obj.field.organization_type,
                        data_src: obj.field.data_src,
                        deal_status: obj.field.deal_status,

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
                        "&number=" + where.number +
                         "&deal_name=" + where.deal_name +
                          "&deal_id=" + where.deal_id +
                        "&user_id=" + where.user_id +
                        "&customer_name=" + where.customer_name +
                          "&phone=" + where.phone + 
                          "&id_number=" + where.id_number + 
                          "&loan_amount_min=" + where.loan_amount_min + 
                          "&loan_amount_max=" + where.loan_amount_max +
                          "&product_name=" + where.product_name +
                          "&organization_type=" + where.organization_type +
                          "&deal_status=" + where.deal_status 
                })
        });

     
        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'detail') {
                xadmin.open('借款明细', '/borrower/DealOrder/Voucher?deal_id=' + data.id );
            } else if (layEvent === 'cancel_file') {
                cancel_file(data);
            }
            else if (layEvent === 'auth_file') {
                auth_file(data);
            }else if (layEvent === 'add_repay_plan') {
                
                xadmin.open('创建新还款计划', '/borrower/DealOrder/addRepayPlan?deal_id=' + data.id );

            } else if (layEvent === 'refund') {
                xadmin.open('退款', '/borrower/DealOrder/refund?deal_id=' + data.id );
            }
        });

    });


   



</script>

<script type="text/html" id="operate">
    <button class="layui-btn" title="借款明细" lay-event="detail">详情</button>
    <!-- {{# if(d.has_new_repay == 0){ }}
    <button class="layui-btn" title="" lay-event="add_repay_plan">创建线上划扣计划</button>
    {{# } }}
    <button class="layui-btn" title="退款" lay-event="refund">退款</button> -->
</script>
</html>