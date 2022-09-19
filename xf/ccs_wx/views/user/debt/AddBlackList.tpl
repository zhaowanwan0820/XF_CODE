<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>添加债转黑名单</title>
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
                            <span class="x-red">*</span>所属平台</label>
                      <div class="layui-input-inline">
                        <input type="radio" class="type" name="type" value="1" title="尊享" checked>
                        <input type="radio" class="type" name="type" value="2" title="普惠">
                      </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="deal_name" class="layui-form-label">
                            <span class="x-red">*</span>借款标题</label>
                        <div class="layui-input-inline">
                            <input type="text" id="deal_name" autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">增加</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer']);

        function do_add() {
          var type      = $(".type:checked").val();
          var deal_name = $("#deal_name").val();
          if (type != 1 && type != 2) {
            layer.alert('请选择所属平台');
          } else if (deal_name == '') {
            layer.alert('请正确输入借款标题');
          } else {
            $.ajax({
              url:'/user/Debt/AddBlackList',
              type:'post',
              data:{
                'type':type,
                'deal_name':deal_name
              },
              dataType:'json',
              success:function(res) {
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
        }
      </script>
    </body>

</html>