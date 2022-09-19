<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>用户管理 批量条件上传</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi"/>
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        body{
            border: 0;
            margin: 0;
        }
        .cont{
            position: relative;
            min-height: 100vh;
            padding-bottom: 95px;
            box-sizing: border-box;
        }
        .cont .list {
            height: 65px;
            line-height: 65px;
            border-bottom: 1px solid #F2F2F2;
            text-align: center;
            font-size: 15px;
            background-color: #ffffff;
        }
        .cbtn {
            text-align: center;
            bottom: 0;
            font-size: 16px;
            color: #FFFFFF;
            margin: 25px 0;
            margin-top: 20px;
            width: 150px;
            height: 45px;
            line-height: 45px;
            background: #E0E0E0;
            border-radius: 5px;
            background-color: #009688;
            left: 50%;
            transform: translateX(-50%);
            z-index: 99999;
            position: fixed;
            bottom: 0px;
            
        }
    </style>
</head>

<body>
<div class="layui-fluid">
    

    <div class="layui-row">
        <!-- <form class="layui-form" > -->
           
            <h2 style="margin-left: 40px; margin-bottom: 10px;">出借人信息</h2>

            <div class="layui-form-item" style="font-size: 14px;">
                <label for="name" class="layui-form-label">
                  姓名:
                </label>
                <div class="layui-form-mid">
                    <{$purchaseInfo['real_name']}>
                </div>
                <label for="name" class="layui-form-label">
                    电话:
                </label>
                <div class="layui-form-mid">
                    <{$purchaseInfo['mobile_phone']}>
                </div>
            </div>

            <div class="layui-form-item" style="font-size: 14px;">
                <label for="name" class="layui-form-label">
                  身份证号:
                </label>
                <div class="layui-form-mid">
                    <{$purchaseInfo['idno']}>
                </div>
                <label for="name" class="layui-form-label">
                    用户ID:
                </label>
                <div class="layui-form-mid">
                    <{$purchaseInfo['user_id']}>
                </div>
            </div>
            
            <div class="layui-form-item" style="font-size: 14px;">
                
                <label for="name" class="layui-form-label">
                    银行卡号:
                </label>
                <div class="layui-form-mid">
                    <{$purchaseInfo['bank_card']}>
                </div>
                <label for="name" class="layui-form-label">
                    收款银行:
                  </label>
                  <div class="layui-form-mid">
                      <{$purchaseInfo['bank_name']}>
                  </div>
            </div>

            
            <h2 style="margin-left: 40px; margin-bottom: 10px;">收购信息</h2>

            <div class="layui-form-item" style="font-size: 14px;">
                <label for="name" class="layui-form-label">
                  收购金额:
                </label>
                <div class="layui-form-mid">
                    <{$purchaseInfo['purchase_amount']}> 元
                </div>
                <label for="name" class="layui-form-label">
                    收购折扣:
                </label>
                <div class="layui-form-mid">
                    <{$purchaseInfo['discount']}> 折
                </div>
            </div>
            
            <h2 style="margin-left: 40px; margin-bottom: 10px;">合同与凭证</h2>

            <div class="layui-form-item" style="font-size: 14px;">
                <label for="name" class="layui-form-label">
                  债转合同:
                </label>
                <div class="layui-form-mid">
                    <{if !empty($purchaseInfo['contract_url'])}>
                    <a href="<{$purchaseInfo['contract_url']}>" target="view_window" style="color:blue">点击查看></a>
                    <{/if}>
                </div>
                <label for="name" class="layui-form-label">
                  付款凭证:
                </label>
                <{if !empty($purchaseInfo['credentials_url'])}>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$purchaseInfo['credentials_url']}>" width="100px">
                    </div>
                <{/if}>
                <{if $purchaseInfo['pay_status'] == 2 && empty($purchaseInfo['credentials_url'])}>
                    获取中……
                <{/if}>
       
            </div>
            <h2 style="margin-left: 40px; margin-bottom: 10px;">付款信息</h2>
            <div class="layui-form-item" style="font-size: 14px;">
                <table class="layui-table">
                    <colgroup>
                      <col width="80">
                      <col width="50">
                      <col width="80">
                      <col width="50">
                      <col width="60">
                    </colgroup>
                    <thead>
                      <tr>
                        <th>批次号</th>
                        <th>支付金额</th>
                        <th>支付时间</th>
                        <th>支付状态</th>
                        <th>备注</th>
                      </tr> 
                    </thead>
                    <tbody>
                        <{foreach $purchaseInfo['payment_log'] as $key => $value}>
                            <tr>
                                <td id=""><{$value['batch_no']}></td>
                                <td id=""><{$value['purchase_amount']}></td>
                                <td id=""><{$value['add_time']}></td>
                                <td id=""><{$value['status_cn']}></td>
                                <td id=""><{$value['remark']}></td>
                            
                        </tr>
                       <{/foreach}>
                    </tbody>
                  </table>
            </div>
            
            <div>
                <span style="margin-left: 40px; margin-bottom: 10px; font-size: 18px;">债权信息</span> 
                <span style="margin-left: 20px;">当前在途本金合计(仅普惠)：<{$purchaseInfo['wait_capital']}> 元</span>
                <span style="margin-left: 20px;">充提差(仅普惠)：<{$purchaseInfo['recharge_withdrawal_difference']}> 元</span>

            </div>
     
            <div class="layui-card-body" style="margin-bottom: 80px;">
                <table class="layui-table layui-form" lay-filter="list" id="list">
                <label for="L_repass" class="layui-form-label"></label>
               
            </div>
            
           
        <!-- </form> -->
       
    </div>
    <{if $purchaseInfo['status'] == 1 && ($purchaseInfo['pay_status'] == 0 || $purchaseInfo['pay_status'] == 3) }>
    <button  class="layui-btn cbtn" type="button" onclick="confirm()" lay-filter="add" lay-submit="">
        确认付款
    </button>
    <{/if}>
