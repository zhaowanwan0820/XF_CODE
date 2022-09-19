<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>新增债转通知</title>
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
                        <input type="radio" class="platform_id" name="platform_id" value="1" title="尊享" checked>
                        <input type="radio" class="platform_id" name="platform_id" value="2" title="普惠">
                      </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="agency_name" class="layui-form-label">
                            担保方名称</label>
                        <div class="layui-input-inline">
                            <input type="text" id="agency_name" name="agency_name" autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">
                          非必填，但担保方名称、咨询方名称、债务方名称需要至少填写一项。
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="advisory_name" class="layui-form-label">
                            咨询方名称</label>
                        <div class="layui-input-inline">
                            <input type="text" id="advisory_name" name="advisory_name" autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">
                          非必填，但担保方名称、咨询方名称、债务方名称需要至少填写一项。
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="company_name" class="layui-form-label">
                            债务方名称</label>
                        <div class="layui-input-inline">
                            <input type="text" id="company_name" name="company_name" autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">
                          非必填，但担保方名称、咨询方名称、债务方名称需要至少填写一项。
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="start_time" class="layui-form-label">
                            债转起始时间</label>
                        <div class="layui-input-inline">
                          <input type="text" class="layui-input" name="start_time" id="start_time" readonly>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="end_time" class="layui-form-label">
                            债转结束时间</label>
                        <div class="layui-input-inline">
                          <input type="text" class="layui-input" name="end_time" id="end_time" readonly>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="email_address" class="layui-form-label">
                            <span class="x-red">*</span>接收邮件邮箱</label>
                        <div class="layui-input-inline">
                            <textarea name="email_address" id="email_address" class="layui-textarea"></textarea>
                        </div>
                        <div class="layui-form-mid layui-word-aux">
                          多个邮箱请用英文分号;分隔<br>
                          例：1234567@qq.com;1234567@163.com
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">增加</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer', 'laydate'],function(){
          var laydate = layui.laydate;
          var form    = layui.form;

          laydate.render({
            elem: '#start_time',
            type: 'datetime'
          });

          laydate.render({
            elem: '#end_time',
            type: 'datetime'
          });
        });

        function do_add() {
          var platform_id   = $(".platform_id:checked").val();
          var agency_name   = $("#agency_name").val();
          var advisory_name = $("#advisory_name").val();
          var company_name  = $("#company_name").val();
          var start_time    = $("#start_time").val();
          var end_time      = $("#end_time").val();
          var email_address = $("#email_address").val();
          if (!platform_id) {
            layer.alert('请选择所属平台');
          } else if (agency_name == '' && advisory_name == '' && company_name == '') {
            layer.alert('担保方名称、咨询方名称、债务方名称需要至少填写一项');
          } else if (email_address == '') {
            layer.alert('请输入接收邮件邮箱');
          } else {
            $.ajax({
              url:'/user/Loan/AddEmailNotice',
              type:'post',
              dataType:'json',
              data:{
                platform_id   : platform_id,
                agency_name   : agency_name,
                advisory_name : advisory_name,
                company_name  : company_name,
                start_time    : start_time,
                end_time      : end_time,
                email_address : email_address
              },
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
        }
      </script>
    </body>

</html>