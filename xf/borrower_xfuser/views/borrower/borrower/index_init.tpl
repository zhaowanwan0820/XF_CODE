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
                    <cite>借款人信息</cite>
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
                                            <label class="layui-form-label">证件类型</label>
                                            <div class="layui-input-inline">
                                                <select name="id_type" id="id_type" lay-search="">
                                                  <option value="0">全部</option>
                                                  <option value="1">身份中</option>
                                                  <option value="2">企业三证合一</option>
                                                
                                                </select>
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
                                                <input type="text" name="real_name" id="real_name" placeholder="" autocomplete="off"  class="layui-input" >
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
                                            <label class="layui-form-label">银行卡号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="bankcard" id="bankcard" placeholder="" autocomplete="off"  class="layui-input">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">撞库结果</label>
                                            <div class="layui-input-inline">
                                                <select name="status" id="status" lay-search="">
                                                  <option value="0">全部</option>
                                                  <option value="1">成功</option>
                                                  <option value="2">失败</option>
                                                
                                                </select>
                                              </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款渠道</label>
                                            <div class="layui-input-inline">
                                                <select name="borrower_src" id="borrower_src" lay-search="">
                                                  <option value="0">全部</option>
                                                  <option value="1">掌众</option>
                                                  <option value="2">大树</option>
                                                  <option value="3">其他</option>
                                                
                                                </select>
                                              </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">扣款方式</label>
                                            <div class="layui-input-inline">
                                                <select name="bind_type" id="bind_type" lay-search="">
                                                  <option value="0">全部</option>
                                                  <option value="1">协议扣款</option>
                                                  <option value="2">代扣款</option>
                                                
                                                </select>
                                              </div>
                                        </div>
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
            {field: 'user_id', title: 'user_id', fixed: 'left', width: 120},
            {field: 'real_name', title: '借款人姓名', width: 120},
            {field: 'mobile', title: '借款人手机号码', width: 180},
            {field: 'bankcard', title: '借款人银行卡号', width: 180},
            {field: 'id_type', title: '证件类型', width: 120},
            {field: 'idno', title: '借款人证件号', width: 180},
            {field: 'src_zz', title: '是否来源掌众借款', width: 140},
            {field: 'src_ds', title: '是否来源大树借款', width: 140},
            {field: 'src_other', title: '是否是其他来源', width: 140},
           
            // {field: 'transaction_number', title: '交易流水号', width: 140},
            {field: 'status', title: '撞库结果', width: 120},
            {field: 'errormsg', title: '撞库返回值', width: 180},
            {field: 'is_set_retail', title: '是否存在于零售系统', width: 160},
            {field: 'bind_type', title: '扣款方式', width: 160},
          
           
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
            url: '/borrower/borrower/index',
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
                        id_type: obj.field.id_type,
                        real_name: obj.field.real_name.trim(),
                        user_id: obj.field.user_id.trim(),
                        phone: obj.field.phone.trim(),
                        id_number: obj.field.id_number.trim(),
                        bankcard: obj.field.bankcard.trim(),
                        status: obj.field.status,
                        borrower_src: obj.field.borrower_src,
                        bind_type: obj.field.bind_type,

                    },
                page: {
                    curr: 1
                },
            });
            return false;
        });

        form.on('submit(export)', function (where) {
            where = where.field
            layer.confirm('确认要根据当前筛选条件导出吗？',
                function (index) {
                    layer.close(index);
                    location.href = "/borrower/borrower/index?execl=1" +
                        "&id_type=" + where.id_type +
                         "&real_name=" + where.real_name +
                         "&user_id=" + where.user_id +
                          "&phone=" + where.phone + 
                          "&id_number=" + where.id_number + 
                          "&phone=" + where.phone + 
                          "&bankcard=" + where.bankcard + 
                          "&status=" + where.status + 
                          "&borrower_src=" + where.borrower_src
                          "&bind_type=" + where.bind_type
                })
        });

     
        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'detail') {
                xadmin.open('借款详情', '/borrower/borrower/detail?user_id=' + data.user_id );
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
    <button class="layui-btn" title="借款详情" lay-event="detail">借款明细</button>

</script>
</html>