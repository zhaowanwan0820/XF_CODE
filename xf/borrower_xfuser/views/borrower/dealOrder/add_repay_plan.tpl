<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>欢迎页面-</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!--datatables-->
    <link rel="stylesheet" href="<{$CONST.cssPath}>/jquery.dataTables.min.css">
    <script src="<{$CONST.jsPath}>/jquery-2.1.4.min.js"></script>
    <script src="<{$CONST.jsPath}>/jquery.dataTables.min.js"></script>
</head>

<body>

<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
         
       
            <div class="layui-card">
                <div class="layui-card-header">零售系统导出借款人信息 &nbsp;&nbsp;&nbsp;&nbsp;
                    <{if $can_edit_user_bank == true}>
                    <button class="layui-btn layui-btn-danger"   lay-submit="" lay-filter="edit_user_bank">修改</button>
                    <{/if}>
                </div>
    
                <div class="layui-card-body">
                    <div class="layui-row">
                        <div class="layui-col-md4">
                           <span style="font-size: 13px;">借款人姓名：<{$userInfo['customer_name']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人手机号：<{$userInfo['phone']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人证件号：<{$userInfo['id_number']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人银行卡号：<{$userInfo['card_number']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">所属银行：<{$userInfo['bank_name']}></span>
                        </div>
                      </div>
                </div>
            </div>

               
            <div class="layui-card">
                <div class="layui-card-header">通过出借人关联出的借款人信息</div>
                <div class="layui-card-body">
                    <div class="layui-row">
                        <div class="layui-col-md4">
                           <span style="font-size: 13px;">借款人姓名：<{$firstp2pUserInfo['real_name']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人手机号：<{$firstp2pUserInfo['mobile']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人证件号：<{$firstp2pUserInfo['idno']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人银行卡号：<{$firstp2pUserInfo['card_number']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">所属银行：<{$firstp2pUserInfo['bank_name']}></span>
                        </div>
                      </div>
                </div>
            </div>
            


            <div class="layui-card">
                <div class="layui-card-header">原标的信息</div>
                <div class="layui-card-body ">
                    <div class="layui-row">
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款金额：<{$dealInfo['loan_amount']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span> <span style="font-size: 13px;">原待还本金和：<{$dealInfo['principal']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span><span style="font-size: 13px;">原待还利息和：<{$dealInfo['interest']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款时间：<{$dealInfo['o_create_time']}></span>
                        </div>
                        <!-- <div class="layui-col-md4">
                            <span class="x-red">*</span><span style="font-size: 13px;">调整后待还本金和：<{$dealInfo['new_principal']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span> <span style="font-size: 13px;">调整后待还利息和：<{$dealInfo['new_interest']}>元</span>
                        </div> -->
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">咨询方：<{$dealInfo['organization_name']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">待还咨询费：<{$dealInfo['consult_fee']}>元</span>
                        </div>
                      </div>
                </div>
            </div>
            <div class="layui-card">
                <form class="layui-form"  id="my_form">
                    <div class="layui-form-item">
                        <div class="layui-card-header">
                            <div class="layui-input-inline">新建还款计划类型</div>
                            <div class="layui-input-inline" style="width: 400px; padding-left: 5px;">
                                <input type="radio"  name="repay_type" lay-filter="repay_type" class="repay_type" value="1" checked title="非特殊还款协议">
                                <input type="radio"  name="repay_type" lay-filter="repay_type" class="repay_type"value="2" title="特殊换款协议">
                            </div>
                        </div>
                            
                    </div>
                    <div class="layui-card-header">新还款金额
                        <!-- <span style="padding-top:15px;padding-left:15px;color: dimgray;" >
                            除【在途本金区间人数统计】模块，均为实时数据
                        </span> -->
                    </div>
               
                    <div class="layui-form-item">
                        <div class="layui-col-lg6">
                            <label class="layui-form-label">新本金</label>    
                            
                            <div class="layui-input-inline">
                                <input type="text" onkeyup="to_calculate(1)" name="capital" id="capital"  lay-verify="number" autocomplete="off"  class="layui-input" value="0">
                            </div>
                            <div class="layui-form-mid layui-word-aux" id="policy_1"></div>
                            <div class="layui-input-inline" id="notice_1"></div>
                            <!-- <{if $capital_policy > 0}>
                            <div class="layui-form-mid layui-word-aux">  优惠幅度<{$capital_policy}>% </div>
                            <{/if}> -->
                        </div>
        
                        <div class="layui-col-lg6">
                            <label class="layui-form-label">新利息</label>
                            
                            <div class="layui-input-inline">
                                <input type="text" onkeyup="to_calculate(2)" name="interest" id="interest"  lay-verify="number" autocomplete="off"  class="layui-input" value="0">
                            </div>
                            <div class="layui-form-mid layui-word-aux" id="policy_2"></div>
    
                            <!-- <{if $interest_policy > 0}>
                            <div class="layui-form-mid layui-word-aux">  优惠幅度<{$interest_policy}>% </div>
                            <{/if}> -->
                        </div>
                    </div>
                    <div class="layui-form-item"> 
                        
                        <div class="layui-col-lg6">
                            <label class="layui-form-label">新滞纳金</label>
                            
                            <div class="layui-input-inline">
                                <input type="text" onkeyup="to_calculate(3)" name="zhinajin" id="zhinajin"  lay-verify="number" autocomplete="off"  class="layui-input" value="0">
                            </div>
                            <div class="layui-form-mid layui-word-aux" id="policy_3">   </div>

    <!--                             
                            <{if $late_fee_policy > 0}>
                            <div class="layui-form-mid layui-word-aux" id="pllicy_3">  优惠幅度<{$late_fee_policy}>% </div>

                            <{/if}> -->
                        </div>
        
                        <div class="layui-col-lg6">
                            <label class="layui-form-label">新罚息</label>
                            
                            <div class="layui-input-inline">
                                <input type="text" onkeyup="to_calculate(4)" name="faxi" id="faxi"  lay-verify="number" autocomplete="off"  class="layui-input" value="0">
                            </div>
                            <div class="layui-form-mid layui-word-aux" id="policy_4"></div>

                            <!-- <{if $penalty_interest_policy > 0}>
                            <div class="layui-form-mid layui-word-aux">  优惠幅度<{$penalty_interest_policy}>% </div>
                            <{/if}> -->
                            <!-- <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>有效期最长30天</div> -->
        
                        </div>
            
                    </div>
                    
                     
                    <div class="layui-form-item"> 
                        <div class="layui-col-lg6">
                            <label class="layui-form-label" >新合计还款金额:</label>
                            <div class="layui-form-mid " id='all_repay'>   </div>

                        </div>
                        <div class="layui-col-lg6">
                            <label class="layui-form-label"  >合计优惠金额:</label>
                          
                            <div class="layui-form-mid " id='all_youhui'>   </div>

                            
                        </div>
                       
                    </div>

                    
                    

                
                <div class="layui-form-item">
                    <div class="layui-card-header">
                        <div class="layui-input-inline">
                            新还款方式
                        </div>
                        
                    </div>
                   
                    <label class="layui-form-label">期数</label>
                    <div class="layui-input-inline">
                        <select name="repay_num" required lay-verify="required" lay-filter="repay_num">
                            <option value="1">1期</option>
                            <option value="2">2期</option>
                            <option value="3">3期</option>
                            <option value="4">4期</option>
                            <option value="5">5期</option>
                            <option value="6">6期</option>
                            <option value="7">7期</option>
                            <option value="8">8期</option>
                            <option value="9">9期</option>
                            <option value="10">10期</option>
                            <option value="11">11期</option>
                            <option value="12">12期</option>
                          </select>
                        <!-- <input type="text" onkeyup="make_repay_plan()" name="repay_num" id="repay_num"  placeholder lay-verify="number" autocomplete="off"  class="layui-input"> -->
                    </div>
    
    
                </div>
                
                <div class=""  id="new_repay_plan">

                    <div class="layui-form-item" >
                        <div class="layui-col-lg6">
                            <label class="layui-form-label">第1期还款金额</label>
                            <div class="layui-input-inline">
                                <input type="text"  name="repay_plan_num[1]" placeholder  lay-verify="number" autocomplete="off"  class="layui-input">

                            </div>
                        </div>
                        <div class="layui-col-lg6">
                            <label class="layui-form-label">还款日期</label>
                            <div class="layui-input-inline">
                                <input class="layui-input" name="repay_plan_time[1]" placeholder="2021-01-01" lay-verify="date" autocomplete="off" id="repay_start_1" >

                            </div>
                        </div>
                    </div>
                </div>

                <!-- <div class="layui-form-item" id="new_repay_plan">
                    <div class="layui-col-lg6">
                        <label class="layui-form-label">第1期还款金额</label>
                        <div class="layui-input-inline">
                            <input type="text"  name="repay_plan_num[1]" placeholder  lay-verify="number" autocomplete="off"  class="layui-input">

                        </div>
                    </div>
                    <div class="layui-col-lg6">
                        <label class="layui-form-label">还款日期</label>
                        <div class="layui-input-inline">
                            <input class="layui-input" name="repay_plan_time[1]" placeholder="开始时间" name="repay_start" id="repay_start_1" readonly>

                        </div>
                    </div>
                </div> -->
              
              
                <div class="layui-row layui-col-space10">
                    <div class="layui-col-md4">
                  
                    </div>
                    <div class="layui-col-md4">
                        <div class="layui-form-item">
                            <label for="L_repass" class="layui-form-label"></label>
                            <button  type="button" class="layui-btn" lay-filter="add" lay-submit="" id="submit">提交</button>
            
                        </div>
                    </div>
                    <div class="layui-col-md4">
                     
                    </div>
                  </div>
               
            </form>
            </div>

        </div>
    </div>
</div>
</body>
<script>
 
  
    var wait_capital = <{$dealInfo['principal']}>;
    var wait_interest = <{$dealInfo['interest']}>;
    

    layui.use(['laydate', 'table', 'layer', 'form','jquery' ], function () {
        $ = layui.jquery;
        var laydate = layui.laydate;
        var form = layui.form;
        var table = layui.table;
    
        form.on('select(repay_num)',function(data){
          
            var repay_num = data.value;
            var all = '';
            for (let index = 1; index <= repay_num; index++) {
                var  str = ' <div class="layui-form-item" > <div class="layui-col-lg6">' +
                            '<label class="layui-form-label">'+'第'+index+ '期还款金额</label>'+
                            '<div class="layui-input-inline">' + 
                                '<input type="text"  name="repay_plan_num['+index+']" placeholder  lay-verify="number" autocomplete="off"  class="layui-input">'+
                            '</div>'+
                        '</div>'+  
                        '<div class="layui-col-lg6">'+
                        '<label class="layui-form-label">还款日期</label>' +
                            '<div class="layui-input-inline">'+
                                '<input class="layui-input" name="repay_plan_time['+index+']" placeholder="2021-01-01" lay-verify="date" autocomplete="off" id="repay_start_'+index+'" >'+
                            '</div>'+
                        '</div> </div>';
                        // <input type="text" name="date" id="date" lay-verify="date" placeholder="1970-01-01" autocomplete="off" class="layui-input">

               
              
                all +=str; 
            }
            $("#new_repay_plan").html(all);

            for (let index = 1; index <= repay_num; index++) {
                laydate.render({
                    elem: '#repay_start_'+index
                });
                
            }

        }),
        laydate.render({
                    elem: '#repay_start_1'
                });
                
        
        form.on('submit(add)', function(data) {
            var wait_capital = <{$dealInfo['principal']}>;
            var wait_interest = <{$dealInfo['interest']}>;
        
            console.log(data);
            //发异步，把数据提交给php
            var interest = Number($("#interest").val());
            var capital = Number($("#capital").val());
            var faxi = Number($("#faxi").val());
            var zhinajin = Number($("#zhinajin").val());
            var all_repay = Math.round( (parseFloat(interest)+parseFloat(capital)+parseFloat(zhinajin)+parseFloat(faxi))*100)/100;
            var repay_type = $(".repay_type:checked").val();
            
            str = Math.round(parseFloat(((wait_capital-capital)/wait_capital)) * 100)


            
            // if(repay_type == 1 all_repay ){

            // }

           
            $.ajax({
                url: '/borrower/dealOrder/createNewRepayPlan?deal_id='+<{$deal_id}>,
                data: data.field,
                type:"POST",
                dataType:'json',
                success: function (res) {
                    
                    if(res.code == 0){
                        layer.alert(res.info, function () {
                                            window.parent.location.reload();
                                        })
                        // layer.alert("添加成功",
                        //         function(data,item){
                        //             var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                        //             parent.layer.close(index); //再执行关闭
                        //             parent.location.reload();
                        //         });
                    }else{
                        layer.alert(res.info);
                    }
                }
            })
            return false;
            
        });

        
        form.on('radio(repay_type)', function(data){
          
            var type=data.value;
            if (type == 2) {
                $("#policy_"+1).hide()
                $("#policy_"+2).hide()
                $("#policy_"+3).hide()
                $("#policy_"+4).hide()
                $("#submit").removeClass('layui-btn-disabled')
                $("#submit").attr('disabled', false);
            }else{
                $("#policy_"+1).show()
                $("#policy_"+2).show()
                $("#policy_"+3).show()
                $("#policy_"+4).show()
                to_calculate(1);
            }
          });

          form.on('submit(edit_user_bank)', function (obj) {
            xadmin.open('修改用户信息', '/borrower/borrower/editUserBank?user_id='+<{$dealInfo['user_id']}>,900,380);
            return false;
        });

    });

 
    function clear_notice(type){
        console.log(type,999);
        if (type == 2) {
                $("#policy_"+1).hide()
                $("#policy_"+2).hide()
                $("#policy_"+3).hide()
                $("#policy_"+4).hide()
            }else{
                $("#policy_"+1).show()
                $("#policy_"+2).show()
                $("#policy_"+3).show()
                $("#policy_"+4).show()
            }
    }
  
       
    function to_calculate(type){

       
        // var wait_capital = <{$dealInfo['principal']}>;
        // var wait_interest = <{$dealInfo['interest']}>;
        

        var repay_type = $(".repay_type:checked").val();
        var base_money = wait_capital;
        var input_capital=  Number($("#capital").val());

        var limit = 0 ;

        limit_1 = <{$capital_policy}>
        limit_2 = <{$interest_policy}>
       

     
        if(input_capital > wait_capital){
            $("#capital").val(wait_capital)
            $("#policy_1").html('')
            //layer.alert('输入金额超过待还金额');
            return;
        }

  
        var input_interest =  Number($("#interest").val());
   
        if(input_interest > wait_interest){
            $("#interest").val(wait_interest)
            $("#policy_2").html('')
            return;
        }

        var out_limit = false;
        if((type <= 2) && (input_interest > 0 || input_capital > 0)  && repay_type==1){
         
            str_1 = Math.round((parseFloat(((wait_capital-input_capital)/wait_capital)) * 100)*100)/100
        
            str_2 = Math.round((parseFloat(((wait_interest-input_interest)/wait_interest)) * 100)*100)/100
    

            $("#policy_1").html('优惠幅度'+str_1+'%');
            $("#policy_2").html('优惠幅度'+str_2+'%');
            
            if(repay_type == 1 && str_1 > limit_1 ){
                out_limit = true;
                $("#policy_1").html('超过优惠额度范围')
            }
            if(repay_type == 1 && str_2 > limit_2 ){
                out_limit = true;
                $("#policy_2").html('超过优惠额度范围')
            }
        }else{
            $("#policy_"+type).html('')
        }

        var interest = Number($("#interest").val());
        var capital = Number($("#capital").val());
        var faxi = Number($("#faxi").val());
        var zhinajin = Number($("#zhinajin").val());
        var all_repay = Math.round( (parseFloat(interest)+parseFloat(capital)+parseFloat(zhinajin)+parseFloat(faxi))*100)/100;
        if(all_repay > 0){
            $("#all_repay").html(all_repay+'元');

        }else{
            $("#all_repay").html('');
        }

        var all_youhui =Math.round(  (parseFloat(wait_capital) + parseFloat(wait_interest) - all_repay)*100)/100;
       
        $("#all_youhui").html(all_youhui+'元');

        console.log(out_limit,'out_limit');

        if(out_limit==true && repay_type == 1){
            // 增加样式
            $('#submit').addClass('layui-btn-disabled');
            $("#submit").attr('disabled', 'disabled');
        }else{
            $("#submit").removeClass('layui-btn-disabled')
            $("#submit").attr('disabled', false);
        }


        // console.log(base_money,input_money);
        // var total_amount = $("#total_amount").val();
        // if(total_amount<0){
        //     $("#total_amount").val("");
        //     return false;
        // }
        // var discount = $("#discount").val();
        // if(discount<0 || discount>10){
        //     $("#discount").val("");
        //     return false;
        // }
        // str = Math.round(parseFloat(total_amount * discount * 0.1) * 100) / 100;
        // $("#budget_amount").html(str);
    }


    function addAuth(deal_id) {
        
        layer.confirm('确认提交审核吗？', function (index) {
            $.ajax({
                url: '/borrower/DealOrder/addAuth',
                data: {id:deal_id},
                type: "POST",
                dataType:'json',
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert(res.info);
                        location.reload()
                    } else {
                        layer.alert(res.info);
                    }
                }
            });
        })
    }

    function authDeal(params,deal_id) {
        if(params==1){
            var title = '确认提交审核吗？'
        }else if(params ==2){
            var title = '确认审核通过吗？'
        }else{
            var title = '确认拒绝通过吗？'
        }
        layer.confirm(title, function (index) {
            $.ajax({
                url: '/borrower/DealOrder/authDeal',
                data: {id:deal_id,type:params},
                type: "POST",
                dataType:'json',
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert(res.info);
                        location.reload()
                    } else {
                        layer.alert(res.info);
                    }
                }
            });
        })
    }

    function resetSearch() {
        $("#user_id").val("");
        $("#mobile").val("");
    }

    function repayLogShow(params) {
        $('#modifyLogTable').css('display','none');
        $('#repayLogTable').css('display','table');
    }
    function modifyLogShow(params) {
        $('#modifyLogTable').css('display','table');
        $('#repayLogTable').css('display','none');
    }

</script>

<script type="text/html" id="operate">

    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>