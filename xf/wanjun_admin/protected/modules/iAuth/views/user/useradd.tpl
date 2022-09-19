<!doctype html>
<html  class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>后台登录</title>
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
                    <span class="x-red">*</span>登录名
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="username" name="username" value="" required="" lay-verify="required"
                           autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    <span class="x-red">*</span>将会成为您唯一的登入名
                </div>
            </div>
            <div class="layui-form-item">
                <label for="phone" class="layui-form-label">
                    <span class="x-red">*</span>手机
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="phone" name="phone" required="" value="" lay-verify="phone"
                           autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    <span class="x-red">*</span>将会成为您唯一的登入名
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_email" class="layui-form-label">
                    <span class="x-red">*</span>邮箱
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="L_email" name="email" required="" value="" lay-verify="email"
                           autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    <span class="x-red">*</span>
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_email" class="layui-form-label">
                    <span class="x-red">*</span>真实姓名
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="L_realname" name="realname" value="" required="" lay-verify="realname"
                           autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    <span class="x-red">*</span>
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_email" class="layui-form-label">
                    <span class="x-red">*</span>用户类型
                </label>
                <div class="layui-input-inline">
                    <input lay-verify="userType" type="radio" name="user_type" value="1" title="普通用户" checked>
                    <input lay-verify="userType" type="radio" name="user_type" value="2" title="咨询方用户">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    <span class="x-red">*</span>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label"><span class="x-red">*</span>角色</label>
                <div class="layui-input-block">
                    <div class="layui-input-inline" style="width: 190px">
                    <select name="itmeId"  lay-verify="itmeId"  id="voting_options" style="width:20px" lay-filter="showInput" >
                        <option value="0">全部</option>
                        <{foreach $itemName as $key => $val}>
                        <option value="<{$val['id']}>"><{$val['name']}></option>
                        <{/foreach}>
                    </select>
                    </div>
                </div>
            </div>

            <div class="layui-form-item" id="assignee_id_dev">
                <label for="L_pass" class="layui-form-label">
                    <span class="x-red">*</span>关联受让人id
                </label>
                <div class="layui-input-inline">
                    <input onBlur="query_buyer_info()" type="text" id="assignee_id" name="assignee_id" value="" required="" 
                           autocomplete="off" class="layui-input">
                </div>
               
                <div class="layui-form-mid layui-word-read" id="buyer_user_name"></div>

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
                    <input type="password" id="L_repass" name="repass" value="" required="" lay-verify="repass"
                           autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label">
                </label>
                <button id='createUser'  class="layui-btn" lay-filter="add" lay-submit="">
                    增加
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
            },
            itmeId: function(value) {
                if (value == 0) {
                    return '请选择角色';
                }
            }
        });
        $("#assignee_id_dev").hide();
        
        //监听提交
        form.on('submit(add)',
                function(data) {
                    var buyer_user_name = $("#buyer_user_name").html();
                    var buyer_user_id = $("#assignee_id").val()*1;
                    if(buyer_user_id > 0 && buyer_user_name==''){
                        layer.alert('受让人信息有误');
                        return false;
                    }
                    console.log(data);
                    //发异步，把数据提交给php
                    $.ajax({
                        url: '/iauth/user/useradd',
                        data: data.field,
                        type:"POST",
                        success: function (res) {
                            if(res.code == 0){
                                layer.alert("添加成功",
                                        function(data,item){
                                            var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
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
            form.on('select(showInput)' , function(data){   
                var val=data.value;
                if(val == 461){
                    $("#assignee_id_dev").show();
                }else{
                    $("#assignee_id_dev").hide();
                    $("#assignee_id").val('');
                    $("#buyer_user_name").html('');
                    $("#createUser").removeAttr('disabled');
                    $("#createUser").removeClass('disabled');
                    $('#createUser').removeClass('layui-btn-disabled');
                }
                
          });
    });

    function query_buyer_info() {
        var buyer_user_id = $("#assignee_id").val();
        $("#buyer_user_name").html('');

        if(buyer_user_id == ''){
            layer.msg('请输入受让人ID' , {icon:2 , time:2000});
        }
        if(buyer_user_id>0){
            $.ajax({
                    url: '/debtMarket/exclusivePurchase/AssigneeChangeUserId',
                    data: {"buyer_user_id":buyer_user_id},
                    type:"POST",
                    dataType:'json',
                    success: function (res) {
                        if(res.code == 0){
                            $("#buyer_user_name").html(res.data.real_name);
                            
                            $("#createUser").removeAttr('disabled');
                            $("#createUser").removeClass('disabled');
                            $('#createUser').removeClass('layui-btn-disabled');
                        }else{
                            $("#createUser").addClass('disabled');
                            $('#createUser').addClass('layui-btn-disabled');
                            layer.msg(res.info , {icon:2 , time:2000});
                           
                        }
                    }
                })
           
        }  
    }
    
            </script>
</body>

</html>
