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
                <label for="username" class="layui-form-label">
                    <span class="x-red">*</span>归属第三方公司
                </label>

                <div class="layui-input-inline">
                    <input type="text" id="company_name" name="company_name" readonly="readonly" style="background:#CCCCCC" value="<{$userInfo['company_name']}>"
                           readonly  unselectable="on" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label for="username" class="layui-form-label">
                    <span class="x-red">*</span>账号名称(登录名)
                </label>

                <div class="layui-input-inline">
                    <input type="text" id="username" name="username" readonly="readonly" style="background:#CCCCCC" value="<{$userInfo['username']}>"
                           readonly  unselectable="on" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    <span class="x-red">*</span>将会成为您唯一的登入名
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_email" class="layui-form-label">
                    <span class="x-red">*</span>真实姓名
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="L_realname" name="realname" readonly="readonly" style="background:#CCCCCC"  value="<{$userInfo['realname']}>" required="" lay-verify="realname"
                           autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label"><span class="x-red">*</span>角色</label>
                <div class="layui-input-block">
                    <div class="layui-input-inline" style="width: 190px">
                        <select name="itmeId"  lay-verify="itmeId" disabled="disabled"  id="voting_options" style="width:20px">
                            <{foreach $authitem as $key => $val}>
                        <option value="<{$val['id']}>" <{if $itemName == $val['name']}>selected="selected"<{/if}>><{$val['name']}></option>
                            <{/foreach}>
                        </select>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label for="phone" class="layui-form-label">
                    <span class="x-red">*</span>联系电话
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="phone" name="phone"     required="" value="<{$userInfo['phone']}>" lay-verify="phone"
                           autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_email" class="layui-form-label">
                    <span class="x-red">*</span>联系邮箱
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="L_email" name="email" required=""    value="<{$userInfo['email']}>" lay-verify="email"
                           autocomplete="off" class="layui-input">
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
                    更改
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
                    nikename: function(value) {
                        if (value.length < 5) {
                            return '昵称至少得5个字符啊';
                        }
                    },
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
                                url: '/iauth/user/ComapnyUserEdit',
                                data: data.field,
                                type:"POST",
                                success: function (res) {
                                    if(res.code == 0){
                                        layer.alert(res.info,
                                                function(data,item){
                                                    location.reload();
                                                });
                                    }else{
                                        layer.alert(res.info);
                                    }
                                }
                            })
                            return false;
                        });

            });</script>
</body>

</html>
