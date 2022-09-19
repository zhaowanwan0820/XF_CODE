<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>回复意见反馈</title>
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

                <div class="layui-form-item layui-form-text">
                    <label for="deal_id" class="layui-form-label">
                        内容</label>
                    <div class="layui-input-inline">
                        <textarea class="layui-textarea" style="width: 400px;" disabled><{$res['content']}></textarea>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        <span class="x-red">*</span>回复状态</label>
                    <div class="layui-input-block">
                        <{if $res['status'] != 3 }>
                        <input type="radio" name="status" value="2" title="处理中" lay-filter="status">
                        <{/if}>
                        <input type="radio" name="status" value="3" title="已回复" lay-filter="status" checked>
                    </div>
                </div>

                <div class="layui-form-item layui-form-text" id="re_content_div">
                    <label for="deal_id" class="layui-form-label">
                        <span class="x-red">*</span>回复内容</label>
                    <div class="layui-input-inline">
                        <textarea name="re_content" id="re_content" class="layui-textarea" style="width: 400px;"><{$res['re_content']}></textarea>
                    </div>
                </div>

                <div class="layui-form-item" id="time_div">
                    <label class="layui-form-label">
                        提交时间</label>
                    <div class="layui-input-inline">
                        <input type="text" readonly autocomplete="off" class="layui-input" value="<{$res['add_time']}>"></div>
                </div>

                <div class="layui-form-item" id="time_div">
                    <label class="layui-form-label">
                        用户ID</label>
                    <div class="layui-input-inline">
                        <input type="text" readonly autocomplete="off" class="layui-input" value="<{$res['user_id']}>"></div>
                </div>

                <div class="layui-form-item" id="time_div">
                    <label class="layui-form-label">
                        用户姓名</label>
                    <div class="layui-input-inline">
                        <input type="text" readonly autocomplete="off" class="layui-input" value="<{$res['user_real_name']}>"></div>
                </div>

                <div class="layui-form-item" id="time_div">
                    <label class="layui-form-label">
                        用户手机号</label>
                    <div class="layui-input-inline">
                        <input type="text" readonly autocomplete="off" class="layui-input" value="<{$res['user_mobile']}>"></div>
                </div>

                <div class="layui-form-item" id="time_div">
                    <label class="layui-form-label">
                        用户证件号</label>
                    <div class="layui-input-inline">
                        <input type="text" readonly autocomplete="off" class="layui-input" value="<{$res['user_idno']}>"></div>
                </div>

                <div class="layui-form-item" id="time_div">
                    <label class="layui-form-label">
                        用户银行卡号</label>
                    <div class="layui-input-inline">
                        <input type="text" readonly autocomplete="off" class="layui-input" value="<{$res['user_bankcard']}>"></div>
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

          form.on('submit(add)' , function(data)
          {
            if (data.field.status == 3 && data.field.re_content == '') {
              layer.alert('请输入回复内容');
            } else {
              $.ajax({
                url:'/user/Message/EditFeedback',
                type:'post',
                data:{
                  id         : data.field.id,
                  status     : data.field.status,
                  re_content : data.field.re_content
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
            $("#re_content").val('');
            if (val == 2) {
              $("#re_content_div").prop('style','display: none;');
            } else if (val == 3) {
              $("#re_content_div").prop('style','');
            }
          });
        });
      </script>
    </body>

</html>