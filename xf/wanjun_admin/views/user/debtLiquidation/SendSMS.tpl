<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>发送短信</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
        <style type="text/css">
          .layui-form-label {
            width: 190px
          }
        </style>
        <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
        <!--[if lt IE 9]>
            <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
            <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
        <![endif]--></head>
    
    <body>
      <div class="x-nav">
            <span class="layui-breadcrumb">
                <a href="">短信管理</a>
                <a>
                    <cite>发送短信</cite></a>
            </span>
          <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8" onclick="location.reload()" title="刷新">
              <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
          </a>
      </div>
        <div class="layui-fluid">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-body">
                          <form class="layui-form">

                              <div class="layui-form-item">
                                  <label for="mobile" class="layui-form-label">
                                      <span class="x-red">*</span>手机号</label>
                                  <div class="layui-input-inline">
                                      <input type="text" id="mobile" autocomplete="off" class="layui-input" value="">
                                  </div>
                              </div>

                              <div class="layui-form-item">
                                  <label for="realname" class="layui-form-label">
                                      <span class="x-red">*</span>用户姓名</label>
                                  <div class="layui-input-inline">
                                      <input type="text" id="realname" autocomplete="off" class="layui-input" value="">
                                  </div>
                              </div>

                              <div class="layui-form-item">
                                  <label for="email" class="layui-form-label">
                                      <span class="x-red">*</span>邮箱</label>
                                  <div class="layui-input-inline">
                                      <input type="text" id="email" autocomplete="off" class="layui-input" value="" style="width: 300px">
                                  </div>
                              </div>

                              <div class="layui-form-item">
                                  <label for="L_repass" class="layui-form-label"></label>
                                  <button type="button" class="layui-btn"  onclick="do_add()">发送</button>
                              </div>
                              
                          </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
          form    = layui.form;
          layer   = layui.layer;
          table   = layui.table;
          laydate = layui.laydate;

        });

        function do_add() {
          var mobile   = $("#mobile").val();
          var realname = $("#realname").val();
          var email    = $("#email").val();

          if (mobile == '') {
            layer.msg('请输入手机号' , {icon:2 , time:2000});
          } if (realname == '') {
            layer.msg('请输入用户姓名' , {icon:2 , time:2000});
          } if (email == '') {
            layer.msg('请输入邮箱' , {icon:2 , time:2000});
          } else {
            var loading = layer.load(2, {
              shade: [0.3],
              time: 3600000
            });
            $.ajax({
              url:'/user/DebtLiquidation/SendSMS',
              type:'post',
              data:{
                mobile   : mobile,
                realname : realname,
                email    : email
              },
              dataType:'json',
              success:function(res){
                layer.close(loading);
                if (res['code'] === 0) {
                  layer.msg(res['info'] , {time:1000,icon:1} , function(){
                    location.reload();
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