<!doctype html>
<html  class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title><{$sys_platform_name}></title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!--datatables-->
    <link rel="stylesheet" href="<{$CONST.cssPath}>/jquery.dataTables.min.css">
    <script src="<{$CONST.jsPath}>/jquery-2.1.4.min.js"></script>
    <script src="<{$CONST.jsPath}>/jquery.dataTables.min.js"></script>
</head>
<body>
    
<div class="layui-fluid">
    <div class="layui-row">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="layui-card">
                <div class="layui-card-body">
                   <!-- method="post" action="/system/config/edit" enctype="multipart/form-data" id="user_condition_form" -->
                    <form class="layui-form"  >
                       
                        <div class="layui-form-item">
                          <label class="layui-form-label">上传付款凭证</label>
                          
                          <div class="layui-input-inline">
                            <button type="button" class="layui-btn" id="logo"><i class="layui-icon"></i>上传文件</button>
                          </div>
                          <div class="layui-form-mid layui-word-aux">请上传jpg、png格式图片</div>

                        </div>

                        <div class="layui-form-item">
                          <div class="layui-input-inline" style="margin-left: 150px;">
                            <img class="layui-upload-img" id="logo_url" style="height:100px" src="<{$platformInfo['logo_url']}>">
                          </div>
                        </div>
                      

                        <div class="layui-form-item">
                          <label for="L_repass" class="layui-form-label"></label>
                          <input type="hidden" name="logo_path" id="logo_path" class="layui-input">

                          <button class="layui-btn" lay-submit="" lay-filter="add" >保存</button>

                        </div>

                        
                        
                      </form>
                </div>
            </div>
          </div>
        </div>
    </div>
</div>
<script>

layui.use(['form', 'layer','upload'],function() {
    $ = layui.jquery;
    var form = layui.form;
    var layer = layui.layer;
    var upload = layui.upload;
    //自定义验证规则
    var element = layui.element;

    //logo图片上传
    var uploadInst = upload.render({
        elem: '#logo'
        ,url: '/debtMarket/debtPurchase/upload' //改成您自己的上传接口
        ,done: function(res){
          //如果上传失败
          if(res.code > 0){
            return layer.msg(res.info);
          }else{
            $('#logo_url').attr('src', res.data.file_url); //图片链接（base64）

            $("#logo_path").val(res.data.file_path);
          }
          //上传成功
        }})




      form.on('submit(add)', function (data) {
            //发异步，把数据提交给php
            var form = data.field;
           
            $.ajax({
                url: '/debtMarket/debtPurchase/uploadCredentials?debt_id='+<{$_GET['id']}>,
                data: data.field,
                type: "POST",
                dataType:'json',
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert("保存成功",
                                
                                function(data,item){
                                                    var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                                                    parent.layer.close(index); //再执行关闭
                                                    parent.location.reload();
                                                });
                    } else {
                        layer.alert(res.info);
                    }
                }
            })
            return false;
        });

  });

  function add_logo() {
          $("#logo").click();
      }

    function change_logo(name) {
        var string = name.lastIndexOf("\\");
        var new_name = name.substring(string + 1);
        $("#logo_file_name").html(new_name);
    }

    function add_banner() {
          $("#banner").click();
      }

    function change_banner(name) {
        var string = name.lastIndexOf("\\");
        var new_name = name.substring(string + 1);
        $("#banner_file_name").html(new_name);
    }

    function user_upload() {
        if ($("#doh").hasClass("disabled")) {
            layer.alert('处理中，请勿重复提交');
        }
        var template = $("#template").val();
        if (template == '') {
            layer.alert('请选择上传文件');
        } else {
            $("#doh").addClass("disabled")
            $("#doh").html("上传中...")
            $("#user_condition_form").submit();
        }
    }
</script>
</body>

</html>
