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
                <a>
                    <cite>电话录音管理</cite></a>
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
                                            <label for="deal_id" class="layui-form-label">公司名称</label>
                                            <div class="layui-input-inline" style="width: 190px">
                                                <select name="company_id"  lay-verify="company_id" id="company_id" style="width:20px">
                                                    <option value="">全部</option>
                                                    <{foreach $company_list as $key => $v}>
                                                    <option value="<{$v['id']}>" <{if $_GET['company_id'] eq $v['id']}> selected <{/if}>><{$v['name']}></option>
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
                                            <button class="layui-btn" lay-submit="" lay-filter="demo1">立即搜索</button>
                                            <button type="button" class="layui-btn layui-btn-primary" onclick="reset_form()">重置</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-card-header">
                    <button class="layui-btn" onclick="xadmin.open('新增','/borrower/company/AddDistribution',800,600)">
                        <i class="layui-icon"></i>新增</button>
                </div>
                <div class="layui-card-body ">
                    <table class="layui-table layui-form">
                        <thead>
                        <tr>
                            <th>录音时间</th>
                            <th>上传时间</th>
                            <th>上传录音数量</th>
                            <th>催收公司名称</th>
                            <th>统一识别码</th>
                            <th>上传人</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <{if $listInfo}>
                        <{foreach $listInfo as $k => $v}>
                            <tr>
                                <td><{$v['record_time']}></td>
                                <td><{$v['addtime']}></td>
                                <td><{$v['success_num']}></td>
                                <td><{$v['company_name']}></td>
                                <td><{$v['tax_number']}></td>
                                <td><{$v['op_user_name']}></td>
                                <td class="td-manage">
                                    <button class="layui-btn layui-btn-warm" title="分案明细" onclick="xadmin.open('分案明细','/borrower/company/distributionView?id=<{$v['id']}>',800,600)"  href="javascript:;" >下载</button>
                                </td>
                            </tr>
                            <{/foreach}>
                        <{else}>
                            <tr><td colspan="11" align="center">暂无数据</td></tr>
                        <{/if}>
                        </tbody>
                    </table>
                </div>
                <div class="layui-card-body ">
                    <div class="page">
                        <div class="in-ul">
                            <{$pages}>
                            <div class="layui-inline">
                                <label class="layui-form-label">总数据量:</label>
                                <span><{$count}></span>
                            </div>
                        </div>
                    </div>
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
    });

    function start(val , id) {
        if (val == 1) {
            var str = '确认审核通过吗？';
        } else if (val == 2) {
            var str = '确认审核拒绝吗？';
        } else if (val == 3) {
            var str = '确认终止分案吗？';
        }
        layer.confirm(str,
            function(index) {
                $.ajax({
                    url:'/borrower/company/Auth',
                    type:'post',
                    data:{
                        'id':id,
                        'status':val
                    },
                    dataType:'json',
                    success:function(res) {
                        if (res['code'] === 0) {
                            layer.msg(res['info'] , {time:1000,icon:1} , function(){
                                location.reload();
                                var index = parent.layer.getFrameIndex(window.name);
                                parent.layer.close(index);
                            });
                        } else {
                            layer.alert(res['info']);
                        }
                    }
                });
            });
    }

    function reset_form()
    {
        $("#company_id").val('');
        $("#view_status").val('');
        $("#type").val('');
        form.render();
    }
</script>
</html>