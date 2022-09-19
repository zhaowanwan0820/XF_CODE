<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>部分还款列表</title>
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
                    <cite>部分还款列表</cite></a>
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
                            <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form">
                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label">序号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="id" placeholder="请输入序号" id="selectKey" autocomplete="off" class="layui-input" value="<{$_GET['id']}>">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">状态</label>
                                            <div class="layui-input-inline">
                                                <select name="pay_status" lay-search="">
                                                    <option value="">全部</option>
                                                    <{foreach $statusArr as $k => $v}>
                                                    <option value="<{$k}>" <{if !empty($_GET['pay_status']) && $_GET['pay_status'] == $k}> selected <{/if}>><{$v}></option>
                                                    <{/foreach}>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">计划还款日期</label>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="请选择开始日期" name="start" id="start" lay-verify="start" readonly value="<{$_GET['start']}>">
                                            </div>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="请选择截止日期" name="end" id="end" lay-verify="start" readonly  value="<{$_GET['end']}>">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <div class="layui-input-inline">
                                                <button class="layui-btn" lay-submit lay-filter="submit-form">立即搜索</button>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="layui-card-header">
                    <button class="layui-btn" onclick="xadmin.open('普惠部分还款录入','/user/PartialRepay/AddPartial')">
                        <i class="layui-icon"></i>普惠部分还款录入</button>
                </div>
                <div class="layui-card-body ">
                    <table class="layui-table layui-form">
                        <thead>
                        <tr>
                            <th>序号</th>
                            <th>还款总额</th>
                            <th>导入成功</th>
                            <th>成功金额合计</th>
                            <th>导入失败</th>
                            <th>失败金额合计</th>
                            <th>录入方</th>
                            <th>付款方</th>
                            <th>计划还款时间</th>
                            <th>录入时间</th>
                            <th>还款凭证</th>
                            <th>状态</th>
                            <th>完成时间</th>
                            <th>备注</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <{foreach $listInfo as $k => $v}>
                            <tr>
                                <td><{$v['id']}></td>
                                <td><{$v['total_repayment']}></td>
                                <td><{$v['success_number']}></td>
                                <td><{$v['total_successful_amount']}></td>
                                <td><{$v['fail_number']}></td>
                                <td><{$v['total_fail_amount']}></td>
                                <td><{$v['admin_username']}></td>
                                <td><{$v['pay_user']}></td>
                                <td><{$v['pay_plan_time']}></td>
                                <td><{$v['addtime']}></td>
                                <td><{if !empty($v['proof_url'])}><a href="/<{$v['proof_url']}>" download><button class="layui-btn layui-btn-primary">下载</button></a><{/if}></td>
                                <td><{$v['statusName']}></td>
                                <td><{$v['task_success_time']}></td>
                                <td><{$v['remark']}></td>
                                <td>
                                    <{if $v['status'] == 1}>
                                    <!--待审核-->
                                        <a title="查看详情">
                                            <button class="layui-btn layui-btn-primary" onclick="xadmin.open('查看详情','/user/PartialRepay/PHPartialDetail?partial_repayment_id=<{$v['id']}>')">详情</button>
                                        </a>
                                        <{if ItzUtil::button_exists("/user/PartialRepay/EditPartialProof")}>
                                        <a title="编辑" >
                                            <button class="layui-btn layui-btn-normal" onclick="xadmin.open('编辑','/user/PartialRepay/EditPartialProof?id=<{$v['id']}>')">编辑</button>
                                        </a>
                                        <{/if}>
                                        <{if ItzUtil::button_exists("/user/PartialRepay/PHDelRepay")}>
                                        <a title="移除" >
                                            <button class="layui-btn layui-btn-danger" onclick="member_join(<{$v['id']}>,6)">移除</button>
                                        </a>
                                        <{/if}>
                                        <{if ItzUtil::button_exists("/user/PartialRepay/PHAllowRepay")}>
                                        <a title="通过" >
                                            <button class="layui-btn " onclick="member_join(<{$v['id']}>,2,<{$v['fail_status']}>)">通过</button>
                                        </a>
                                        <{/if}>
                                        <{if ItzUtil::button_exists("/user/PartialRepay/PHRefuseEdit")}>
                                        <a title="拒绝" >
                                            <button class="layui-btn layui-btn-warm" onclick="xadmin.open('拒绝原因','/user/PartialRepay/PHRefuseEdit?partial_repayment_id=<{$v['id']}>')">拒绝</button>
                                        </a>
                                        <{/if}>
                                    <{elseif $v['status'] == 2}>
                                    <!--审核已通过-->
                                    <a title="查看详情">
                                        <button class="layui-btn layui-btn-primary" onclick="xadmin.open('查看详情','/user/PartialRepay/PHPartialDetail?partial_repayment_id=<{$v['id']}>')">详情</button>
                                    </a>
                                    <{elseif $v['status'] == 3}>
                                    <!--审核未通过-->
                                        <a title="查看详情">
                                            <button class="layui-btn layui-btn-primary" onclick="xadmin.open('查看详情','/user/PartialRepay/PHPartialDetail?partial_repayment_id=<{$v['id']}>')">详情</button>
                                        </a>
                                        <{if ItzUtil::button_exists("/user/PartialRepay/PHDelRepay")}>
                                        <a title="移除" >
                                            <button class="layui-btn layui-btn-danger" onclick="member_join(<{$v['id']}>,6)">移除</button>
                                        </a>
                                        <{/if}>
                                    <{elseif in_array($v['status'],[4,5]) }>
                                    <!--还款成功 还款失败-->
                                    <a title="查看详情">
                                        <button class="layui-btn layui-btn-primary" onclick="xadmin.open('查看详情','/user/PartialRepay/PHPartialDetail?partial_repayment_id=<{$v['id']}>')">详情</button>
                                    </a>
                                    <{/if}>
                                </td>
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
</body>
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
            });</script>
<script>layui.use(['form', 'layer'],
            function() {
                $ = layui.jquery;
                var form = layui.form,
                        layer = layui.layer;
                //监听提交
                form.on('submit(submit-form)',
                        function(data) {
                            if ($("#start").val() > $("#end").val() ) {
                              layer.alert("计划还款时间选择错误");
                              return false;
                            }
                        });
            });
    /*用户-停用*/
    function member_join(id,status,fail_status=2){
        if(status == 2){
            if(fail_status == 1){
                var str = '只能通过导入成功的信息，确定要通过？';
            }else{
                var str = '确定要通过？';
            }
            var url = "/user/PartialRepay/PHAllowRepay?status="+status+"&partial_repayment_id="+id ;
        }else if(status == 6){
            var str = '移除后将不再展示确定要移除？';
            var url = "/user/PartialRepay/PHDelRepay?status="+status+"&partial_repayment_id="+id ;
        }
        layer.confirm(str,function(index){
            //发异步把用户状态进行更改
            $.ajax({
                url: url ,
                type:"GET",
                success: function (res) {
                    if(res.code == 0){
                        layer.alert(res.info,{icon: 1},function(){
                            location.reload();
                        });
                    }else{
                        layer.alert(res.info,{icon: 5},function(){
                            location.reload();
                        });
                    }
                }
            })
        });
    }
</script>
</html>