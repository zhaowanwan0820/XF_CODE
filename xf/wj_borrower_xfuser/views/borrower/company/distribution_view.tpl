<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>分案明细</title>
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
                                    <input type="hidden" name="id" id="id"  value="<{$_GET['id']}>">
                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label">导入状态</label>
                                            <div class="layui-input-inline">
                                                <select name="status" id="status" lay-search="">
                                                    <option value="-1"   >全部</option>
                                                    <option value="1" <{if $_GET['status'] eq '1'}> selected <{/if}> >导入成功</option>
                                                    <option value="2" <{if $_GET['status'] eq '2'}> selected <{/if}> >导入失败</option>
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

                <div class="layui-card-body ">
                    <table class="layui-table layui-form">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>分配ID</th>
                            <th>借款人ID</th>
                            <th>状态</th>
                            <th>备注</th>
                            <th>分配时间</th>
                        </tr>
                        </thead>
                        <{if $listInfo}>
                        <{foreach $listInfo as $k => $v}>
                            <tr>
                                <td><{$v['id']}></td>
                                <td><{$v['distribution_id']}></td>
                                <td><{$v['user_id']}></td>
                                <td><{$v['status_tips']}></td>
                                <td><{$v['remark']}></td>
                                <td><{$v['addtime']}></td>
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



    function reset_form()
    {
        $("#status").val('');
        form.render();
    }
</script>
</html>