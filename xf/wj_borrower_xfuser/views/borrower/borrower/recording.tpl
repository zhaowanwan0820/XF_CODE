<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>电话录音管理</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
<div class="x-nav">
            <span class="layui-breadcrumb">
                <a href="">催收管理</a>
                <a><cite>电话录音管理</cite></a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8" onclick="location.reload()" title="刷新">
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
                                            <label class="layui-form-label">录音时间</label>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                                <input class="layui-input" name="record_time_start" id="record_time_start" readonly>
                                            </div>
                                            <div class="layui-form-mid">-</div>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                                <input class="layui-input"   name="record_time_end" id="record_time_end" readonly>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">上传时间</label>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                                <input class="layui-input" name="addtime_start" id="addtime_start" readonly>
                                            </div>
                                            <div class="layui-form-mid">-</div>
                                            <div class="layui-input-inline"  style="width: 85px;">
                                                <input class="layui-input"   name="addtime_end" id="addtime_end" readonly>
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="title" class="layui-form-label">公司名称</label>
                                            <div class="layui-input-inline" style="width: 190px">
                                                <select name="company_id"  lay-verify="company_id" id="company_id" style="width:20px">
                                                    <option value="">全部</option>
                                                    <{foreach $company_list as $key => $v}>
                                                    <option value="<{$v['id']}>"><{$v['name']}></option>
                                                    <{/foreach}>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="deal_id" class="layui-form-label">统一识别码</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="tax_number" id="tax_number"   autocomplete="off" class="layui-input" value="<{$_GET['tax_number']}>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding: 0 15px">
                    <button type="button" class="layui-btn layui-btn-warm" onclick="xadmin.open('新增','/borrower/borrower/AddRecording',600,400)"><i class="layui-icon"></i>新增</button>
                </div>
                <div class="layui-card-body">
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;
        laydate = layui.laydate;

        laydate.render({
            elem: '#record_time_start'
        });

        laydate.render({
            elem: '#record_time_end'
        });

        laydate.render({
            elem: '#addtime_start'
        });

        laydate.render({
            elem: '#addtime_end'
        });

        table.render({
            elem           : '#list',
            toolbar        : '#toolbar',
            defaultToolbar : ['filter'],
            page           : true,
            limit          : 10,
            limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
            autoSort       : false,
            cols:[[
                {field: 'record_time', title: '录音时间', width: 140},
                {field: 'addtime', title: '上传时间', width: 120},
                {field: 'record_num', title: '上传录音数量', width: 120},
                {field: 'company_name', title: '催收公司名称'},
                {field: 'tax_number', title: '统一识别码', width: 190},
                {field: 'op_user_name', title: '上传人', width: 120},
                {field: 'operate', title: '操作', width: 100}
            ]],
            url      : '/borrower/borrower/recording',
            method   : 'post',
            response :
                {
                    statusName : 'code',
                    statusCode : 0,
                    msgName    : 'info',
                    countName  : 'count',
                    dataName   : 'data'
                }
        });

        form.on('submit(sreach)', function(obj){
            table.reload('list', {
                where :
                    {
                        record_time_start:obj.field.record_time_start.trim(),
                        record_time_end:obj.field.record_time_end.trim(),
                        addtime_start: obj.field.addtime_start.trim(),
                        addtime_end: obj.field.addtime_end.trim(),
                        company_id  : obj.field.company_id,
                        tax_number  : obj.field.tax_number,
                    },
                page:{curr:1}
            });
            return false;
        });



        table.on('tool(list)' , function(obj){
            var layEvent  = obj.event;
            var data      = obj.data;

            if (layEvent === 'edit') {
                xadmin.open('编辑' , '/borrower/company/EditCompany?id='+data.id);
            }else if(layEvent === 'stop_company') {
                start(1,data.id);
            }else if(layEvent === 'start_company') {
                start(0,data.id);
            }
        });
    });
</script>
</html>