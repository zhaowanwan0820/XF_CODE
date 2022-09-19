<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>审核企业</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
        <style type="text/css">
          .layui-form-label {
            width: 140px;
          }
        </style>
        <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
        <!--[if lt IE 9]>
            <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
            <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
        <![endif]--></head>
    
    <body>
        <div class="layui-fluid">
            <div class="layui-row">
                <form class="layui-form" method="post" action="/user/Enterprise/VerifyEnterprise" enctype="multipart/form-data" id="my_form">
                    <input type="hidden" name="id" value="<{$res['id']}>">
                    <input type="hidden" name="status" id="status" value="<{$res['status']}>">
                    <input type="hidden" name="update_time" value="<{$res['update_time']}>">

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>企业全称</label>
                        <div class="layui-input-inline">
                            <input type="text" value="<{$res['company_name']}>" readonly autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>企业证件号码</label>
                        <div class="layui-input-inline">
                            <input type="text" value="<{$res['credentials_no']}>" readonly autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">三证合一营业执照</div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>法定代表人姓名</label>
                        <div class="layui-input-inline">
                            <input type="text" value="<{$res['legalbody_name']}>" readonly autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>法定代表人证件号码</label>
                        <div class="layui-input-inline">
                            <input type="text" value="<{$res['legalbody_credentials_no']}>" readonly autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">身份证号，另：如含有X，X需要大写</div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>企业联系电话</label>
                        <div class="layui-input-inline">
                            <input type="text" value="<{$res['legalbody_mobile']}>" readonly autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">手机号</div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>企业注册地址</label>
                        <div class="layui-input-inline">
                            <input type="text" value="<{$res['registration_address']}>" readonly autocomplete="off" class="layui-input" style="width: 380px"></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>企业联系地址</label>
                        <div class="layui-input-inline">
                            <input type="text" value="<{$res['contract_address']}>" readonly autocomplete="off" class="layui-input" style="width: 380px"></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>统一社会信用代码电子版</label>
                        <div class="layui-input-inline img_list">
                            <img src="<{$res['credit_code_file_name']}>" width="800px">
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>授权委托书电子版</label>
                        <div class="layui-input-inline img_list">
                            <img src="<{$res['power_attorney_file_name']}>" width="800px">
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn" onclick="verify(3)">通过</button>
                        <button type="button" class="layui-btn layui-btn-danger" onclick="verify(2)">拒绝</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer'] , function(){
          form  = layui.form;
          layer = layui.layer;
          layer.photos({
            photos: '.img_list'
          });
        });

        function verify(value) {
          $("#status").val(value);
          $("#my_form").submit();
        }
      </script>
    </body>

</html>