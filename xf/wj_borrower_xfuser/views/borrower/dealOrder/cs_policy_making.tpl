<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>维护</title>
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
                <div class="layui-card-body ">
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">借款人信息<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" >
                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label" style="width: 80px;">用户ID</label>
                                            <div class="layui-input-inline">
                                                <div id="countNum" style="margin-top: 8px;"><{$user_id}></div>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label" style="width: 80px;">姓名</label>
                                            <div class="layui-input-inline">
                                                <div id="countNum" style="margin-top: 8px;"><{$real_name}></div>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label" style="width: 80px;">证件号</label>
                                            <div class="layui-input-inline">
                                                <div id="countNum" style="margin-top: 8px;"><{$idno}></div>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label" style="width: 80px;">手机号</label>
                                            <div class="layui-input-inline">
                                                <div id="total_loan_amount" style="margin-top: 8px;"><{$mobile}></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

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
                            <h2 class="layui-colla-title">新增维护记录<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form">

                                    <div class="layui-form-item" id="question_1_2_div"  >
                                        <label class="layui-form-label"><span class="x-red">*</span>1.联系状态</label>
                                        <div class="layui-input-inline" style="width: 500px;">
                                            <input type="radio" class="contact_status" lay-filter="contact_status" name="contact_status" value="1" title="可联">
                                            <input type="radio" class="contact_status" lay-filter="contact_status" name="contact_status" value="2" title="空号">
                                            <input type="radio" class="contact_status" lay-filter="contact_status" name="contact_status" value="3" title="停机">
                                            <input type="radio" class="contact_status" lay-filter="contact_status" name="contact_status" value="4" title="关机">
                                            <input type="radio" class="contact_status" lay-filter="contact_status" name="contact_status" value="5" title="响铃未接通">
                                            <input type="radio" class="contact_status" lay-filter="contact_status" name="contact_status" value="6" title="接通后挂机">
                                        </div>
                                    </div>

                                    <div class="layui-form-item">
                                        <label class="layui-form-label">
                                            <span class="x-red">*</span>2.是否本人接听</label>
                                        <div class="layui-input-inline" style="width: 500px;">
                                            <input type="radio" class="question_2" lay-filter="question_2" name="question_2" value="1" title="是">
                                            <input type="radio" class="question_2" lay-filter="question_2" name="question_2" value="2" title="否">
                                        </div>
                                    </div>

                                    <div class="layui-form-item">
                                        <label class="layui-form-label">
                                            <span class="x-red">*</span>3.客户状态</label>
                                        <div class="layui-input-inline" style="width: 500px;">
                                            <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="1" title="本人失联，无法代偿（无还款意愿）">
                                            <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="2" title="质疑合同、金额（恶意逃废债）">
                                            <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="3" title="承认借款，无力偿还（有还款意愿）">
                                            <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="4" title="承认借款，积极筹措（有还款意愿）">
                                            <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="5" title="恶意拖欠，敷衍跳票（恶意逃废债）">
                                            <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="6" title="其他">
                                        </div>
                                    </div>

                                    <div class="layui-form-item" id="other_div" style="display: none;">
                                        <label for="other" class="layui-form-label">
                                            <span class="x-red star"></span>其他</label>
                                        <div class="layui-input-inline">
                                            <textarea id="other" name="other" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                        </div>
                                    </div>


                                    <div class="layui-form-item">
                                        <label for="remark" class="layui-form-label">
                                            <span class="x-red">*</span>4.催收记录</label>
                                        <div class="layui-input-inline">
                                            <textarea id="remark" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                        </div>
                                    </div>
                                    <input type="hidden" id='user_id' name="user_id" value="<{$_GET['user_id']}>" >
                                    <div class="layui-form-item">
                                        <label for="L_repass" class="layui-form-label"></label>
                                        <button type="button" class="layui-btn"  onclick="do_add()">提交维护申请</button>
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
            {field: 'add_user_name', title: '客服姓名', width: 120},
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

        form.on('radio(question_3)', function(data){
            var val=data.value;
            if (val == 6) {
                $("#other_div").show();
                $("#other_div").val('');
            } else {
                $("#other_div").hide();
            }
        });

    });

//===========================================================

    function do_add() {
        var contact_status = $(".contact_status:checked").val();
        var question_2 = $(".question_2:checked").val();
        var question_3 = $(".question_3:checked").val();
        var other = $("#other").val();
        var remark = $("#remark").val();
        var user_id = $("#user_id").val();
        if (contact_status=='' || question_2=='' || question_3=='' || remark==''  || user_id=='' ) {
            layer.msg('必选项不能为空' , {icon:2 , time:2000});
        }else if(question_3 == 6 && other==''){
            layer.msg('请输入用户的其他状态' , {icon:2 , time:2000});
        } else {
            var loading = layer.load(2, {
                shade: [0.3],
                time: 3600000
            });
            $.ajax({
                url:'/borrower/dealOrder/AddCallLog',
                type:'post',
                data:{
                    contact_status: contact_status,
                    question_2: question_2,
                    question_3: question_3,
                    other: other,
                    remark: remark,
                    user_id: user_id,
                    id_type: 1,
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