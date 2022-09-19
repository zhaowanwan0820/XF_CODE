<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>客服录入</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <style type="text/css">
        .layui-form-label {
            width: 190px
        }
    </style>
    <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]--></head>

<body>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body res_div"  >
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                    </table>
                </div>

                <div class="layui-card-body res_div"  >
                    <table class="layui-table layui-form" lay-filter="list" id="list_1">
                    </table>
                </div>

                <div class="layui-card-body res_div"  >
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">催收建议<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form">

                                    <div class="layui-form-item">
                                        <label class="layui-form-label">分类</label>
                                        <div class="layui-input-inline" style="width: 200px;">
                                            <select id="cs_suggest" name="cs_suggest">
                                                <option value="1" <{if $cs_suggest == 1}>selected = "selected"<{/if}> >电催</option>
                                                <option value="2" <{if $cs_suggest == 2}>selected = "selected"<{/if}> >法催</option>
                                            </select>
                                            <input type="hidden" id='cs_suggest_hidden' name="cs_suggest_hidden" value="<{$cs_suggest}>" >
                                            <input type="hidden" id='user_id' name="user_id" value="<{$_GET['user_id']}>" >
                                        </div>
                                    </div>
                                    <div class="layui-form-item">
                                        <label for="L_repass" class="layui-form-label"></label>
                                        <button type="button" class="layui-btn"  onclick="do_add()">保存</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
    layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;
        laydate = layui.laydate;
        var join = [
            {field: 'id', title: '序号', fixed: 'left', width: 80},
            {field: 'product_name', title: '产品名称', width: 180},
            {field: 'number', title: '订单编号', width: 150},
            {field: 'deal_name', title: '借款标题', width: 140},
            {field: 'id', title: '借款编号', width: 140},
            {field: 'loan_amount', title: '借款金额', width: 120},
            {field: 'repay_type', title: '借款期限', width: 80},
            {field: 'un_puy_num', title: '原先锋待还期数', width: 130},
            {field: 'principal', title: '原先锋待还本金和', width: 150},
            {field: 'interest', title: '原先锋待还利息和', width: 150},
            {field: 'user_id', title: '借款人ID', width: 120},
            {field: 'customer_name', title: '借款人姓名', width: 120},
            {field: 'id_number', title: '借款人证件号', width: 160},
            {field: 'phone', title: '借款人手机号', width: 120},
            {field: 'voucher_url_html', title: '放款凭证', width: 120},
            {title: '操作', fixed: 'right',toolbar: '#operate',width: 120},
        ];

        table.render({
            elem: '#list',
            toolbar : '<div>标的信息</div>',
            defaultToolbar: ['filter'],
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/borrower/DealOrder/index01',
            method: 'post',
            where: {user_id:<{$_GET['user_id']}>},
            response:
                {
                    statusName: 'code',
                    statusCode: 0,
                    msgName: 'info',
                    countName: 'countNum',
                    dataName: 'list'
                }
        });

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;
            if (layEvent === 'detail') {
                xadmin.open('详情', '/borrower/DealOrder/repayPlan?deal_id=' + data.id );
            }
        });


        var join1 = [
            {field: 'add_time', title: '维护时间', width: 150},
            {field: 'contact_status_cn', title: '联系状态', width: 80},
            {field: 'question_3_cn', title: '还款意愿', width: 280},
            {field: 'add_user_name', title: '客服姓名', width: 80},
            {field: 'remark', title: '催收记录'}
        ];

        table.render({
            elem: '#list_1',
            toolbar : '<div>维护记录</div>',
            defaultToolbar: ['filter'],
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join1],
            url: '/borrower/DealOrder/callLog',
            method: 'post',
            where: {user_id:<{$_GET['user_id']}>},
            response:
                {
                    statusName: 'code',
                    statusCode: 0,
                    msgName: 'info',
                    countName: 'countNum',
                    dataName: 'list'
                }
        });
    });

//===========================================================

    function do_add() {
        var cs_suggest = $("#cs_suggest").val();
        var cs_suggest_hidden = $("#cs_suggest_hidden").val();
        var user_id = $("#user_id").val();
        if (cs_suggest_hidden == cs_suggest) {
            layer.msg('分类没有变更，无需修改' , {icon:2 , time:2000});
        } else {
            var loading = layer.load(2, {
                shade: [0.3],
                time: 3600000
            });
            $.ajax({
                url:'/borrower/dealOrder/AddCsSuggest',
                type:'post',
                data:{
                    cs_suggest: cs_suggest,
                    user_id: user_id,
                },
                dataType:'json',
                success:function(res){
                    layer.close(loading);
                    if (res['code'] === 0) {
                        layer.msg(res['info'] , {time:1000,icon:1} , function(){
                            location.reload();
                        });
                    } else {
                        layer.alert(res['info']);
                    }
                }
            });
        }
    }
</script>
</body>
<script type="text/html" id="operate">
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>