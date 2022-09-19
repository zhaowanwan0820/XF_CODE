<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>部分还款查看详情</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <script src="<{$CONST.jsPath}>/jquery-2.1.4.min.js"></script>
</head>

<body>
<div class="x-nav">
            <span class="layui-breadcrumb">
                <a href="">普惠还款管理</a>
                <a>
                    <cite>部分还款查看详情</cite></a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
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
                            <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form">
                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款标题</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name" placeholder="请输入借款标题" autocomplete="off" class="layui-input" value="<{$_GET['name']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">投资记录ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_loan_id" placeholder="请输入投资记录ID" autocomplete="off" class="layui-input" value="<{$_GET['deal_loan_id']}>">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input" value="<{$_GET['user_id']}>">
                                            </div>
                                        </div>
                                        <{foreach $statusName as $key => $val}>
                                                    <div><{$val}></div>
                                                    <{/foreach}>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">导入状态</label>
                                            <div class="layui-input-inline">
                                                <select name="pay_status">
                                                    <option value="">全部</option>
                                                    <{foreach $statusArr as $key => $val}>
                                                    <option value="<{$key}>" <{if $_GET['pay_status'] == $key}> selected <{/if}>><{$val}></option>
                                                    <{/foreach}>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">还款状态</label>
                                            <div class="layui-input-inline">
                                                <select name="repay_status">
                                                    <option value="" selected="selected">全部</option>
                                                    <option value="1" <{if $_GET['repay_status'] == 1}> selected <{/if}>>待还</option>
                                                    <option value="2" <{if $_GET['repay_status'] == 2}> selected <{/if}>>已还</option>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <div class="layui-input-inline">
                                                <input type="hidden"  name="partial_repayment_id" lay-skin="primary" lay-filter="father" value="<{$_GET['partial_repayment_id']}>">
                                                <button class="layui-btn" lay-submit lay-filter="submit-form">立即搜索</button>
                                                <button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit-form-export">导出</button>
                                            </div>
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
                            <th>序号</th>
                            <th>借款标题</th>
                            <th>到期日</th>
                            <th>投资记录ID</th>
                            <th>用户ID</th>
                            <th>还款金额</th>
                            <th>导入状态</th>
                            <th>实际还款时间</th>
                            <th>还款状态</th>
                            <th>失败原因</th>
                        </tr>
                        </thead>
                        <tbody>
                        <{foreach $listInfo as $k => $v}>
                            <tr>
                                <td><{$v['id']}></td>
                                <td><{$v['name']}></td>
                                <td><{$v['end_time']}></td>
                                <td><{$v['deal_loan_id']}></td>
                                <td><{$v['user_id']}></td>
                                <td><{$v['repay_money']}></td>
                                <td><{$v['statusName']}></td>
                                <td><{$v['repay_yestime']}></td>
                                <td><{$v['repay_status']}></td>
                                <td><{$v['remark']}></td>
                            </tr>
                            <{/foreach}>
                        </tbody>
                    </table>
                </div>
                <div class="layui-card-body ">
                    <div class="page">
                        <div class="in-ul">
                            <{$pages}>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>layui.use(['laydate', 'form'],
            function() {
                var laydate = layui.laydate;
                //执行一个laydate实例
                laydate.render({
                    elem: '#start' //指定元素
                });
                //执行一个laydate实例
                laydate.render({
                    elem: '#end' //指定元素
                });
            });
    layui.use(['form', 'layer'],
            function() {
            $ = layui.jquery;
            var form = layui.form,
            layer = layui.layer;
            //监听提交
            form.on('submit(submit-form-export)',
                    function(data) {
                        layer.confirm("确认导出吗？",function(index){
                            $.ajax({
                                url: "/user/PartialRepay/PHPartialDetail?export=1" ,
                                type:"GET",
                                data: data.field,
                                success: function (res) {
                                    //window.open(this.url);
                                    window.location.href = this.url;
                                    layer.close(index);
                                }
                            })
                        });
                        return false;
                    });
            });
</script>
</body>
</html>