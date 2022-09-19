<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>尊享匹配债权还本编辑</title>
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
                <form class="layui-form" method="post" action="/user/Automatch/EditZXPartialRepay" id="my_form" enctype="multipart/form-data">
                    <input type="hidden" name="platform_id" value="<{$res['platform_id']}>">
                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>ID
                        </label>
                        <div class="layui-input-inline">
                            <input type="text" name="id" autocomplete="off" class="layui-input" readonly value="<{$res['id']}>"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>付款方</label>
                        <div class="layui-input-inline">
                            <input type="text" readonly autocomplete="off" class="layui-input" value="<{$res['pay_user']}>"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="pay_plan_time" class="layui-form-label">
                            计划还款日期</label>
                        <div class="layui-input-inline">
                            <input type="text" id="pay_plan_time" name="pay_plan_time" readonly autocomplete="off" class="layui-input" value="<{$res['pay_plan_time']}>"></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>还款凭证</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_proof()">上传</button>
                            <span id="proof_name"><{$res['proof_url']}></span>
                            <input type="file" id="proof" name="proof" autocomplete="off" class="layui-input" style="display: none;" onchange="change_proof(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传压缩文件（ rar，zip，7z ）</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">保存</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer' , 'laydate'] , function() {
          var laydate = layui.laydate;

          laydate.render({
              elem: '#pay_plan_time'
          });

        });

        function add_proof() {
          $("#proof").click();
        }

        function change_proof(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#proof_name").html(new_name);
        }

        function do_add() {
          var proof      = $("#proof").val();
          var proof_name = $("#proof_name").html();
          if (proof == '' && proof_name == '') {
            layer.alert('请选择还款凭证');
          } else {
            $("#my_form").submit();
          }
        }
      </script>
    </body>

</html>