<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>借款人还款凭证详情</title>
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

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        用户ID</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['user_id']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        用户姓名</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['real_name']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        用户证件号</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['idno']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        手机号</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['mobile']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        上传时间</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['add_time']}>">
                    </div>
                </div>

                <{foreach $res['proof'] as $k => $v}>
                <div class="layui-form-item">
                    <label class="layui-form-label">还款凭证<{$k+1}></label>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$v}>" width="400px">
                    </div>
                </div>
                <{/foreach}>
              </form>
            </div>
        </div>
        <script>
        layui.use(['layer' , 'form'] , function(){
          var form  = layui.form;
          var layer = layui.layer;
          layer.photos({
            photos: '.img_list'
          });
        });
      </script>
    </body>

</html>