</div>
<script>


    layui.use(['form', 'layer', 'laydate','table'], function () {
        $ = layui.jquery;
        
        var laydate = layui.laydate;
        var form = layui.form;
        var table = layui.table;
        var join = [
            {field: 'id', title: '投资记录ID',  },
            {field: 'name', title: '借款标题', },
            {field: 'borrow_id', title: '借款编号', },
            {field: 'account', title: '在途本金', },
            {field: 'create_time', title: '投资时间', },
        ];

        layer.photos({
            photos: '.img_list'
          });
        table.render({
            elem: '#list',
            // toolbar: '#toolbar',
            defaultToolbar: [''],
            style:"position: absolute",

            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/debtMarket/ExclusivePurchase/UserDebtList?id='+<{$purchaseInfo['id']}>,
            method: 'post',
            response:
                {
                    statusName: 'code',
                    statusCode: 0,
                    msgName: 'info',
                    countName: 'countNum',
                    dataName: 'list'
                }
        });
        laydate.render({
            elem: '#auth_start'
        });

        laydate.render({
            elem: '#auth_end'
        });

        laydate.render({
            elem: '#action_start'
        });

        laydate.render({
            elem: '#action_end'
        });

        form.on('submit(search)', function (obj) {
            table.reload('list', {
                where:
                    {
                        user_id: obj.field.user_id.trim(),
                        real_name: obj.field.real_name.trim(),
                        mobile_phone: obj.field.mobile_phone.trim(),
                        status: obj.field.status,                        
                    },
                page: {
                    curr: 1
                },
            });
            return false;
        });    
       

        form.on('submit(edit_user_bank)', function (obj) {
            xadmin.open('修改用户信息', '/borrower/borrower/editUserBank?user_id=',900,380);
            return false;
        });

        form.on('submit(user_debt_list)', function (obj) {
            xadmin.open('债权明细', '/debtMarket/ExclusivePurchase/index?user_id=',900,380);
            return false;
        });
        



    });

    function confirm() {
        var id = <{$purchaseInfo['id']}>;
        $.ajax({
                    url: '/debtMarket/exclusivePurchase/audit',
                    data: {"id":id},
                    type:"POST",
                    dataType:'json',
                    success: function (res) {
                        
                        if(res.code == 0){
                            layer.alert("发起付款成功……稍后查看结果",
                                    function(data,item){
                                        var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                                        parent.layer.close(index); //再执行关闭
                                        parent.location.reload();
                                    });
                        }else{
                            layer.alert(res.info);
                        }
                    }
                })
    }

    function query_buyer_info() {
        var buyer_user_id = $("#buyer_user_id").val();
        console.log(buyer_user_id);
    }

  
    function add_file() {
        $("#file").click();
    }

    function change_name(name) {
        var string   = name.lastIndexOf("\\");
        var new_name = name.substring(string+1);  
        $("#file_name").html(new_name);
        $("#file_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
    }

    function to_calculate(){
        var total_amount = $("#total_amount").val();
        if(total_amount<0){
            $("#total_amount").val("");
            return false;
        }
        var discount = $("#discount").val();
        if(discount<0 || discount>10){
            $("#discount").val("");
            return false;
        }
        str = Math.round(parseFloat(total_amount * discount * 0.1) * 100) / 100;
        $("#budget_amount").html(str);
    }

    function add_template() {
        $("#template").click();
    }

    function change_template(name) {
        var string = name.lastIndexOf("\\");
        var new_name = name.substring(string + 1);
        $("#template_name").html(new_name);
    }

    function purchase_create() {
        if ($("#doh").hasClass("disabled")) {
            layer.alert('处理中，请勿重复提交');
        }
        var template = $("#template").val();
        if (template == '') {
            layer.alert('请选择上传文件');
        } else {
            $("#doh").addClass("disabled")
            $("#doh").html("上传中...")
            $("#user_condition_form").submit();
        }
    }
</script>
</body>
</html>