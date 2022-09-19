<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>分案列表</title>
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
                <a href="">分案管理</a>
                <a>
                    <cite>分案列表</cite></a>
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
                                        <!--div class="layui-inline">
                                            <label for="deal_id" class="layui-form-label">第三方公司名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="company_name" id="company_name" placeholder="请输入第三方公司名称" autocomplete="off" class="layui-input" value="<{$_GET['company_name']}>">
                                            </div>
                                        </div-->

                                        <div class="layui-inline">
                                            <label for="deal_id" class="layui-form-label">第三方公司名称</label>
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
                                            <label class="layui-form-label">当前状态</label>
                                            <div class="layui-input-inline" style="width: 190px">
                                                <select name="view_status" id="view_status" lay-search="" style="width:20px">
                                                    <option value="-1" >全部</option>
                                                    <option value="1" <{if $_GET['view_status'] eq '1'}> selected <{/if}> >生效中</option>
                                                    <option value="2" <{if $_GET['view_status'] eq '2'}> selected <{/if}> >已失效</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">分案类型</label>
                                            <div class="layui-input-inline" style="width: 190px">
                                                <select name="type" id="type" lay-search="" style="width:20px">
                                                    <option value="" >全部</option>
                                                    <option value="0" <{if $_GET['type'] eq '0'}> selected <{/if}> >个人借款人</option>
                                                    <option value="1" <{if $_GET['type'] eq '1'}> selected <{/if}> >担保企业</option>
                                                </select>
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
                            <th>ID</th>
                            <th>第三方公司名称</th>
                            <th>分案类型</th>
                            <th>分案成功人数</th>
                            <th>分案失败人数</th>
                            <th>当前状态</th>
                            <th>生效时间</th>
                            <th>失效时间</th>
                            <th>分案文件</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <{if $listInfo}>
                        <{foreach $listInfo as $k => $v}>
                            <tr>
                                <td><{$v['id']}></td>
                                <td><{$v['company_name']}></td>
                                <td><{$v['type_cn']}></td>
                                <td><{$v['success_num']}></td>
                                <td><{$v['fail_num']}></td>
                                <td><{$v['view_status_cn']}></td>
                                <td><{$v['start_time_tips']}></td>
                                <td><{$v['end_time_tips']}></td>
                                <td><{$v['file_path']}></td>
                                <td class="td-manage">
                                    <button class="layui-btn layui-btn-warm" title="分案明细" onclick="xadmin.open('分案明细','/borrower/company/distributionView?id=<{$v['id']}>',800,600)"  href="javascript:;" >分案明细</button>
                                    <{if $v['status'] eq '0' }>
                                    <a title="审核通过" onclick="start(1,<{$v['id']}>)" href="javascript:;">
                                        <button class="layui-btn">审核通过</button>
                                    </a>
                                    <a title="审核拒绝" onclick="start(2,<{$v['id']}>)" href="javascript:;">
                                        <button class="layui-btn layui-btn-danger">审核拒绝</button>
                                    </a>
                                    <{else if $v['status'] eq '1'}>
                                    <a title="终止分案" onclick="start(3,<{$v['id']}>)" href="javascript:;">
                                        <button class="layui-btn layui-btn-normal">终止分案</button>
                                    </a>
                                    <{/if}>
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
    layui.use(['laydate', 'form'] , function(){
        form = layui.form;
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