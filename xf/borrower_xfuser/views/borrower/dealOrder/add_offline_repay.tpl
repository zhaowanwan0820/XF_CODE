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
                <div class="layui-card-header">零售系统导出借款人信息</div>
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
                            <span style="font-size: 13px;">借款时间：<{$dealInfo['d_create_time']}></span>
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
                <!-- <div class="layui-card-header">原还款计划</div> -->

                <div class="layui-card-body ">
                    <div class="layui-tab layui-tab-brief" lay-filter="table-all">
                        <ul class="layui-tab-title">
                            <li data-status="0" class="layui-this" style="font-size: 14px;" id='repay_log' onclick="repayLogShow()">本次还款期数</li>
                            <!-- <li data-status="1" id='modify_log' style="font-size: 14px;" onclick="modifyLogShow()">修改记录</li> -->
                            
                        </ul>
                    </div>
                    <table class="layui-table layui-form" id="repayLogTable">
                        <thead>
                        <tr>
                           
                            <th>期数</th>
                            <th>原待还本金</th>
                            <th>本金还款金额</th>
                            <th>本金还款状态</th>
                            <th>本金还款方式</th>
                            <th>线下还款金额</th>
                            <th>本金退款状态</th>
                            <th>原待还利息</th>
                            <th>利息还款金额</th>
                            <th>利息还款状态</th>
                            <th>利息还款方式</th>
                            <th>利息退款状态</th>
                            <th>应还款时间</th>
                            <th>实际还款时间</th>
                            <th>退款时间</th>
                            <th></th>
                        </thead>
                        <tbody>
                        <{foreach $repayPlan as $key => $val}>
                            <tr>
                                <td><{$val['repay_num']}></td>
                                <td><{$val['principal']}></td>
                                <td><{$val['paid_principal']}></td>
                                <td><{$val['is_repay_principal']}></td>
                                <td><{$val['principal_repay_type_cn']}></td>
                                <td><{$val['offline_repay_amount']}></td>
                                <td><{$val['principal_refund_status_cn']}></td>
                                <td><{$val['interest']}></td>
                                <td><{$val['paid_interest']}></td>
                                <td><{$val['is_repay_interest']}></td>
                                <td><{$val['interest_repay_type_cn']}></td>
                                <td><{$val['interest_refund_status_cn']}></td>
                                <td><{$val['repay_time']}></td>
                                <td><{$val['true_repay_time']}></td>
                                <td><{$val['principal_refund_time']}></td>
                                <{if $val['is_repay_principal'] == '已还' || $val['is_repay_interest'] == '已还'}>
                                <td></td>
                                <{else}>
                                    <{if $val['is_add_offline_repay']}>
                                        <td> <input name= "checkbox" class="repay_ids" type="checkbox"  value="<{$val['id']}>" disabled lay-skin="primary"></td>
                                    <{else}>
                                        <td> <input name= "checkbox" class="repay_ids" type="checkbox"  value="<{$val['id']}>" lay-skin="primary"></td>

                                    <{/if}>
                                <{/if}>
                            </tr>
                       
                            <{/foreach}>
                        </tbody>
                    </table>

                </div>
            </div>
            <div class="layui-card">
                <form class="layui-form"  id="my_form">
                    <div class="layui-card-header">
                        还款信息                
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">还款方式</label>
                        <div class="layui-input-inline" style="width: 400px;">
                            <input lay-filter="repay_type" type="radio" class="repay_type" name="repay_type" value="2" checked title="按金额还款">
                            <input lay-filter="repay_type" type="radio" class="repay_type" name="repay_type" value="1" title="按折扣还款">
                        </div>
                    </div>
                    <div class="layui-form-item" id="use_repay_amount">
                        <label for="name" class="layui-form-label">协商还款金额</label>
                        <div class="layui-input-inline">
                            <input  type="text" id="repay_amount" name="repay_amount"   required="" 
                                autocomplete="off" class="layui-input">

                        </div>
                        <input   class="layui-btn" id="deal_repay_discount" value="计算还款折扣"  ></input>
                        <span id="deal_discount_result"></span>
                    </div>
                    <div class="layui-form-item" id="use_discount">
                        <label for="name" class="layui-form-label">协商还款折扣</label>
                        <div class="layui-input-inline">
                            <input  type="text" id="discount" name="discount"  placeholder="请输入0-10之间的数字"    required="" 
                                autocomplete="off" class="layui-input">

                        </div>
                        <input  class="layui-btn" id="deal_repay_amount" value="计算还款金额"   ></input>
                        <span id="deal_repay_amount_result"></span>

                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">还款内容</label>
                        <div class="layui-input-inline" style="width: 400px;">
                            <input lay-filter="repay_content" type="radio" class="repay_content" name="repay_content" value="1" title="本金" checked>
                            <!-- <input lay-filter="repay_content" type="radio" class="repay_content" name="repay_content" value="2" title="利息">
                            <input lay-filter="repay_content" type="radio" class="repay_content" name="repay_content" value="3" title="本金+利息"> -->
                            <input lay-filter="repay_content" type="radio" class="repay_content" name="repay_content" value="4" title="当期部分还款">
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">凭证内还款时间</label>
                        <div class="layui-input-inline">
                            <input class="layui-input" name="repay_date" placeholder="2021-01-01" lay-verify="date" autocomplete="off" id="repay_date" >
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">上传付款凭证</label>
                        
                        <div class="layui-input-inline">
                          <button type="button" class="layui-btn" id="logo"><i class="layui-icon"></i>上传文件</button>
                          <span id="file_tips"></span>  
                        </div>
                        <!-- <div class="layui-form-mid layui-word-aux">请上传jpg、png格式图片</div> -->

                      </div>
