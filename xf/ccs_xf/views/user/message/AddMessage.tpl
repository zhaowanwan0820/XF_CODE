<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>新增消息</title>
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
                    <label class="layui-form-label">
                        <span class="x-red">*</span>推送用户范围</label>
                    <div class="layui-input-inline">
                        <input type="radio" name="user_1" value="1" title="全量在途用户" lay-filter="user_1" checked>
                        <input type="radio" name="user_1" value="2" title="指定在途用户" lay-filter="user_1">
                    </div>
                    <div class="layui-form-mid layui-word-aux">推送全量在途用户预计完成时间需要2.2分钟</div>
                </div>

                <div id="zhiding" style="display: none;">

                  <div class="layui-form-item">
                      <label class="layui-form-label">
                          <span class="x-red">*</span>指定用户方式</label>
                      <div class="layui-input-inline">
                          <!-- <input type="radio" name="user_2" value="1" title="输入项目ID" lay-filter="user_2"> -->
                          <input type="radio" name="user_2" value="2" title="输入借款编号" lay-filter="user_2" checked>
                          <input type="radio" name="user_2" value="3" title="输入用户ID" lay-filter="user_2">
                          <input type="radio" name="user_2" value="4" title="上传用户ID" lay-filter="user_2">
                      </div>
                  </div>

                  <div class="layui-form-item" id="platform_div">
                      <label class="layui-form-label">
                          <span class="x-red">*</span>指定平台</label>
                      <div class="layui-input-inline">
                          <input type="radio" name="platform" value="1" title="尊享" lay-filter="platform" checked>
                          <input type="radio" name="platform" value="2" title="普惠" lay-filter="platform">
                      </div>
                  </div>

                  <div class="layui-form-item layui-form-text" id="project_id_div" style="display: none;">
                      <label for="project_id" class="layui-form-label">
                          <span class="x-red">*</span>输入项目ID</label>
                      <div class="layui-input-inline">
                          <textarea placeholder="请输入项目ID（多个以英文逗号,分隔）" id="project_id" name="project_id" class="layui-textarea" style="width: 400px;"></textarea>
                      </div>
                  </div>

                  <div class="layui-form-item layui-form-text" id="deal_id_div">
                      <label for="deal_id" class="layui-form-label">
                          <span class="x-red">*</span>输入借款编号</label>
                      <div class="layui-input-inline">
                          <textarea placeholder="请输入借款编号（多个以英文逗号,分隔）" id="deal_id" name="deal_id" class="layui-textarea" style="width: 400px;"></textarea>
                      </div>
                  </div>

                  <div class="layui-form-item layui-form-text" id="user_id_div" style="display: none;">
                      <label for="user_id" class="layui-form-label">
                          <span class="x-red">*</span>输入用户ID</label>
                      <div class="layui-input-inline">
                          <textarea placeholder="请输入用户ID（多个以英文逗号,分隔）" id="user_id" name="user_id" class="layui-textarea" style="width: 400px;"></textarea>
                      </div>
                  </div>

                  <div class="layui-form-item layui-form-text" id="user_id_file_div" style="display: none;">
                      <label for="user_id" class="layui-form-label">
                          <span class="x-red">*</span>上传用户ID</label>
                      <div class="layui-input-inline">
                          <button type="button" class="layui-btn" id="upload">
                            <i class="layui-icon">&#xe67c;</i>上传文件
                          </button>
                          <input type="hidden" name="user_id_file" id="user_id_file" value="">
                          <span id="file_name"></span>
                      </div>
                      <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传 xls 文件（数据量不可超过一万行） <a href="/user/Message/DownloadUserIdTemplate" style="color: blue;">下载模板</a></div>
                  </div>

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
        layui.use(['layedit' , 'layer' , 'form' , 'laydate' , 'upload' , 'element'] , function(){
          var layedit = layui.layedit;
          var laydate = layui.laydate;
          var form    = layui.form;
          var upload  = layui.upload;
          var element = layui.element;

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

          form.on('radio(user_1)', function(data){
            var val=data.value;
            if (val == 1) {
              $("#zhiding").prop('style','display: none;');
            } else if (val == 2) {
              $("#zhiding").prop('style','');
            }
          });

          form.on('radio(user_2)', function(data){
            var val=data.value;
            $("#project_id").val('');
            $("#deal_id").val('');
            $("#user_id").val('');
            $("#user_id_file").val('');
            $("#file_name").html('');
            $("#project_id_div").prop('style' , 'display: none;');
            $("#deal_id_div").prop('style' , 'display: none;');
            $("#user_id_div").prop('style' , 'display: none;');
            $("#user_id_file_div").prop('style' , 'display: none;');
            $("#platform_div").prop('style' , 'display: none;');
            if (val == 1) {
              $("#project_id_div").prop('style' , '');
              $("#platform_div").prop('style' , '');
            } else if (val == 2) {
              $("#deal_id_div").prop('style' , '');
              $("#platform_div").prop('style' , '');
            } else if (val == 3) {
              $("#user_id_div").prop('style' , '');
            } else if (val == 4) {
              $("#user_id_file_div").prop('style' , '');
            }
          });

          var uploadInst = upload.render({
            elem: '#upload' //绑定元素
            ,url: '/user/Message/UploadXls' //上传接口
            ,accept: 'file'
            ,exts: 'xls'
            ,field: 'user_id_file'
            ,done: function(res){
              //上传完毕回调
              if (res['code'] === 0) {
                layer.msg(res['info'] , {time:1000,icon:1} , function(){
                  $("#user_id_file").val(res['data']['url']);
                  $("#file_name").html(res['data']['name']);
                });
              } else {
                layer.alert(res['info']);
              }
            }
            ,error: function(){
              //请求异常回调
            }
          });

          form.on('submit(add)' , function(data)
          {
            var content = layedit.getContent(index);
            if (data.field.start_time == '' && data.field.type == 2) {
              layer.alert('请选择发布时间');
            } else if (data.field.project_id == '' && data.field.user_2 == 1 && data.field.user_1 == 2) {
              layer.alert('请输入项目ID');
            } else if (data.field.deal_id == '' && data.field.user_2 == 2 && data.field.user_1 == 2) {
              layer.alert('请输入借款编号');
            } else if (data.field.user_id == '' && data.field.user_2 == 3 && data.field.user_1 == 2) {
              layer.alert('请输入用户ID');
            } else if (data.field.user_id_file == '' && data.field.user_2 == 4 && data.field.user_1 == 2) {
              layer.alert('请上传用户ID');
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
              var loading = layer.load(2, {
                shade: [0.3],
                time: 3600000
              });
              $.ajax({
                url:'/user/Message/AddMessage',
                type:'post',
                data:{
                  start_time   : data.field.start_time,
                  user_1       : data.field.user_1,
                  user_2       : data.field.user_2,
                  platform     : data.field.platform,
                  project_id   : data.field.project_id,
                  deal_id      : data.field.deal_id,
                  user_id      : data.field.user_id,
                  user_id_file : data.field.user_id_file,
                  start_time   : data.field.start_time,
                  title        : data.field.title,
                  abstract     : data.field.abstract,
                  content      : content
                },
                dataType:'json',
                success:function(res){
                  layer.close(loading);
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

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
        }

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