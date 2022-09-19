<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>审核申请</title>
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
                        <input type="text" autocomplete="off" class="layui-input" style="background: #c2c2c2;" readonly value="<{$res['user_id']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        用户姓名</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" style="background: #c2c2c2;" readonly value="<{$res['real_name']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        用户证件号</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" style="background: #c2c2c2;" readonly value="<{$res['idno']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        旧手机号</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" style="background: #c2c2c2;" readonly value="<{$res['old_mobile']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        新手机号</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" style="background: #c2c2c2;" readonly value="<{$res['new_mobile']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        申请人</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" style="background: #c2c2c2;" readonly value="<{$res['add_user_name']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        申请时间</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" style="background: #c2c2c2;" readonly value="<{$res['add_time']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">用户凭证压缩文件</label>
                    <div class="layui-input-inline">
                    </div>
                    <div class="layui-form-mid layui-word-aux"><a href="<{$res['photograph']}>" style="color: blue;" target="_blank">下载用户凭证压缩文件</a></div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        <span class="x-red">*</span>审核状态</label>
                    <div class="layui-input-block">
                        <input type="radio" name="status" value="2" title="审核通过" lay-filter="status" checked>
                        <input type="radio" name="status" value="3" title="审核拒绝" lay-filter="status">
                    </div>
                </div>

                <div class="layui-form-item layui-form-text" id="reason_div" style="display: none;">
                    <label for="reason" class="layui-form-label">
                        <span class="x-red">*</span>拒绝原因</label>
                    <div class="layui-input-inline">
                        <textarea name="reason" id="reason" class="layui-textarea" style="width: 400px;"></textarea>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label for="L_repass" class="layui-form-label"></label>
                    <button type="button" lay-filter="add" lay-submit="" class="layui-btn">提交</button>
                </div>
              </form>
            </div>
        </div>
        <script>
        layui.use(['layer' , 'form'] , function(){
          var form = layui.form;

          form.on('submit(add)' , function(data)
          {
            if (data.field.status == 3 && data.field.reason == '') {
              layer.alert('请输入拒绝原因');
            } else {
              $.ajax({
                url:'/user/Message/auditUserMobile',
                type:'post',
                data:{
                  id     : data.field.id,
                  status : data.field.status,
                  reason : data.field.reason
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

          form.on('radio(status)', function(data){
            var val=data.value;
            $("#reason").val('');
            if (val == 2) {
              $("#reason_div").prop('style','display: none;');
            } else if (val == 3) {
              $("#reason_div").prop('style','');
            }
          });
        });
      </script>
    </body>

</html>