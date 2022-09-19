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
                                url: '/debtMarket/exclusivePurchase/editUserBank',
                                data: {user_id:user_id,bankcard:dd.bankcard,bank_name:dd.bank_name},
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



</script>
</body>

</html>
