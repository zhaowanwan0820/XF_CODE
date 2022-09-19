<!doctype html>
<html  class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>网信客服后台</title>
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <!-- <link rel="stylesheet" href="<{$CONST.cssPath}>/css/font.css"> -->
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
                <label for="L_pass" class="layui-form-label">
                    账号
                </label>
                <div class="layui-input-inline">
                    <input type="text"  value="<{$old['username']}>" required="" autocomplete="off" class="layui-input" readonly>
                </div>
            </div>

            <div class="layui-form-item">
                <label for="L_pass" class="layui-form-label">
                    姓名
                </label>
                <div class="layui-input-inline">
                    <input type="text"  value="<{$old['realname']}>" required="" autocomplete="off" class="layui-input" readonly>
                </div>
            </div>


            <div class="layui-form-item">
                <label for="L_pass" class="layui-form-label">
                    <span class="x-red">*</span>密码
                </label>
                <div class="layui-input-inline">
                    <input type="password" id="L_pass" name="password" value="" required="" lay-verify="pass"
                           autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    6到16个字符
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label">
                    <span class="x-red">*</span>确认密码
                </label>
                <div class="layui-input-inline">
                    <input type="hidden" name = "id" value="<{$userInfo['id']}>">
                    <input type="password" id="L_repass" name="repass" value="" required="" lay-verify="repass"
                           autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label">
                </label>
                <button  class="layui-btn" lay-filter="add" lay-submit="">
                    保存
                </button>
            </div>
        </form>
    </div>
</div>
<script>layui.use(['form', 'layer'],
            function() {
                $ = layui.jquery;
                var form = layui.form,
                        layer = layui.layer;
                //自定义验证规则
                form.verify({
                    repass: function(value) {
                        if ($('#L_pass').val() != $('#L_repass').val()) {
                            return '两次密码不一致';
                        }
                    }
                });
                //监听提交
                form.on('submit(add)',
                    function(data) {
                        //发异步，把数据提交给php
                        $.ajax({
                            url: '/user/Debt/EditPassword',
                            data: {password:data.field.password,repass:data.field.repass},
                            type:"POST",
                            dataType:'json',
                            success: function (res) {
                                if (res['code'] === 0) {
                                layer.msg(res['info'] , {time:1000,icon:1} , function(){
                                    var index = parent.layer.getFrameIndex(window.name);
                                    parent.layer.close(index);
                                    location.reload();
                                  });
                                } else {
                                  layer.alert(res['info']);
                                }
                            }
                        })
                        return false;
                    });

            });</script>
</body>

</html>
