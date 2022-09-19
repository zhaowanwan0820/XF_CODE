<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>添加受让方</title>
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
                <form class="layui-form" method="post" action="/user/Debt/AddAssignee" enctype="multipart/form-data" id="my_form">

                    <div class="layui-form-item">
                        <label for="user_id" class="layui-form-label">
                            <span class="x-red">*</span>用户ID</label>
                        <div class="layui-input-inline">
                            <input type="text" name="user_id" id="user_id" autocomplete="off" class="layui-input" onchange="change_user_id(this.value)"></div>
                        <div class="layui-form-mid layui-word-aux user_id_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            姓名</label>
                        <div class="layui-input-inline">
                            <input type="text" id="real_name" disabled autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux user_id_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            手机号</label>
                        <div class="layui-input-inline">
                            <input type="text" id="mobile" disabled autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux user_id_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            证件号码</label>
                        <div class="layui-input-inline">
                            <input type="text" id="idno" disabled autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux user_id_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="limit" class="layui-form-label">
                            <span class="x-red">*</span>受让额度</label>
                        <div class="layui-input-inline">
                            <input type="text" name="limit" id="limit" autocomplete="off" class="layui-input" value="5000000" onchange="change_limit(this.value)"></div>
                        <div class="layui-form-mid layui-word-aux" id="limit_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>合作框架协议</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                            <span id="file_name"></span>
                            <input type="file" id="file" name="file" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="file_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                        <div class="layui-form-mid layui-word-aux">请上传压缩文件（rar，zip，7z）</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn" onclick="add()">增加</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer']);

        function add() {
          var user_id = $("#user_id").val();
          var limit   = $("#limit").val();
          var file    = $("#file").val();
          if (isNaN(user_id) || user_id < 1) {
            layer.msg('请正确输入用户ID' , {icon:2 , time:2000});
          } else if (isNaN(limit) || limit <= 0 || limit > 999999999) {
            layer.msg('请正确输入受让额度' , {icon:2 , time:2000});
          } else if (file == '') {
            layer.msg('请上传合作框架协议' , {icon:2 , time:2000});
          } else {
            $("#my_form").submit();
          }
        }

        function add_file() {
          $("#file").click();
        }

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
          $("#file_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
        }

        function change_limit(value) {
          if (!isNaN(value) && value > 0 && value <= 999999999) {
            $("#limit_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
          } else {
            $("#limit_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
          }
        }

        function change_user_id(value) {
          if (!isNaN(value) && value > 0) {
            $.ajax({
              url:'/user/Debt/AssigneeChangeUserId',
              type:'post',
              dataType:'json',
              data:{user_id:value},
              success:function(res){
                if (res['code'] == 0) {
                  $("#user_id").val(res['data']['user_id']);
                  $("#real_name").val(res['data']['real_name']);
                  $("#mobile").val(res['data']['mobile']);
                  $("#idno").val(res['data']['idno']);
                  $(".user_id_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                } else {
                  layer.msg(res['info'] , {icon:2 , time:2000});
                  $("#real_name").val('');
                  $("#mobile").val('');
                  $("#idno").val('');
                  $(".user_id_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                }
              }
            });
          } else {
            $("#real_name").val('');
            $("#mobile").val('');
            $("#idno").val('');
            $(".user_id_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
          }
        }
      </script>
    </body>

</html>