<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>新增申请</title>
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
              <form class="layui-form" action="/user/Message/addUserMobile" method="post" enctype="multipart/form-data" id="my_form">

                <div class="layui-form-item">
                    <label for="idno" class="layui-form-label">
                        <span class="x-red">*</span>用户证件号</label>
                    <div class="layui-input-inline">
                        <input type="text" name="idno" id="idno" autocomplete="off" required class="layui-input" onchange="change_old_mobile()">
                    </div>
                    <div class="layui-form-mid layui-word-aux" id="idno_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                </div>

                <div class="layui-form-item">
                    <label for="old_mobile" class="layui-form-label">
                        旧手机号</label>
                    <div class="layui-input-inline">
                        <input type="text" name="old_mobile" id="old_mobile" autocomplete="off" class="layui-input" onchange="change_old_mobile()">
                    </div>
                    <div class="layui-form-mid layui-word-aux">旧手机号非空时，此项必填</div>
                    <div class="layui-form-mid layui-word-aux" id="old_mobile_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                </div>

                <div class="layui-form-item">
                    <label for="new_mobile" class="layui-form-label">
                        <span class="x-red">*</span>新手机号</label>
                    <div class="layui-input-inline">
                        <input type="text" name="new_mobile" id="new_mobile" autocomplete="off" required class="layui-input" onchange="change_new_mobile(this.value)">
                    </div>
                    <div class="layui-form-mid layui-word-aux" id="new_mobile_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                </div>

                <div class="layui-form-item">
                    <label for="user_id" class="layui-form-label">
                        <span class="x-red">*</span>用户ID</label>
                    <div class="layui-input-inline">
                        <input type="text" name="user_id" id="user_id" autocomplete="off" required class="layui-input" style="background: #c2c2c2;" readonly>
                    </div>
                    <div class="layui-form-mid layui-word-aux" id="user_id_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                </div>

                <div class="layui-form-item">
                    <label for="real_name" class="layui-form-label">
                        <span class="x-red">*</span>用户姓名</label>
                    <div class="layui-input-inline">
                        <input type="text" name="real_name" id="real_name" autocomplete="off" required class="layui-input" style="background: #c2c2c2;" readonly>
                    </div>
                    <div class="layui-form-mid layui-word-aux" id="real_name_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label"><span class="x-red">*</span>用户凭证压缩文件</label>
                    <div class="layui-input-inline">
                        <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                        <span id="file_name"></span>
                        <input type="file" id="file" name="file" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                    </div>
                    <div class="layui-form-mid layui-word-aux">请上传压缩文件（rar，zip，7z）</div>
                </div>

                <div class="layui-form-item">
                    <label for="L_repass" class="layui-form-label"></label>
                    <button type="submit" lay-filter="add" lay-submit="" class="layui-btn">提交</button>
                </div>
              </form>
            </div>
        </div>
        <script>
        layui.use(['layer' , 'form'] , function(){
          var form = layui.form;

          form.on('submit(add)' , function(data)
          {
            var user_id   = data.field.user_id;
            var real_name = data.field.real_name;
            var file      = data.field.file;
            if (user_id == '' || real_name == '') {
              layer.alert('请正确输入用户信息');
              return false;
            } else if (check_new_mobile !== true) {
              layer.alert('请正确输入新手机号');
              return false;
            } else if (file == '') {
              layer.alert('请上传用户凭证压缩文件');
              return false;
            }
          });
        });

        function change_old_mobile() {
          var idno       = $("#idno").val();
          var old_mobile = $("#old_mobile").val();
          if (idno == '' && old_mobile == '') {
            $("#user_id_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            $("#real_name_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            $("#idno_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            $("#old_mobile_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            layer.alert('请输入用户证件号、旧手机号');
          } else {
            $.ajax({
              url:'/user/Message/checkOldMobile',
              type:'post',
              data:{
                idno       : idno,
                old_mobile : old_mobile
              },
              dataType:'json',
              success:function(res){
                if (res['code'] === 0) {
                  if (res['data']['idno_res'] == 0) {
                    $("#idno_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  } else {
                    $("#idno_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                  }
                  if (res['data']['old_mobile_res'] == 0) {
                    $("#old_mobile_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  } else {
                    $("#old_mobile_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                  }
                  if (res['data']['user_id'] == 0 || res['data']['real_name'] == '') {
                    $("#user_id").val('');
                    $("#real_name").val('');
                    $("#user_id_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                    $("#real_name_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  } else {
                    $("#user_id").val(res['data']['user_id']);
                    $("#real_name").val(res['data']['real_name']);
                    $("#user_id_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                    $("#real_name_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                  }
                } else {
                  $("#user_id").val('');
                  $("#real_name").val('');
                  $("#user_id_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  $("#real_name_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  $("#idno_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  $("#old_mobile_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  layer.alert(res['info']);
                }
              }
            });
          } 
        }

        check_new_mobile = false;

        function change_new_mobile(value) {
          if (value == '') {
            layer.alert('请输入新手机号');
          } else {
            $.ajax({
              url:'/user/Message/checkNewMobile',
              type:'post',
              data:{
                new_mobile : value
              },
              dataType:'json',
              success:function(res){
                if (res['code'] === 0) {
                  check_new_mobile = true;
                  $("#new_mobile_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                } else {
                  check_new_mobile = false;
                  $("#new_mobile_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  layer.alert(res['info']);
                }
              }
            });
          }
        }

        function add_file() {
          $("#file").click();
        }

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
        }
      </script>
    </body>

</html>