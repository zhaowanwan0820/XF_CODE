<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>审核</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
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
                <div style="margin-left: 100px;">
                    <div class="layui-card-body">
                        <div class="layui-row">
                            <label class="layui-form-label" style="font-size: 13px;">
                                还款金额:
                               </label>
                               <label class="layui-form-label" style="font-size: 13px;">
                                <{$new_principal+$new_interest}> 元
                               </label>
                               <label class="layui-form-label" style="font-size: 13px;  width: 270px;">
                                其中 本金:<{$new_principal}> 元 利息:<{$new_interest}> 元
                               </label>
                        </div>
                    </div>
                    <div class="layui-card-body">
                        <div class="layui-row">
                            <label class="layui-form-label" style="font-size: 13px;">
                                凭证内还款日期:
                            </label>
                            <label class="layui-form-label" style="font-size: 13px;">
                                <{$deal_reply_slip['repay_date']}>
                            </label>
                           
                        </div>
                    </div>
                    <div class="layui-card-body">
                        <div class="layui-row">
                            <label class="layui-form-label" style="font-size: 13px;">
                                客户付款凭证:
                            </label>
                            <label class="layui-form-label" style="font-size: 13px;">
                                <a href="<{$deal_reply_slip['reply_slip']}>" style="color: blue;">查看</a>
                            </label>
                           
                        </div>
                    </div>

                
                    <div class="layui-card-body">
                        <div class="layui-row">
                            <label class="layui-form-label" style="font-size: 13px;">
                                状态:
                            </label>
                            <label class="layui-form-label" style="font-size: 13px;">
                                <{$deal_reply_slip['status_cn']}>
                            </label>
                           
                        </div>
                    </div>
                    
                    
                  
            </div>
            <{if $deal_reply_slip['status']!= 1 }>
            <div class="layui-card-body" style="margin-left: 230px;">
                <button type="button" class="layui-btn layui-btn-normal" onclick="batchAgree(1)">审核通过</button>
                <button type="button"  class="layui-btn  layui-btn-primary" onclick="batchAgree(2)">取消</button>
            </div>
            <{/if}>
        </div>
        <script>
        layui.use(['laydate','form', 'layer'] , function(){
            $ = layui.jquery;
            var laydate = layui.laydate;
            var form = layui.form;
            var table = layui.table;
            
            laydate.render({
                elem: '#repay_date'
            });
            form.verify({
                    // nikename: function(value) {
                    //     if (value.length < 5) {
                    //         return '昵称至少得5个字符啊';
                    //     }
                    // },
                    // repass: function(value) {
                    //     if ($('#L_pass').val() != $('#L_repass').val()) {
                    //         return '两次密码不一致';
                    //     }
                    // }
                });

        });

        
    function batchAgree(n) {
        if(n ==2){
            window.parent.location.reload();
            return;
        }
        var info = n == 1 ? '通过' : '拒绝'
        layer.confirm('是否确定审核'+info,
            function () {
            
                var id = <{$deal_reply_slip['id']}>
             
                $.ajax({
                    url: '/borrower/DealOrder/AuditVoucher',
                    data: {id:id},
                    type: "POST",
                    dataType:'json',
                    success: function (res) {
                        if (res.code == 0) {
                            layer.confirm(res.info, function () {
                                window.parent.location.reload();
                            })
                        } else {
                            layer.alert(res.info);
                        }
                    }
                });
            })
    }


      </script>
    </body>

</html>