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
    <![endif]--></head>

<body>
<div class="layui-fluid">
    <div class="layui-row">
        <form class="layui-form" >
           
            <h2 style="margin-left: 40px; margin-bottom: 10px;">出借人信息</h2>

            <div class="layui-form-item">
                <label for="name" class="layui-form-label">
                  出借人ID
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="user_id" name="user_id" value="" lay-verify="number"    required="" 
                           autocomplete="off" class="layui-input">
                </div>
             
                <div class="layui-card-inline">

                    <button type="button" class="layui-btn layui-btn-danger"   onclick="user_info()" >查询</button>                    
                </div>
            </div>
            <!-- <div class="layui-form-item">
                <div class="layui-card-body">
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                </div>
            </div> -->

            <div class="layui-form-item">

                <div class="layui-inline" style="margin-left: 40px;">
                    <label class="layui-form-mid">在途本金合计(仅普惠)：</label>
                    <div id="_wait_capital" class="layui-form-mid "></div>    
                    <div  class="layui-form-mid ">元</div>        
                </div>
                <div class="layui-inline" style="margin-left: 40px;">
                    <label class="layui-form-mid">充提差(仅普惠)：</label>
                    <div id="_user_chong_ti_cha" class="layui-form-mid "></div>    
                    <div  class="layui-form-mid ">元</div>        
                </div>
                <table class="layui-table">
                    <colgroup>
                      <col width="100">
                      <col width="200">
                      <col width="200">
                      <col>
                    </colgroup>
                    <thead>
                      <tr>
                        <th>姓名</th>
                        <th>证件号</th>
                        <th>手机号</th>
                        <th>银行卡号</th>
                        <th>操作</th>
                      </tr> 
                    </thead>
                    <tbody>
                        <tr>
                            <td id="_real_name"></td>
                            <td id="_idno"></td>
                            <td id="_mobile_phone"></td>
                            <td id="_bank_card"></td>
                            <td>
                                <button type="button" class="layui-btn layui-btn-danger"  onclick="edit_user_bank()">修改</button>
                            </td>
                       </tr>
                      
                    </tbody>
                  </table>
             
              
            </div>
       
            <h2 style="margin-left: 40px; margin-bottom: 10px;">收购信息</h2>

            <div class="layui-form-item">
                <label class="layui-form-label">收购方式</label>
                <div class="layui-input-inline" style="width: 400px;">
                    <input lay-filter="purchase_type" type="radio" class="purchase_type" name="purchase_type" value="2" title="按金额收购" checked>
                    <input lay-filter="purchase_type" type="radio" class="purchase_type" name="purchase_type" value="1" title="按折扣收购" >
                </div>
            </div>
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">收购金额</label>
                <div class="layui-input-inline">
                    <input step="0.01"  min="0" onkeyup="this.value= this.value.match(/\d+(\.\d{0,2})?/) ? this.value.match(/\d+(\.\d{0,2})?/)[0] : ''"  onBlur="to_calculate(2)"  type="text" id="purchase_amount" name="purchase_amount" lay-verify="number"    required="" lay-verify="required"
                           autocomplete="off" class="layui-input">
                </div>
            </div>
            
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">收购折扣</label>
                <div class="layui-input-inline">
                    <input oninput="if(value>10)value=10;if(value.length>4)value=value.slice(0,4);if(value<0)value=0"  onBlur="to_calculate(1)" type="text" id="discount" name="discount"  lay-verify="number"    required="" lay-verify="required"
                           autocomplete="off" class="layui-input">
                </div>
                <span style="color: red;">折扣范围需在充提差金额的1.9~2折之间</span>
            </div>
          

            <!-- <h2 style="margin-left: 40px; margin-bottom: 10px;">受让人信息</h2>
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">
                  受让人ID
                </label>
                <div class="layui-input-inline">
                    <input onBlur="query_buyer_info()" type="text" id="buyer_user_id" name="buyer_user_id"  lay-verify="number"    required="" lay-verify="required"
                           autocomplete="off" class="layui-input">
                </div>
                <label for="name" class="layui-form-label">
                    受让人名称
                  </label>
                <div class="layui-form-mid layui-word-read" id="buyer_user_name"></div>

             
            </div> -->

    
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label"></label>
                <input type="hidden"  id ="user_chong_ti_cha" name = "user_chong_ti_cha" >
                <button id="confirm_user_info" onclick="confirm_and_send()"  type="button" class="layui-btn">确认收款信息</button>
                <button id="confirm_send" class="layui-btn layui-btn-disabled" lay-filter="add" lay-submit="">
                    发起求购同时下发短信
                </button>

            </div>
        </form>
       
    </div>
