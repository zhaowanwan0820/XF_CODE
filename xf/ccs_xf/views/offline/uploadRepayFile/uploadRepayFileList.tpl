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
                <a href="">工场微金数据录入</a>
                <a>
                    <cite>还款计划录入</cite>
                </a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
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
                                            <label class="layui-form-label">录入时间</label>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="开始时间" name="start" id="start" <{if $_GET['start']}> value="<{$_GET['start']}>" <{/if}> readonly>
                                            </div>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="截止时间" name="end" id="end" <{if $_GET['end']}> value="<{$_GET['end']}>" <{/if}> readonly>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">审核状态</label>
                                            <div class="layui-inline layui-show-xs-block">
                                                <select name="auth_status" id="auth_status">
                                                    <option value="0" >全部</option>
                                                    <option value="1" <{if $_GET['auth_status'] == 1}>selected = "selected"<{/if}>>待审核</option>
                                                    <option value="2" <{if $_GET['auth_status'] == 2}>selected = "selected"<{/if}>>已通过</option>
                                                    <option value="3" <{if $_GET['auth_status'] == 3}>selected = "selected"<{/if}>>已拒绝</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <input type="hidden" name="p" value="<{$_GET['p']}>">
                                                <button class="layui-btn" lay-submit="" lay-filter="search">立即搜索</button>
                                                <button  type="button" class="layui-btn layui-btn-primary" onclick="resetSearch()" >重置</button>
                                                <{if $daochu_status == 0}>
                                                <button type="button" class="layui-btn layui-btn-danger" onclick="daochu()">导出</button>
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
                    <div class="layui-colla-item">
                        <button class="layui-btn" lay-submit="" lay-filter="user_condition_upload">还款计划录入</button>
                        <i class="layui-icon layui-icon-about" style="font-size: 14px; color: #F34743; margin-left: 20px;vertical-align:bottom;">&nbsp;说明：请务必先确保对应出借记录【入库成功】，再录入还款计划。</i>
                    </div>
                </div>

                <div class="layui-card-body ">
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                </div>
<input type="hidden" id="p">
            </div>
        </div>
    </div>
</div>
</body>
<script>
    layui.use(['laydate','layer' ,'table', 'form'], function(){
        var laydate = layui.laydate;
        var form = layui.form;
        var table   = layui.table;
        var p= <{$p}>;
        table.render({
            elem           : '#list',
            toolbar        : '#toolbar',
            defaultToolbar : ['filter'],
            where: {
                p: p,
            },
            page           : true,
            limit          : 10,
            limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
            autoSort       : false,
            cols:[[
                {
                    field : 'id',
                    title : '序号',
                    fixed : 'left',
                    width : 80
                },
                {
                    field : 'total_amount',
                    title : '录入总金额',
                    width : 120
                },
                {
                    field : 'success_num',
                    title : '录入成功条数',
                    width : 120
                },
                {
                    field : 'fail_num',
                    title : '录入失败条数',
                    width : 120
                },
                {
                    field : 'success_capital_amount',
                    title : '录入成功在途本金',
                    width : 150
                },
                {
                    field : 'success_interest_amount',
                    title : '录入成功在途利息',
                    width : 150
                },
                {
                    field : 'fail_capital_amount',
                    title : '录入失败在途本金',
                    width : 150
                },
                {
                    field : 'fail_interest_amount',
                    title : '录入失败在途利息',
                    width : 150
                },
                {
                    field : 'handle_success_capital_amount',
                    title : '执行成功在途本金',
                    width : 150
                },
                {
                    field : 'handle_success_interest_amount',
                    title : '执行成功在途利息',
                    width : 150
                },
                {
                    field : 'handle_fail_capital_amount',
                    title : '执行失败在途本金',
                    width : 150
                },
                {
                    field : 'handle_fail_interest_amount',
                    title : '执行失败在途利息',
                    width : 150
                },
                {
                    field : 'handle_success_num',
                    title : '执行成功条数',
                    width : 150
                },
                {
                    field : 'handle_fail_num',
                    title : '执行失败条数',
                    width : 150
                },
                {
                    field : 'action_user_name',
                    title : '录入人',
                    width : 120
                },
                {
                    field : 'addtime',
                    title : '录入时间',
                    width : 150
                },
                {
                    field : 'auth_user_name',
                    title : '审核人',
                    width : 120
                },
                {
                    field : 'auth_time',
                    title : '审核时间',
                    width : 150
                },
                {
                    field : 'status_cn',
                    title : '状态',
                    width : 120
                },

                {
                    title   : '操作',
                    toolbar : '#operate',
                    fixed   : 'right',
                    width   : 140
                },
            ]],
            url      : '/offline/UploadRepayFile/listP'+p,
            method   : 'post',
            done: function (res) {
                if (res.code == 0) {
                    $("#p").val(res.p)
                }
            },
            response :
                {
                    statusName : 'code',
                    statusCode : 0,
                    msgName    : 'info',
                    countName  : 'countNum',
                    dataName   : 'list'
                }
        });

        laydate.render({
            elem: '#start'
        });

        laydate.render({
            elem: '#end'
        });
        /**
         * 触发上传文件功能
         */
        form.on('submit(user_condition_upload)', function(obj){
            xadmin.open('还款计划录入','/offline/uploadRepayFile/UploadOffline3');
            return false;
        });

        form.on('submit(search)', function (obj) {

            table.reload('list', {
                where:
                    {
                        start: obj.field.start,
                        end: obj.field.end,
                        auth_status: obj.field.auth_status,
                    },
                page:{curr:1}
            });
            return false;
        });

        table.on('tool(list)' , function(obj){
            var layEvent  = obj.event;
            var data      = obj.data;

            if (layEvent === 'detail')
            {
                xadmin.open('详情' , '/offline/uploadRepayLog/list?file_id='+data.id+"&p="+data.platform_id);
            } else if (layEvent === 'cancel_file') {
                cancel_file(data);
            }
        });

    });

    function resetSearch() {
        $("#start").val("");
        $("#end").val("");
        $("#auth_status").val("0");
    }


    function do_add() {
        var template = $("#template").val();
        if (template == '') {
            layer.alert('请选择上传文件');
        } else {
            $("#my_form").submit();
        }
    }
    function daochu() {
        var auth_status   = $("#auth_status").val();
        var start         = $("#start").val();
        var end           = $("#end").val();
        var p             = $("#p").val()
        layer.confirm('确认要导出吗？',
            function(index) {
                layer.close(index);
                location.href = "/offline/uploadRepayFile/listP"+p+"?p="+p+"&export=1&auth_status="+auth_status+"&&start="+start+"&end="+end;
            });
    }
    function cancel_file(data) {
        layer.confirm('确认要撤回吗？', function (index) {
            $.ajax({
                url: '/offline/uploadRepayFile/authFileP'+data.platform_id,
                data: {p:data.platform_id,id:data.id,auth_status:3},
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
    {{# if(d.auth_status == 0){ }}
    <button class="layui-btn" title="撤销" lay-event="cancel_file">撤回</button>
    {{# } }}
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>