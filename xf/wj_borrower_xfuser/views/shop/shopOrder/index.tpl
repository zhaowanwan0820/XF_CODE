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
                <a href="">商城化债管理</a>
                <a>
                    <cite>订单管理</cite>
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
                                            <label class="layui-form-label">选择商城</label>
                                            <div class="layui-inline layui-show-xs-block" style="width: 190px;">
                                                <select name="appid" required lay-verify="required" lay-filter="out_area"  >
                                                    <option value=0 >请选择</option>
                                                    <{foreach $shopList as $key => $val}>
                                                    <option value="<{$val['id']}>" ><{$val['name']}></option>
                                                    <{/foreach}>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">审核状态</label>
                                            <div class="layui-input-inline">
                                                <select name="auth_status" id="auth_status" lay-search="">
                                                  <option value="-1">全部</option>
                                                  <option value="0">待审核</option>
                                                  <option value="1">审核通过</option>
                                                  <option value="2">审核拒绝</option>
                                                  <option value="3">撤销</option>

                                                </select>
                                              </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">录入条数</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="action_num" id="name" placeholder="录入条数" autocomplete="off" id="username" class="layui-input" value="<{$_GET['username']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">录入人</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="action_name" id="name" placeholder="请输入录入人" autocomplete="off" id="username" class="layui-input" value="<{$_GET['username']}>">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">录入时间</label>
                                            <div class="layui-input-inline">
                                              <input class="layui-input" placeholder="开始时间" name="action_start" id="action_start" readonly>
                                            </div>
                                            <div class="layui-input-inline">
                                              <input class="layui-input" placeholder="截止时间" name="action_end" id="action_end" readonly>
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">审核人</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="auth_name" id="auth_name" placeholder="请输入审核人" autocomplete="off" id="username" class="layui-input" value="<{$_GET['username']}>">
                                            </div>
                                        </div>
                                       
                                        <div class="layui-inline">
                                            <label class="layui-form-label">审核时间</label>
                                            <div class="layui-input-inline">
                                              <input class="layui-input" placeholder="开始时间" name="auth_start" id="auth_start" readonly>
                                            </div>
                                            <div class="layui-input-inline">
                                              <input class="layui-input" placeholder="截止时间" name="auth_end" id="auth_end" readonly>
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
                    <div class="layui-colla-item">
                        <button class="layui-btn" lay-submit="" lay-filter="user_condition_upload">订单导入</button>
                        <i class="layui-icon layui-icon-about"
                           style="font-size: 14px; color: #9F9F9F; margin-left: 20px;vertical-align:bottom;">&nbsp;流程：录入（待审核、已撤回
                            终）->审核（审核已通过、审核未通过 终）->完成</i>

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
            {field: 'name', title: '商城名称', width: 100},
            {field: 'total_amount', title: '总订单金额', width: 120},
            {field: 'total_integral', title: '总订单使用债权积分', width: 140},
            {field: 'total_num', title: '总录入条数', width: 100},
            {field: 'action_user_name', title: '录入人', width: 120},
            {field: 'addtime', title: '录入时间', width: 150},
            {field: 'auth_user_name', title: '审核人', width: 120},
            {field: 'auth_time', title: '审核时间', width: 150},
            {field: 'status_cn', title: '状态', width: 120},
            {title: '操作', toolbar: '#operate',width: 190},
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
            url: '/shop/ShopOrder/index',
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
                        appid: obj.field.appid,
                        auth_status:obj.field.auth_status,
                        action_num:obj.field.action_num,
                        action_name: obj.field.action_name.trim(),
                        action_start: obj.field.action_start,
                        action_end: obj.field.action_end,
                        auth_name: obj.field.auth_name.trim(),
                        auth_start: obj.field.auth_start,
                        auth_end: obj.field.auth_end
                        

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

            if (layEvent === 'detail') {
                xadmin.open('详情', '/shop/ShopOrder/Detail?upload_id=' + data.id );
            } else if (layEvent === 'cancel_file') {
                cancel_file(data);
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
    {{# if(d.status == 0 ){ }}
    <button class="layui-btn" title="撤销" lay-event="cancel_file">撤回</button>
    {{# } }}
    {{# if(d.status == 0 && <{$can_auth}> == 1){ }}
    <button id="doh"  class="layui-btn" title="审核" lay-event="auth_file">审核</button>
    {{# } }}
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>