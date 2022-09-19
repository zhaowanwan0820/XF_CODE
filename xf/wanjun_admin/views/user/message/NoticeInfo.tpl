<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>公告详情</title>
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

                <input type="hidden" name="id" value="<{$res['id']}>">

                <div class="layui-form-item" id="time_div">
                    <label for="start_time" class="layui-form-label">
                        <span class="x-red">*</span>发布时间</label>
                    <div class="layui-input-inline">
                        <input type="text" id="start_time" name="start_time" readonly autocomplete="off" class="layui-input" value="<{$res['start_time']}>" disabled></div>
                </div>

                <div class="layui-form-item">
                    <label for="title" class="layui-form-label">
                        <span class="x-red">*</span>标题</label>
                    <div class="layui-input-inline">
                        <input type="text" id="title" name="title" lay-verify="required" autocomplete="off" class="layui-input" value="<{$res['title']}>" style="width: 300px;" disabled></div>
                </div>

                <div class="layui-form-item">
                    <label for="abstract" class="layui-form-label">
                        <span class="x-red">*</span>摘要</label>
                    <div class="layui-input-inline">
                        <input type="text" id="abstract" name="abstract" lay-verify="required" autocomplete="off" class="layui-input" value="<{$res['abstract']}>" style="width: 400px;" disabled></div>
                </div>

                <div class="layui-form-item layui-form-text">
                    <label for="user_id" class="layui-form-label">
                        <span class="x-red">*</span>内容</label>
                    <div class="layui-input-inline" style="background-color: white;width: 800px;">
                      <textarea name="content" id="content" style="display: none;"><{$res['content']}></textarea>
                    </div>
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

          var index = layedit.build('content',{tool:[]}); //建立编辑器
        });
      </script>
    </body>

</html>