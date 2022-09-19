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
                <form class="layui-form" method="post" action="/user/exclusivePurchase/AddAssignee" enctype="multipart/form-data" id="my_form">

                    <div class="layui-form-item">
                        <label for="user_id" class="layui-form-label">
                            <span class="x-red">*</span>用户ID</label>
                        <div class="layui-input-inline">
                            <input type="text" name="user_id" id="user_id" autocomplete="off" class="layui-input" onchange="change_user_id(this.value)"></div>

                        <div class="layui-form-mid layui-word-aux user_id_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                        <div class="layui-form-mid layui-word-aux" >请先将受让人注册为“先锋用户”并完成“法大大企业认证”。</div>
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
                            <input type="text" name="limit" id="limit" autocomplete="off" class="layui-input" value="" onchange="change_limit(this.value)"></div>
                        <div class="layui-form-mid layui-word-aux" >元</div>
                        <div class="layui-form-mid layui-word-aux" id="limit_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="limit" class="layui-form-label">
                            <span class="x-red">*</span>收购发布时效(小时)</label>
                        <div class="layui-input-inline">
                            <input type="text" name="purchase_time" id="purchase_time" autocomplete="off" class="layui-input" value=""  ></div>
                        <div class="layui-form-mid layui-word-aux" id="limit_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="limit" class="layui-form-label">
                            <span class="x-red">*</span>最高收购折扣</label>
                        <div class="layui-input-inline">
                            <input type="text" name="purchase_discount" id="purchase_discount" autocomplete="off" class="layui-input" value="" ></div>

                        <div class="layui-form-mid layui-word-aux" id="limit_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                        <div class="layui-form-mid layui-word-aux" >输入范围：0.01至10.00</div>
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
                    <div class="layui-form-item" id="file_a_div" >
                        <label class="layui-form-label"><span class="x-red">*</span>分配用户ID文件</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file_a()">上传</button>
                            <span id="file_a_name"></span>
                            <input type="file" id="file_a" name="file_a" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name_a(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="file_a_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                        <div class="layui-form-mid layui-word-aux">请上传 xlsx 文件（数据量不可超过十万行） <a href="/user/exclusivePurchase/AddAssignee?download=1" style="color: blue;">下载模板</a></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn" onclick="add()">增加</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer'] , function(){
          form=layui.form;


        });

        function add() {
          var user_id = $("#user_id").val();
          var limit   = $("#limit").val();
          var file    = $("#file").val();
          var file_a  = $("#file_a").val();
            var purchase_time = $("#purchase_time").val();
            var purchase_discount = $("#purchase_discount").val();
          if (isNaN(user_id) || user_id < 1) {
            layer.msg('请正确输入用户ID' , {icon:2 , time:2000});
          } else if (isNaN(limit) || limit <= 0 || limit > 1000000000) {
            layer.msg('请正确输入受让额度' , {icon:2 , time:2000});
          }else if (isNaN(purchase_time) || purchase_time <= 0  ) {
              layer.msg('请正确输入收购时效' , {icon:2 , time:2000});
          }else if (isNaN(purchase_discount) || purchase_discount < 0.01 || purchase_discount > 10) {
              layer.msg('请正确输入收购最高折扣' , {icon:2 , time:2000});
          } else if (file == '') {
            layer.msg('请上传合作框架协议' , {icon:2 , time:2000});
          } else if (file_a == '') {
            layer.msg('请上传分配用户ID文件' , {icon:2 , time:2000});
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

        function add_file_a() {
          $("#file_a").click();
        }

        function change_name_a(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_a_name").html(new_name);
          $("#file_a_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
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
              url:'/user/Debt/PurchaseAssigneeChangeUserId',
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