<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>用户详情</title>
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
                <form class="layui-form">
                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">用户ID</label>
                        <div class="layui-input-inline">
                          <input type="text"  class="layui-input" value="<{$info['user_id']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">会员编号</label>
                        <div class="layui-input-inline">
                          <input type="text"  class="layui-input" value="<{$info['member_id']}>" style="width: 300px" disabled>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">会员名称</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['user_name']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">用户姓名</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['real_name']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">手机号</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['mobile']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">注册时间</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['create_time']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">证件类型</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['id_type']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">证件号码</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['idno']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">银行卡号</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['bankcard']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                     <div class="layui-form-item">
                        <label for="username" class="layui-form-label">开户行信息</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['bankzone']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">帐户类型</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['user_purpose']}>" style="width: 300px" disabled>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <style type="text/css">
            .layui-form-label {
                width: 110px;
            }
        </style>

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
                    pass: [/(.+){6,12}$/, '密码必须6到12位'],
                    repass: function(value) {
                        if ($('#L_pass').val() != $('#L_repass').val()) {
                            return '两次密码不一致';
                        }
                    }
                });

                //监听提交
                form.on('submit(add)',
                function(data) {
                    console.log(data);
                    //发异步，把数据提交给php
                    layer.alert("增加成功", {
                        icon: 6
                    },
                    function() {
                        // 获得frame索引
                        var index = parent.layer.getFrameIndex(window.name);
                        //关闭当前frame
                        parent.layer.close(index);
                    });
                    return false;
                });

            });</script>
        <script>var _hmt = _hmt || []; (function() {
                var hm = document.createElement("script");
                hm.src = "https://hm.baidu.com/hm.js?b393d153aeb26b46e9431fabaf0f6190";
                var s = document.getElementsByTagName("script")[0];
                s.parentNode.insertBefore(hm, s);
            })();</script>
    </body>

</html>