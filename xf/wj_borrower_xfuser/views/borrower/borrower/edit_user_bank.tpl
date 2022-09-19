<!doctype html>
<html  class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>网信客服后台</title>
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="stylesheet" href="./css/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/login.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script type="text/javascript" src="<{$CONST.jsPath}>/jquery.min.js"></script>
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
</head>
<body>
<div class="layui-fluid">
    <div class="layui-row">
        <form class="layui-form">
            <div class="layui-form-item">
                <label for="bankcard" class="layui-form-label">
                    <span class="x-red">*</span>银行卡号
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="bankcard" name="bankcard"   value="<{$userInfo['bankcard']}>"
                          class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    用户提供的新银行卡号
                </div>
            </div>
            <div class="layui-form-item">
                <label for="bank_name" class="layui-form-label">
                    <span class="x-red">*</span>所属银行
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="bank_name" name="bank_name" required=""    value="<{$userInfo['bank_name']}>" lay-verify="realname"
                           autocomplete="off" class="layui-input">
                </div>
              
            </div>
           
            <div class="layui-form-item">
                <label for="bank_mobile" class="layui-form-label">
                    <span class="x-red">*</span>银行卡开户手机号
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="bank_mobile" name="bank_mobile"    required=""  lay-verify="phone"
                           autocomplete="off" class="layui-input">
                </div>
                <!-- lay-filter="send_sms"  -->
                <div class="layui-input-inline">
                    <button type="button" id="send_sms_id" class="layui-btn" onclick="send_sms()">发送验证码</button>
                </div>
                <div class="layui-form-mid layui-word-aux">
                    短信会发送给用户银行卡开户手机号
                </div>
            </div>

            <div class="layui-form-item">
                <label for="sms_code" class="layui-form-label">
                    <span class="x-red">*</span>验证码
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="sms_code" name="sms_code" required=""   lay-verify="number"
                           autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    从用户处索取的短信验证码
                </div>
            </div>

           
         
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label">
                </label>
                <button  class="layui-btn" lay-filter="commit" lay-submit="">
                    更改
                </button>
            </div>
        </form>
    </div>
</div>
<script>

layui.use(['form', 'layer'],
            function() {
                $ = layui.jquery;
                var form = layui.form,
                        layer = layui.layer;
                //自定义验证规则
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
              
                //监听提交
                form.on('submit(commit)',
                        function(data) {
                        
                            var user_id = <{$user_id}>;
                            var dd = data.field;
                            //发异步，把数据提交给php
                            $.ajax({
                                url: '/borrower/borrower/editUserBank',
                                data: {user_id:user_id,bank_mobile:dd.bank_mobile,step:2,bankcard:dd.bankcard,bank_name:dd.bank_name,sms_code:dd.sms_code},
                                dataType:'json',
                                type:"POST",
                                success: function (res) {
                                    if(res.code == 0){
                                        layer.alert("修改成功",
                                        function(data,item){
                                            var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                                            console.log(index,999);
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

   function send_sms(){
      var user_id = <{$user_id}>;
      var bank_mobile = $('#bank_mobile').val();
      var bankcard = $('#bankcard').val();
      if(bankcard==''){
        alert('请输入银行卡号！');
        return;
      }
      var bank_name = $('#bank_name').val();
      if(bank_name==''){
        alert('请输入所属银行！');
        return;
      }
      var flag = false;
      var message = "";
      var myreg = /^(((13[0-9]{1})|(14[0-9]{1})|(17[0]{1})|(15[0-3]{1})|(15[5-9]{1})|(18[0-9]{1}))+\d{8})$/;       
      if(bank_mobile == ''){
        layer.alert('手机号码不能为空！');
        return;
      }else if(bank_mobile.length !=11){
        layer.alert("请输入有效的手机号码！");
        return;
      }else if(!myreg.test(bank_mobile)){
        layer.alert('请输入有效的手机号码！');
        return;
      }
      $.ajax({
            url: '/borrower/borrower/editUserBank',
            data: {user_id:user_id,bank_mobile:bank_mobile,step:1,bankcard:bankcard,bank_name:bank_name},
            type:"POST",
            dataType:'json',
            success: function (res) {
                if(res.code == 0){
                     // 增加样式
                    $('#send_sms_id').addClass('layui-btn-disabled');
                    $("#send_sms_id").attr('disabled', 'disabled');
                    $("#send_sms_id").html('发送成功 请联系用户索取验证码');
                    // layer.alert(res.info,
                    //         function(data,item){
                    //             location.reload();
                    //         });
                }else{
                    layer.alert(res.info);
                }
            }
        })

}

</script>
</body>

</html>