<!-- 
                      <div class="layui-form-item">
                        <div class="layui-input-inline" style="margin-left: 150px;">
                          <img class="layui-upload-img" id="logo_url" style="height:100px" src="<{$platformInfo['logo_url']}>">
                        </div>
                      </div> -->
                    
                <div class="layui-row layui-col-space10">
                    <div class="layui-col-md4">
                  
                    </div>
                    <div class="layui-col-md4">
                        <div class="layui-form-item">
                            <label for="L_repass" class="layui-form-label"></label>
                            <input type="hidden" name="logo_path" id="logo_path" class="layui-input">
                            <input type="hidden" name="repay_ids" id="repay_ids" class="layui-input">

                            <button  type="button" class="layui-btn" lay-filter="add" lay-submit="" id="submit">提交还款申请</button>
            
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

    
layui.use(['laydate', 'table', 'layer', 'form','upload'], function () {
        var laydate = layui.laydate;
        $ = layui.jquery;
    var form = layui.form;
    var layer = layui.layer;
    var upload = layui.upload;
    //自定义验证规则
    var element = layui.element;

    $("#use_discount").hide();
    $("#use_repay_amount").show();
    $("#discount").prop('disabled','disabled');
    $("#repay_amount").removeAttr('disabled');
            

    form.on('radio(repay_type)', function(data){
        $("#repay_amount").val('');
        $("#discount").val('')
            var val=data.value;
            if (val == 1) {
                $("#use_repay_amount").hide();
                $("#use_discount").show();
                $("#repay_amount").prop('disabled','disabled');
                $("#discount").removeAttr('disabled');
          
            } else if (val == 2) {

                $("#use_discount").hide();
                $("#use_repay_amount").show();
                $("#discount").prop('disabled','disabled');
                $("#repay_amount").removeAttr('disabled');
            }
    });
    
    form.on('submit(add)', function(data) {



        var repay_ids = jqchk();

       
        if(data.field.repay_content*1 == 4 && repay_ids.length > 1 ){
            layer.alert('当期部分还款只能选择一条还款计划');
            return false;
        }


        data.field.repay_ids = repay_ids;
            $.ajax({
                url: '/borrower/dealOrder/createOfflineRepay',
                data: data.field,
                type:"POST",
                dataType:'json',
                success: function (res) {
                    if(res.code == 0){
                      
                        layer.alert('提交成功', function () {
                                            window.parent.location.reload();
                                        })
                      
                    }else{
                        layer.alert(res.info);
                    }
                }
            })
            return false;
            
        });
       

    laydate.render({
            elem: '#repay_date'
        });

        //logo图片上传
    // var uploadInst = upload.render({
    //     elem: '#logo'
    //     ,url: '/borrower/DealOrder/upload' //改成您自己的上传接口
    //     ,done: function(res){
    //     //如果上传失败
    //     if(res.code > 0){
    //         return layer.msg(res.info);
    //     }else{
    //         $('#logo_url').attr('src', res.data.file_url); //图片链接（base64）

    //         $("#logo_path").val(res.data.file_url);
    //     }
    //     //上传成功
    // }})
    //logo图片上传
    var uploadInst = upload.render({
        elem: '#logo',
         type:'file',
        // exts:'zip|rar',
        accept:'file',
        url: '/borrower/DealOrder/upload', //改成您自己的上传接口
        choose:function(obj){
            obj.preview(function (index,file,result) {
                $("#file_tips").text(file.name);
            })
        },
        done: function(res){
          //如果上传失败
          if(res.code > 0){
            return layer.msg(res.info);
          }else{

            $("#logo_path").val(res.data.file_url);
            layer.msg('上传成功');
          }
          //上传成功
        }})

          
        
        
});

