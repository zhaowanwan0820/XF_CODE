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
                          <input type="text"  class="layui-input" value="<{$info['id']}>" style="width: 300px" disabled>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">用户名</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['user_name']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">尊享待还本金</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['zx_wait_capital']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">普惠待还本金</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['ph_wait_capital']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">真实姓名</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['real_name']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">性别</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['sex']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">证件号码</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['idno']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">手机</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['mobile']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">邮箱地址</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['email']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">帐户状态</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['is_effect']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">是否已删除</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['is_delete']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">登录ip</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['login_ip']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">用户组id</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['group_id']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">用户类型</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['user_type']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">用户等级ID</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['coupon_level_id']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">积分</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['score']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">信用额度</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['quota']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">信用等级</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['level_id']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">信用</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['point']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">信用报告</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['creditpassed']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">有效期结束时间</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['coupon_level_valid_end']}>" style="width: 300px" disabled>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="username" class="layui-form-label">法大大ID</label>
                        <div class="layui-input-inline">
                          <input type="text" autocomplete="off" class="layui-input" value="<{$info['fdd_customer_id']}>" style="width: 300px" disabled>
                        </div>
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