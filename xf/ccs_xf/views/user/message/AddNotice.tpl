<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>新增公告</title>
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
                    <label class="layui-form-label">
                        <span class="x-red">*</span>发布方式</label>
                    <div class="layui-input-inline">
                        <input type="radio" name="type" value="1" title="立即发布" lay-filter="type" checked>
                        <input type="radio" name="type" value="2" title="定时发布" lay-filter="type">
                    </div>
                </div>

                <div class="layui-form-item" id="time_div" style="display: none;">
                    <label for="start_time" class="layui-form-label">
                        <span class="x-red">*</span>发布时间</label>
                    <div class="layui-input-inline">
                        <input type="text" id="start_time" name="start_time" readonly autocomplete="off" class="layui-input"></div>
                </div>

                <div class="layui-form-item">
                    <label for="title" class="layui-form-label">
                        <span class="x-red">*</span>标题</label>
                    <div class="layui-input-inline">
                        <input type="text" id="title" name="title" lay-verify="required" autocomplete="off" class="layui-input" style="width: 300px;" onkeyup="check_title()" onchange="check_title()"></div>
                    <div class="layui-form-mid layui-word-aux" id="check_title" style="margin: 0px 0px 0px 115px;">0/14</div>
                </div>

                <div class="layui-form-item">
                    <label for="abstract" class="layui-form-label">
                        摘要</label>
                    <div class="layui-input-inline">
                        <input type="text" id="abstract" name="abstract" autocomplete="off" class="layui-input" style="width: 400px;" onkeyup="check_abstract()" onchange="check_abstract()"></div>
                    <div class="layui-form-mid layui-word-aux" id="check_abstract" style="margin: 0px 0px 0px 215px;">0/30</div>
                </div>

                <div class="layui-form-item layui-form-text">
                    <label for="user_id" class="layui-form-label">
                        <span class="x-red">*</span>内容</label>
                    <div class="layui-input-inline" style="background-color: white;width: 800px;">
                      <textarea name="content" id="content" style="display: none;"></textarea>
                    </div>
                </div>

                <div class="layui-form-item layui-form-text">
                    <label for="user_id" class="layui-form-label"></label>
                    <div class="layui-input-inline">
                    </div>
                    <div class="layui-form-mid layui-word-aux">上传图片宽度不大于320px</div>
                </div>

                <div class="layui-form-item">
                    <label for="L_repass" class="layui-form-label"></label>
                    <button type="button" lay-filter="add" lay-submit="" class="layui-btn">提交</button>
                </div>
              </form>
            </div>
        </div>
        <script>
        layui.use(['layedit' , 'layer' , 'form' , 'laydate'] , function(){
          var layedit = layui.layedit;
          var laydate = layui.laydate;
          var form    = layui.form;

          laydate.render({
            elem: '#start_time'
            ,type: 'datetime'
          });

          layedit.set({
            uploadImage: {
              url: '/user/Message/UploadPicture' //接口url
              ,type: 'post' //默认post
            }
          });

          var index = layedit.build('content'); //建立编辑器

          form.on('radio(type)', function(data){
            var val=data.value;
            $("#start_time").val('');
            if (val == 1) {
              $("#time_div").prop('style','display: none;');
            } else if (val == 2) {
              $("#time_div").prop('style','');
            }
          });

          form.on('submit(add)' , function(data)
          {
            var content = layedit.getContent(index);
            if (data.field.start_time == '' && data.field.type == 2) {
              layer.alert('请选择发布时间');
            } else if (data.field.title == '') {
              layer.alert('请输入标题');
            } else if (data.field.title.length > 14) {
              layer.alert('标题超过规定字数');
            // } else if (data.field.abstract == '') {
            //   layer.alert('请输入摘要');
            } else if (data.field.abstract.length > 30) {
              layer.alert('摘要超过规定字数');
            } else if (content == '') {
              layer.alert('请输入内容');
            } else {
              $.ajax({
                url:'/user/Message/AddNotice',
                type:'post',
                data:{
                  start_time : data.field.start_time,
                  title      : data.field.title,
                  abstract   : data.field.abstract,
                  content    : content
                },
                dataType:'json',
                success:function(res){
                  if (res['code'] === 0) {
                    layer.msg(res['info'] , {time:1000,icon:1} , function(){
                      parent.location.reload();
                      var index = parent.layer.getFrameIndex(window.name);
                      parent.layer.close(index);
                    });
                  } else {
                    layer.alert(res['info']);
                  }
                }
              });
            }
            return false;
          });
        });

        function check_title()
        {
          var value = $("#title").val();
          $("#check_title").html(value.length+'/14');
        }

        function check_abstract()
        {
          var value = $("#abstract").val();
          $("#check_abstract").html(value.length+'/30');
        }
      </script>
    </body>

</html>