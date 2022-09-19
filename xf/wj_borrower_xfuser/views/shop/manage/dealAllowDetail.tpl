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
                <a href="">商城管理</a>
                <a>
                    <cite>项目白名单</cite>
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
                                            <label class="layui-form-label">项目ID</label>
                                            <div class="layui-input-inline">
                                                <input  name="deal_id" id="deal_id" placeholder="请输入项目ID"  class="layui-input" <{if $_GET['deal_id']}> value="<{$_GET['deal_id']}>" <{/if}>>
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">项目名称</label>
                                            <div class="layui-input-inline">
                                                <input  name="name" id="name" placeholder="请输入项目名称"  class="layui-input" <{if $_GET['name']}> value="<{$_GET['name']}>" <{/if}>>
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">项目类型</label>
                                            <div class="layui-inline layui-show-xs-block">
                                                <select name="deal_type">
                                                    <option value="2" >普惠</option>
                                                    <option value="1" >尊享</option>
                                                    <option value="3" >工厂微金</option>
                                                    <option value="4" >智多新</option>
                                                    <option value="5" >交易所</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <input type="hidden" name = "upload_id" value="<{$upload_id}>">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button  type="button" class="layui-btn layui-btn-primary" onclick="resetSearch()" >重置</button>
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
        var appid = <{$appid}>;
        var area_id = <{$area_id}>;
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'deal_id', title: '项目id', width: 220},
            {field: 'type_cn', title: '项目类型', width: 180},
            {field: 'name', title: '项目名称', width: 240},
            {field: 'status_cn', title: '状态', width: 180},
            {title: '操作', toolbar: '#operate',  width: 220},

        ];

        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],
            where: {
                appid: appid,
                area_id: area_id,
                deal_type: 2,
            },
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/shop/Manage/dealAllowDetail',
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


        form.on('submit(search)', function (obj) {
            table.reload('list', {
                where:
                    {
                        deal_id: obj.field.deal_id,
                        name: obj.field.name,
                        deal_type: obj.field.deal_type,
                        appid:  <{$appid}>,
                        area_id:  <{$area_id}>,

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

            if (layEvent === 'deal_open') {
                deal_open(data);
            }
            else if (layEvent === 'deal_close') {
                deal_close(data);
            }
        });

    });

    function resetSearch() {
        $("#user_id").val("");
        $("#mobile").val("");
    }


    function deal_close(data) {
        layer.confirm('确认要移除吗？', function (index) {
            $.ajax({
                url: '/shop/Manage/editDealStatus',
                data: {id:data.id,status:2},
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

    function deal_open(data) {
        layer.confirm('确认要启用吗？', function (index) {
            $.ajax({
                url: '/shop/Manage/editDealStatus',
                data: {id:data.id,status:1},
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

</script>

<script type="text/html" id="operate">
    {{# if(<{$can_edit}> == 1 && d.status == 2){ }}
    <button class="layui-btn" title="启用" lay-event="deal_open">启用</button>
    {{# } }}
    {{# if(<{$can_edit}> == 1&& d.status == 1){ }}
    <button class="layui-btn" title="移除" lay-event="deal_close">移除</button>
    {{# } }}
</script>
</html>