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
                <a href="">债转市场管理</a>
                <a>
                    <cite>汇源专区-债权求购</cite>
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
                                            <label class="layui-form-label">选择专区</label>
                                            <div class="layui-inline layui-show-xs-block" style="width: 190px;">
                                                <select name="area_id" required lay-verify="required"  >
                                                    <option value=0 >请选择</option>
                                                    <{foreach $area_list as $key => $val}>
                                                    <option value="<{$val['id']}>" ><{$val['name']}></option>
                                                    <{/foreach}>
                                                </select>
                                            </div>
                                        </div> -->
                                        <div class="layui-inline">
                                            <label class="layui-form-label">折扣：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="discount" id="discount" placeholder="折扣金额0.01~10" autocomplete="off"   class="layui-input" value="<{$_GET['discount']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">发布时间：</label>
                                            <div class="layui-input-inline">
                                              <input class="layui-input" placeholder="起始" name="action_start" id="action_start" readonly>
                                            </div>
                                            <div class="layui-input-inline">
                                              <input class="layui-input" placeholder="截止" name="action_end" id="action_end" readonly>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">有无待处理：</label>
                                            <div class="layui-input-inline">
                                                <input type="radio" class="to_be_processed" name="to_be_processed" value="1" title="有">
                                                <input type="radio" class="to_be_processed" name="to_be_processed" value="2" title="无">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">受让人：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="buyer_people" id="buyer_people" placeholder="请输入受让人" autocomplete="off"  class="layui-input" value="<{$_GET['buyer_people']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">发布人：</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="release_people" id="release_people" placeholder="请输入发布人" autocomplete="off"  class="layui-input" value="<{$_GET['release_people']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">求购状态：</label>
                                            <div class="layui-input-inline" style="width: 400px;">
                                                <input type="radio" class="purchase_status" name="purchase_status" value="1" title="求购中">
                                                <input type="radio" class="purchase_status" name="purchase_status" value="2" title="已完成">
                                                <input type="radio" class="purchase_status" name="purchase_status" value="3" title="已终止">
                                              </div>
                                        </div>

                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button class="layui-btn layui-btn-primary" onclick="resetSearch()">重置</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-card-body ">
                    <div class="layui-colla-item">
                        <button class="layui-btn" style="background-color:#1E90FF;" lay-submit="" lay-filter="create_purchase">发布求购信息</button>
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
            {field: 'discount', title: '折扣', width: 80},
            {field: 'total_amount', title: '求购债权总额', width: 120},
            {field: 'budget_amount', title: '预算金额', width: 100},
            {field: 'purchased_amount', title: '已购债权总额', width: 120},
            {field: 'trading_amount', title: '进行中债权总额', width: 120},
            {field: 'use_amount', title: '已用预算金额', width: 120},
            {field: 'traded_num', title: '成交单数', width: 80},
            {field: 'trading_num', title: '待处理单数', width: 80},
            {field: 'buyer_user', title: '受让人', width: 150},
            {field: 'start_time', title: '发布时间', width: 150},
            {field: 'end_time', title: '截止时间', width: 150},
            {field: 'status_cn', title: '状态', width: 80},
            {title: '操作', toolbar: '#operate',width: 320},
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
            url: '/debtMarket/DebtPurchase/Huiyuan',
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
                        area_id: obj.field.area_id,
                        discount:obj.field.discount,
                        to_be_processed:obj.field.to_be_processed,
                        buyer_people: obj.field.buyer_people.trim(),
                        action_start: obj.field.action_start,
                        action_end: obj.field.action_end,
                        release_people: obj.field.release_people.trim(),
                        purchase_status: obj.field.purchase_status,                        

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
            xadmin.open('发布求购信息', '/debtMarket/debtPurchase/create?area_id=1',800,580);
            return false;
        });

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;

            if (layEvent === 'detail') {
                xadmin.open('详情', '/debtMarket/debtPurchase/Detail?id=' + data.id );
            } else if (layEvent === 'cancel_file') {
                cancel_file(data);
            }
            else if (layEvent === 'auth_access') {
                auth_access(data);
            }
            else if (layEvent === 'auth_fail') {
                auth_fail(data);
            }
            else if (layEvent === 'deal_list') {
                xadmin.open('求购标的信息', '/debtMarket/debtPurchase/DealList?purchase_id=' + data.id );
            }
        });

    });

    function resetSearch() {
       
        $(".to_be_processed").val("0");
        $(".purchase_status").val("0");
        $("#action_start").val("");
        $("#action_end").val("0");
        $("#release_people").val("");
        $("#buyer_people").val("");
        $("#discount").val("");
        $("#area_id").val("0");
        
    }


    function cancel_file(data) {
        layer.confirm('确认要终止吗？', function (index) {
            $.ajax({
                url: '/debtMarket/debtPurchase/stop',
                data: {id:data.id},
                type: "POST",
                dataType:'json',
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
    function auth_access(data) {
        layer.confirm('确认要审核通过吗？', function (index) {
            $.ajax({
                url: '/debtMarket/debtPurchase/auth',
                data: {id:data.id,type:1},
                type: "POST",
                dataType:'json',
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
    function auth_fail(data) {
        layer.confirm('确认要拒绝吗？', function (index) {
            $.ajax({
                url: '/debtMarket/debtPurchase/auth',
                data: {id:data.id,type:0},
                type: "POST",
                dataType:'json',
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




</script>

<script type="text/html" id="operate">
    
    {{# if(d.status == 0){ }}
        <button class="layui-btn" title="审核" lay-event="auth_access">通过</button>
        <button class="layui-btn" title="审核" lay-event="auth_fail">拒绝</button>
    {{# } }}
    {{# if(d.status >= 1 && d.status <= 3){ }}
        {{# if(d.status < 3){ }}
            <button class="layui-btn" title="撤销" lay-event="cancel_file">终止</button>
        {{# } }}
        <button class="layui-btn" title="详情" lay-event="detail">承接记录</button>

    {{# } }}
    <button class="layui-btn" title="详情" lay-event="deal_list">求购标的信息</button>

</script>
</html>