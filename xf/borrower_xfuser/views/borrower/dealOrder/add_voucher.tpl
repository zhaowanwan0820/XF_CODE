<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>凭证补录</title>
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
                <form class="layui-form" method="post" action="/borrower/DealOrder/addVoucher" enctype="multipart/form-data" id="my_form">

                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>还款金额
                        </label>
                        <div class="layui-input-inline">
                            <input type="text" id="amount" name="amount" value="<{$new_principal+$new_interest}> " lay-verify="number" disabled class="layui-input">
                        </div>
                         <div class="" style="padding-top: 9px;">
                            元 其中：本金：<{$new_principal}> 元 利息： <{$new_interest}> 元
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>凭证中还款日期
                        </label>
                        <div class="layui-input-inline">
                            <input class="layui-input" name="repay_date" placeholder="2021-01-01" lay-verify="date" autocomplete="off" id="repay_date" >
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>客户付款凭证                       
                         </label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                            <span id="file_name"></span>
                            <input type="file" id="business_license" name="business_license" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="file_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                        <div class="layui-form-mid layui-word-aux">请上传扫描件（rar，zip，7z，pdf，jpg，png）</div>
                    </div>
                    <div class="layui-form-item">
                        <input type="hidden"  name="repay_id" lay-skin="primary" lay-filter="father" value="<{$repay_id}>">

                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="submit" class="layui-btn" onclick="add()">提交补录申请</button>
                    </div>
                </form>
            </div>
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

        function add() {
          var refund_date = $("#refund_date").val();
          var amount   = $("#amount").val();
          var business_license    = $("#business_license").val();
      

          if (refund_date == '') {
            layer.msg('请输入退款日期' , {icon:2 , time:2000});
          }
          if (amount == '') {
            layer.msg('请输入退款金额' , {icon:2 , time:2000});
          } 
          if (business_license == '') {
            layer.msg('请上传营业执照扫描件' , {icon:2 , time:2000});
          } 

          $("#my_form").submit();
    
        }

        function add_file() {
          $("#business_license").click();
        }

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
          $("#file_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
        }

      </script>
    </body>

</html>