$(function (params) {
    $('.repay_ids').on('click',function (params) {
        alert(2222);
    })
})

$(document).on('change',"input[data-index][type='checkbox']",function(){
       
       
       var repay_ids = jqchk();
       if(repay_ids.length==0){
           layer.msg("请选择还款计划");
           return false;
       }
       console.log(repay_ids.length ,99999);

       if(repay_content*1 == 4 && repay_ids.length > 1 ){
           $("#deal_discount_result").html('');
           $("#deal_repay_amount_result").html('');
           layer.msg('当期部分还款只能选择一条还款计划');
           return false;
       }



       getDiscountOrRepayAmount(repay_ids,repay_type,repay_content,repay_amount,null);

   });



    $(document).on('click',"#deal_repay_discount",function(){
       
        var repay_type = $(".repay_type:checked").val();
        if(repay_type == undefined){
            layer.msg("请选择还款方式");
            return;
        }
        //输入的还款金额
        var repay_amount = $("#repay_amount").val();

        var repay_content = $(".repay_content:checked").val();

        var repay_ids = jqchk();
        if(repay_ids.length==0){
            layer.msg("请选择还款计划");
            return false;
        }

        if(repay_content*1 == 4 && repay_ids.length > 1 ){
            $("#deal_discount_result").html('');
            $("#deal_repay_amount_result").html('');
            layer.msg('当期部分还款只能选择一条还款计划');
            return false;
        }



        getDiscountOrRepayAmount(repay_ids,repay_type,repay_content,repay_amount,null);

        console.log(repay_type,repay_amount,repay_content,repay_ids);
    });


    $(document).on('click',"#deal_repay_amount",function(){
        var repay_type = $(".repay_type:checked").val();
        if(repay_type == undefined){
            layer.msg("请选择还款方式");
            return;
        }

        var repay_content = $(".repay_content:checked").val();

         //输入的还款金额
         var discount = $("#discount").val();

         if(discount > 10 || discount < 0){
            layer.msg("请输入0-10之间的数字");
            return;
         }
         var repay_ids = jqchk();
        if(repay_ids.length==0){
            layer.msg("请选择还款计划");
            return;
        }

        if(repay_content*1 == 4 && repay_ids.length > 1 ){
            
            $("#deal_discount_result").html('');
            $("#deal_repay_amount_result").html('');

            layer.msg('当期部分还款只能选择一条还款计划');
            return false;
        }



        getDiscountOrRepayAmount(repay_ids,repay_type,repay_content,null,discount);
        console.log(repay_ids,discount,repay_content,repay_ids);


        return;
    });

    //根据还款方式，计算还款折扣或者还款金额
    function getDiscountOrRepayAmount(repay_ids,repay_type,repay_content,repay_amount,discount) {
        
        $.ajax({
                url: '/borrower/DealOrder/getDiscountOrRepayAmount',
                data: {repay_ids:repay_ids,repay_type:repay_type,repay_content:repay_content,repay_amount:repay_amount,discount:discount},
                type: "POST",
                dataType:'json',
                success: function (res) {
                    if (res.code == 0) {
                        //按照折扣 返回的是金额 
                        console.log(res,999);
                        if(res.data.repay_type == 1){
                            $("#deal_repay_amount_result").html(res.data.repay_amount+'元');
                        }
                        //按照金额 返回的是折扣 
                        if(res.data.repay_type == 2){
                            $("#deal_discount_result").html(res.data.discount+'折');
                        }
                       
                    } else {
                        $("#deal_discount_result").html('');
                        $("#deal_repay_amount_result").html('');


                        console.log(res.info);
                       
                    }
                }
            })
            
    }


    function jqchk(){ //jquery获取复选框值
        var chk_value =[];
        $('input[name="checkbox"]:checked').each(function(){
        chk_value.push($(this).val());
        });
        return  chk_value;
    }




</script>

<script type="text/html" id="operate">

    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>