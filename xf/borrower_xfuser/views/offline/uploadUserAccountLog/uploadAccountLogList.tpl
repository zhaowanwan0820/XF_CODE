<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>待加入金额详情</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi"/>
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        .layui-table-cell {
            height: inherit;
        }

    </style>
</head>

<body>
<div class="x-nav">
            <span class="layui-breadcrumb">
                <a href="">智多新数据录入</a>
                <a href="">用户待加入金额录入</a>
                <a>
                    <cite>详情</cite>
                </a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38"
       onclick="location.reload()" title="刷新">
        <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
    </a>
</div>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">

        <div class="layui-col-md12">

            <div class="layui-card">
                <div class="layui-card-body">
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" action="">

                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="请输入用户ID" name="old_user_id">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <button type="button" class="layui-btn layui-btn-danger" lay-submit="export"
                                                    lay-filter="export">导出
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-card-body">
                    <{if $auth_status==0}>
                    <button type="button" class="layui-btn" onclick="batchAgree(1)">审核通过</button>
                    <button type="button" class="layui-btn" onclick="batchAgree(2)">审核拒绝</button>
                    <{/if}>
                    <input type="hidden" id="platform_id">
                    <input type="hidden" id="file_id">
                    <div class="layui-tab layui-tab-brief" lay-filter="table-all">
                        <ul class="layui-tab-title">
                            <li data-status="0" class="layui-this">全部 <span id="all"></span></li>
                            <li data-status="1">录入成功 <span id="l_success"></span></li>
                            <li data-status="2">录入失败 <span id="l_fail"></span></li>
                            <li data-status="4">入库成功 <span id="r_success"></span></li>
                            <li data-status="5">入库失败 <span id="r_fail"></span></li
                        </ul>
                    </div>
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                        <script type="text/html" id="status">
                            {{# if(d.status==1){ }}待入库
                            {{# }else if(d.status==2){ }}录入失败
                            {{# }else if(d.status==3){ }}取消
                            {{# }else if(d.status==4){ }}入库成功
                            {{# }else if(d.status==5){ }}入库失败
                            {{# }else{ }}待处理
                            {{# } }}
                        </script>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    layui.use(['form', 'layer', 'table', 'laydate', 'element'], function () {
        form = layui.form;
        layer = layui.layer;
        table = layui.table;
        laydate = layui.laydate;
        element = layui.element
        var file_id = "<{$_GET['file_id']}>"
        var p = <{$_GET['p']}>

        laydate.render({
            elem: '#start'
        });

        laydate.render({
            elem: '#end'
        });

        getList(file_id, p);

        element.on('tab(table-all)', function () {
            var type = $(this).attr('data-status')
            table.reload('list', {
                where: {type:type},
                page:{curr:1}
            })
        })

        form.on('submit(sreach)', function (obj) {
            table.reload('list', {
                where:
                    {
                        old_user_id: obj.field.old_user_id,
                    },
                page:{curr:1}
            });
            return false;
        });

        form.on('submit(export)', function (where) {
            where = where.field
            var p = $("#platform_id").val()
            layer.confirm('确认要根据当前筛选条件导出吗？',
                function (index) {
                    layer.close(index);
                    location.href = "/offline/UploadUserAccountLog/list?execl=1" +
                        "&old_user_id=" + where.old_user_id + "&file_id=" + file_id + "&p=" + p;
                })
        });

        layui.laytpl.toDateString = function(d, format){
            if (!d) {
                return 0;
            }
            var date = new Date(d)
                ,ymd = [
                this.digit(date.getFullYear(), 4)
                ,this.digit(date.getMonth() + 1)
                ,this.digit(date.getDate())
            ]
                ,hms = [
                this.digit(date.getHours())
                ,this.digit(date.getMinutes())
                ,this.digit(date.getSeconds())
            ];

            format = format || 'yyyy-MM-dd HH:mm:ss';

            return format.replace(/yyyy/g, ymd[0])
                .replace(/MM/g, ymd[1])
                .replace(/dd/g, ymd[2])
                .replace(/HH/g, hms[0])
                .replace(/mm/g, hms[1])
                .replace(/ss/g, hms[2]);
        };

        //数字前置补零
        layui.laytpl.digit = function(num, length, end){
            var str = '';
            num = String(num);
            length = length || 2;
            for(var i = num.length; i < length; i++){
                str += '0';
            }
            return num < Math.pow(10, length) ? str + (num|0) : num;
        };
    });

    function getList(file_id, p, type = 0) {
        table.render({
            elem: '#list',
            toolbar: '#toolbar',
            defaultToolbar: ['filter'],
            page: true,
            limit: 10,
            where: {
                p: p,
                file_id: file_id,
                type: type,
            },
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [[
                {field: 'old_user_id', title: 'user_id', width: 180},
                {field: 'wait_amount', title: '待加入金额', width: 180},
                {field: 'status', title: '录入状态', fixed: 'right', width: 180,templet:'#status'},
                {field: 'remark', title: '失败原因', fixed: 'right', width: 180,templet:'<div>{{strReplace(d.remark)}}</div>'},
            ]],
            url: '/offline/uploadUserAccountLog/list',
            method: 'post',
            done: function (res) {
                if (res.code == 0) {
                    $("#all").html('(' + res.all_num + ')')
                    $("#l_success").html('(' + res.l_success_num + ')')
                    $("#l_fail").html('(' + res.l_fail_num + ')')
                    $("#r_success").html('(' + res.r_success_num + ')')
                    $("#r_fail").html('(' + res.r_fail_num + ')')
                    $("#platform_id").val(res.platform_id)
                    $("#file_id").val(res.file_id)
                }
            },
            response:
                {
                    statusName: 'code',
                    statusCode: 0,
                    msgName: 'info',
                    countName: 'count',
                    dataName: 'data'
                }
        });
    }

    function batchAgree(n) {
        var info = n == 1 ? '通过' : '拒绝'
        layer.confirm('是否确定审核'+info,
            function () {
                var p = $("#platform_id").val()
                var id = $("#file_id").val()
                if (!p || !id) {
                    layer.alert('文件不存在');
                    return false;
                }
                $.ajax({
                    url: '/offline/uploadUserAccount/authFileP'+p,
                    data: {p:p,id:id,auth_status:n},
                    type: "POST",
                    success: function (res) {
                        if (res.code == 0) {
                            layer.confirm(res.info, function () {
                                window.parent.location.reload();
                            })
                        } else {
                            layer.alert(res.info);
                        }
                    }
                });
            })
    }
    function strReplace(str) {
        if (str == 0) {
            return '';
        }
        return str.replace(/,/g,"<br>")
    }

</script>
</html>