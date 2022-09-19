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
                <a href="">债权管理</a>
                <a>
                    <cite>债权置换记录列表</cite>
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
                                            <label class="layui-form-label">置换记录ID：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="id" id="id" placeholder="请输入置换记录ID" autocomplete="off"   class="layui-input" value="<{$_GET['id']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">出借人ID：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" id="user_id" placeholder="请输入出借人ID" autocomplete="off"   class="layui-input" value="<{$_GET['user_id']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">出借人姓名：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="real_name" id="real_name" placeholder="请输入出借人姓名" autocomplete="off"  class="layui-input" value="<{$_GET['real_name']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">出借人电话：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="mobile_phone" id="mobile_phone" placeholder="请输入电话号码" autocomplete="off"  class="layui-input" value="<{$_GET['mobile_phone']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">出借人证件号：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="idno" id="idno" placeholder="请输入出借人证件号" autocomplete="off"  class="layui-input" value="<{$_GET['idno']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">银行卡号：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="bank_card" id="bank_card" placeholder="请输入银行卡号" autocomplete="off"  class="layui-input" value="<{$_GET['bank_card']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">归属地(省)：</label>
                                            <div class="layui-input-inline">
                                                <select name="province_name" id="province_name" lay-search="">
                                                    <option value="-1">全部</option>
                                                    <{foreach $province_name_list as $key=>$val}>
                                                    <option value='<{$key}>'><{$val}></option>
                                                    <{/foreach}>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">置换状态：</label>
                                            <div class="layui-input-inline">
                                                <select name="status" id="status" lay-search="">
                                                  <option value="-1">全部</option>
                                                  <option value="4">数据已迁移待置换</option>
                                                  <option value="5">置换成功</option>
                                                  <option value="6">用户已可见</option>
                                                </select>
                                              </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">置换方式：</label>
                                            <div class="layui-input-inline">
                                                <select name="displace_type" id="displace_type" lay-search="">
                                                    <option value="-1">全部</option>
                                                    <option value="0">系统批量操作</option>
                                                    <option value="1">用户法大大签约</option>
                                                    <option value="2">用户确认签约</option>
                                                    <option value="3">用户其他签约</option>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button class="layui-btn layui-btn-primary" type="reset">重置</button>
                                                <{if $displaceList2Excel == 1 }>
                                                <button type="button" class="layui-btn layui-btn-danger" onclick="displaceList2Excel()">导出列表</button>
                                                <{/if}>
                                                <{if $displaceListContract2Excel == 1 }>
                                                <button type="button" class="layui-btn layui-btn-danger" onclick="displaceListContract2Excel()">导出相关合同</button>
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
            {field: 'id', title: '置换记录ID',  width: 90},
            {field: 'user_id', title: '出借人ID',  width: 90},
            {field: 'real_name', title: '姓名', width: 80},
            {field: 'mobile_phone', title: '手机号', width: 120},
            {field: 'idno', title: '证件号', width: 160},
            {field: 'province_name_cn', title: '归属地(省)', width: 100},
            {field: 'card_address', title: '详细地址', width: 180},
            {field: 'bank_card', title: '银行卡号', width: 150},
            {field: 'displace_capital', title: '置换金额', align : 'right',width: 130},
            {field: 'ph_increase_reduce', title: '原普惠充提差', align : 'right',width: 130},
            {field: 'user_sign_time', title: '用户签约时间', width: 150},
            {field: 'debt_time', title: '债转完成时间', width: 150},
            {field: 'move_time', title: '债权迁移完成时间', width: 150},
            {field: 'displace_time', title: '置换完成时间', width: 150},
            {field: 'displace_type_cn', title: '置换方式', width: 150},
            {field: 'status_cn', title: '置换状态', width: 180},
            {title: '操作', toolbar: '#operate',width: 250},
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
            url: '/user/Loan/displaceList',
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
                        user_id: obj.field.user_id.trim(),
                        id: obj.field.id.trim(),
                        real_name: obj.field.real_name.trim(),
                        mobile_phone: obj.field.mobile_phone.trim(),
                        idno: obj.field.idno.trim(),
                        bank_card: obj.field.bank_card.trim(),
                        status: obj.field.status,
                        province_name: obj.field.province_name,
                        displace_type: obj.field.displace_type,
                    },
                page: {
                    curr: 1
                },
            });
            return false;
        });



        /**
         * 触发发布
         */
        form.on('submit(create_purchase)', function (obj) {
            xadmin.open('发布求购信息', '/debtMarket/exclusivePurchase/create?area_id=1',900,580);
            return false;
        });

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'detail') {
                xadmin.open('原普惠债权详情', '/user/Loan/displaceDetail?id=' + data.id ,900,680);
            }else if (layEvent === 'down_contract') {
                window.open("/user/loan/displaceListContract2Excel?id="+data.id , "_blank");
            }
        });

    });

    function resetSearch() {
        $("#id").val("");
        $("#status").val("-1");
        $("#user_id").val("");
        $("#real_name").val("");
        $("#mobile_phone").val("");
        $("#idno").val("");
        $("#bank_card").val("");
        $("#province_name").val("-1");
        $("#displace_type").val("-1");
    }

    function displaceList2Excel()
    {
        var id = $("#id").val();
        var status = $("#status").val();
        var user_id = $("#user_id").val();
        var real_name = $("#real_name").val();
        var mobile_phone = $("#mobile_phone").val();
        var idno = $("#idno").val();
        var bank_card = $("#bank_card").val();
        var province_name = $("#province_name").val();
        var displace_type = $("#displace_type").val();
        layer.confirm('确认要根据当前筛选条件导出吗？',
            function(index) {
                layer.close(index);
                window.open("/user/loan/displaceList2Excel?id="+id+"&status="+status+"&user_id="+user_id+"&real_name="+real_name+"&mobile_phone="+mobile_phone+"&idno="+idno+"&bank_card="+bank_card+"&province_name="+province_name+"&displace_type="+displace_type  , "_blank");
            }
        );
    }

    function displaceListContract2Excel()
    {
        var id = $("#id").val();
        var status = $("#status").val();
        var user_id = $("#user_id").val();
        var real_name = $("#real_name").val();
        var mobile_phone = $("#mobile_phone").val();
        var idno = $("#idno").val();
        var bank_card = $("#bank_card").val();
        var province_name = $("#province_name").val();
        var displace_type = $("#displace_type").val();
        layer.confirm('确认要根据当前筛选条件导出吗？',
            function(index) {
                layer.close(index);
                window.open("/user/loan/displaceListContract2Excel?id="+id+"&status="+status+"&user_id="+user_id+"&real_name="+real_name+"&mobile_phone="+mobile_phone+"&idno="+idno+"&bank_card="+bank_card+"&province_name="+province_name+"&displace_type="+displace_type  , "_blank");
            }
        );
    }

</script>

<script type="text/html" id="operate">
    <button class="layui-btn" title="原普惠债权详情" lay-event="detail">原普惠债权详情</button>
    <button class="layui-btn" title="下载相关合同" lay-event="down_contract">下载相关合同</button>
</script>
</html>