</div>
<script>


    layui.use(['form', 'layer', 'laydate','table'], function () {
        $ = layui.jquery;
        
        var laydate = layui.laydate;
        var form = layui.form;
        var table = layui.table;
        var join = [
            {field: 'user_id', title: '出借人ID',  width: 80},
            {field: 'real_name', title: '姓名', width: 80},
            {field: 'mobile_phone', title: '电话', width: 120},
            {field: 'idno', title: '身份证号', width: 180},
            {field: 'bank_card', title: '银行卡号', width: 180},
            {field: 'wait_capital', title: '在途本金（元）', width: 120},
            {field: 'purchase_amount', title: '收购金额（元）', width: 120},
            {field: 'discount', title: '收购折扣', width: 80},
            {field: 'trading_num', title: '待处理单数', width: 80},
            {field: 'start_time', title: '发布时间', width: 150},
            {field: 'end_time', title: '失效时间', width: 150},
            {field: 'add_user_name', title: '录入人', width: 150},
            {field: 'status_cn', title: '状态', width: 80},
            {title: '操作', toolbar: '#operate',width: 320},
        ];

        table.render({
            elem: '#list',
            // toolbar: '#toolbar',
            defaultToolbar: [''],

            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/debtMarket/ExclusivePurchase/index',
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
        $("#discount").prop('disabled','disabled');
        $("#purchase_amount").removeAttr('disabled');
       
        $("#confirm_send").prop('disabled','disabled');
        $("#confirm_send").addClass('disabled');


        form.on('radio(purchase_type)', function(data){
            var val=data.value;
            if (val == 1) {
                $("#purchase_amount").prop('disabled','disabled');
                $("#discount").removeAttr('disabled');
            } else if (val == 2) {
                $("#discount").prop('disabled','disabled');
                $("#purchase_amount").removeAttr('disabled');
            }
          });

       

        form.on('submit(user_debt_list)', function (obj) {
            xadmin.open('债权明细', '/debtMarket/ExclusivePurchase/index?user_id=',900,380);
            return false;
        });
        form.on('submit(add)',
            function(data) {
                console.log(data);
                //发异步，把数据提交给php
                var user_id = $("#user_id").val();
                var buyer_user_id = $("#buyer_user_id").val();
                var discount = $("#discount").val() * 1;
                
                if(data.field.user_id == ''){
                    layer.msg('请输入出借人ID' , {icon:2 , time:2000}); 
                    return false;
                } 
                if(data.field.user_id == undefined){
                    layer.msg('请输入出借人ID' , {icon:2 , time:2000}); 
                    return false;
                } 

                // if( data.field.buyer_user_id == ''){
                //     layer.msg('请输入受让人ID' , {icon:2 , time:2000}); 
                //     return false;
                // } 
                // if(data.field.buyer_user_id == undefined){
                //     layer.msg('请输入受让人ID' , {icon:2 , time:2000}); 
                //     return false;
                // } 
                if(data.field.discount*1 >10 || data.field.discount*1 <0.01){
                    layer.msg('请确保折扣值在0.01~10范围' , {icon:2 , time:2000}); 
                    return false;
                }
                if(data.field.discount*1 >2 || data.field.discount*1 < 1.9){
                    layer.msg('请确保折扣值在1.9~2范围' , {icon:2 , time:2000}); 
                    return false;
                }

                if(data.field.purchase_amount*1 <= 0 ){
                    layer.msg('请输入收购金额' , {icon:2 , time:2000}); 
                    return false;
                }
             
                $.ajax({
                    url: '/debtMarket/exclusivePurchase/create',
                    data: data.field,
                    type:"POST",
                    dataType:'json',
                    success: function (res) {
                        
                        if(res.code == 0){
                            layer.alert("添加成功",
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
                return false;
                
            });



    });

    function confirm_and_send(){
        var user_id = $("#user_id").val();
        var buyer_user_id = $("#buyer_user_id").val();
       
        var purchase_amount = $("#purchase_amount").val() * 1;
        var discount = $("#discount").val() * 1;
        
        if(user_id == ''){
            layer.msg('请输入出借人ID' , {icon:2 , time:2000}); 
            return false;
        } 
        if(user_id == undefined){
            layer.msg('请输入出借人ID' , {icon:2 , time:2000}); 
            return false;
        } 

        if(discount >10 || discount<0.01){
            layer.msg('请确保折扣值在0.01~10范围' , {icon:2 , time:2000}); 
            return false;
        }
        if(purchase_amount*1 <= 0 ){
            layer.msg('请输入收购金额' , {icon:2 , time:2000}); 
            return false;
        }
        
        // if( buyer_user_id == ''){
        //     layer.msg('请输入受让人ID' , {icon:2 , time:2000}); 
        //     return false;
        // } 
        // if(buyer_user_id == undefined){
        //     layer.msg('请输入受让人ID' , {icon:2 , time:2000}); 
        //     return false;
        // } 

        $("#confirm_send").removeAttr('disabled');
        $("#confirm_send").removeClass('disabled');
        $('#confirm_send').removeClass('layui-btn-disabled');
        $("#confirm_user_info").addClass('disabled');
        $('#confirm_user_info').addClass('layui-btn-disabled');
        console.log(888);
    }
  
    function user_info() {
        var user_id = $("#user_id").val();
        $.ajax({
                    url: '/debtMarket/exclusivePurchase/getUserInfo',
                    data: {"user_id":user_id},
                    type:"POST",
                    dataType:'json',
                    success: function (res) {
                        
                        if(res.code == 0){
                            $("#user_chong_ti_cha").val(res.data.recharge_withdrawal_difference);
                            $("#_real_name").html(res.data.real_name);
                            $("#_idno").html(res.data.idno);
                            $("#_bank_card").html(res.data.bank_card);
                            $("#_mobile_phone").html(res.data.mobile_phone);
                            $("#_wait_capital").html(res.data.wait_capital);
                            $("#_user_chong_ti_cha").html(res.data.recharge_withdrawal_difference);
                           
                        }else{
                            layer.alert(res.info);
                            $("#user_id").val('');
                        }
                    }
                })
    }

    function query_buyer_info() {
        var buyer_user_id = $("#buyer_user_id").val();
        var user_id = $("#user_id").val();
        if(user_id == ''){
            layer.msg('请输入出借人ID' , {icon:2 , time:2000});
        }
        if(buyer_user_id>0){
            $.ajax({
                    url: '/debtMarket/exclusivePurchase/AssigneeChangeUserId',
                    data: {"user_id":user_id,"buyer_user_id":buyer_user_id},
                    type:"POST",
                    dataType:'json',
                    success: function (res) {
                        if(res.code == 0){
                            $("#buyer_user_name").html(res.data.real_name);
                        }else{
                            
                            layer.msg(res.info , {icon:2 , time:2000});
                           

                        }
                    }
                })
           
        } 
  
       
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

    function fomatFloat(value, n) {
        var f = Math.round(value*Math.pow(10,n))/Math.pow(10,n);
        var s = f.toString();
        var rs = s.indexOf('.');   
        if (rs < 0) {     
        s += '.';   
        } 
        for(var i = s.length - s.indexOf('.'); i <= n; i++){
        s += "0";
        }
        return s;
    }

    function to_calculate(type){
        
        var user_id = $("#user_id").val();

        if(user_id == ''){
            layer.msg('请输入出借人ID并点击查询按钮' , {icon:2 , time:2000});

            return false;
        }
        //充提差
        var wait_capital = $("#user_chong_ti_cha").val();

        if(wait_capital == ''){
            layer.msg('用户充提差为0' , {icon:2 , time:2000});

            return false;
        }
        if(type==1){
           
            var discount = $("#discount").val();
            if(discount*1<0 || discount*1>10){
                $("#discount").val("");
                return false;
            }
            str = Math.round(parseFloat(wait_capital * discount * 0.1) * 100) / 100;
            $("#purchase_amount").val(str);
        }else{
            var purchase_amount = $("#purchase_amount").val();
            if(purchase_amount == ''){
               
                return false;
            }
           
            if(purchase_amount*1>wait_capital*1){
                $("#purchase_amount").val("");
                console.log(purchase_amount,purchase_amount>wait_capital,wait_capital);
                return false;
            }
            var num = purchase_amount / wait_capital * 10;
    
            $("#discount").val(forDight(num,2));
        }
    }
    //四舍五入
    function forDight(_num,_x){
        var n = 1;
        for(var i=0;i<_x;i++){
            n=n*10;
        }
        return Math.round(_num*n)/n;
    }

    function edit_user_bank(params) {
        var user_id = $("#user_id").val();
        if(user_id==''){
            layer.msg('请输入出借人ID' , {icon:2 , time:2000});
            return;
        }
        xadmin.open('修改银行卡信息', '/debtMarket/exclusivePurchase/editUserBank?user_id='+user_id,800,380);
        return false;
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