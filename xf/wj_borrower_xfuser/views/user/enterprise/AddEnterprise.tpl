<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>新增企业</title>
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
                <form class="layui-form" method="post" action="/user/Enterprise/AddEnterprise" enctype="multipart/form-data" id="my_form">
                    <input type="hidden" name="enterprise_id" id="enterprise_id" value="0">
                    <input type="hidden" name="user_id" id="user_id" value="0">

                    <div class="layui-form-item">
                        <label for="company_name" class="layui-form-label">
                            <span class="x-red">*</span>企业全称</label>
                        <div class="layui-input-inline">
                            <input type="text" name="company_name" id="company_name" autocomplete="off" class="layui-input" onchange="check_company_name(this.value)"></div>
                        <div class="layui-form-mid layui-word-aux">如已存在相关企业信息且未注册法大大，则可自动补全其他信息</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="credentials_no" class="layui-form-label">
                            <span class="x-red">*</span>企业证件号码</label>
                        <div class="layui-input-inline">
                            <input type="text" name="credentials_no" id="credentials_no" autocomplete="off" class="layui-input" onchange="check_credentials_no(this.value)"></div>
                        <div class="layui-form-mid layui-word-aux">三证合一营业执照，如已存在相关企业信息且未注册法大大，则可自动补全其他信息</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="legalbody_name" class="layui-form-label">
                            <span class="x-red">*</span>法定代表人姓名</label>
                        <div class="layui-input-inline">
                            <input type="text" name="legalbody_name" id="legalbody_name" autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="legalbody_credentials_no" class="layui-form-label">
                            <span class="x-red">*</span>法定代表人证件号码</label>
                        <div class="layui-input-inline">
                            <input type="text" name="legalbody_credentials_no" id="legalbody_credentials_no" autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">身份证号，如含有X，X需要大写</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="legalbody_mobile" class="layui-form-label">
                            <span class="x-red">*</span>企业联系电话</label>
                        <div class="layui-input-inline">
                            <input type="text" name="legalbody_mobile" id="legalbody_mobile" autocomplete="off" class="layui-input" onchange="check_legalbody_mobile(this.value)"></div>
                        <div class="layui-form-mid layui-word-aux">手机号</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="registration_address" class="layui-form-label">
                            <span class="x-red">*</span>企业注册地址</label>
                        <div class="layui-input-inline">
                            <input type="text" name="registration_address" id="registration_address" autocomplete="off" class="layui-input" style="width: 380px"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="contract_address" class="layui-form-label">
                            <span class="x-red">*</span>企业联系地址</label>
                        <div class="layui-input-inline">
                            <input type="text" name="contract_address" id="contract_address" autocomplete="off" class="layui-input" style="width: 380px"></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>统一社会信用代码电子版</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                            <span id="credit_code_file_name"></span>
                            <input type="file" id="credit_code_file" name="credit_code_file" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux">请上传图片 ( bmp , png , gif , jpeg , jpg , peg )</div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>授权委托书电子版</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file_a()">上传</button>
                            <span id="power_attorney_file_name"></span>
                            <input type="file" id="power_attorney_file" name="power_attorney_file" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name_a(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux">请上传图片 ( bmp , png , gif , jpeg , jpg , peg )</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn" onclick="add()">增加</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer'] , function(){
          form = layui.form;
        });

        function add() {
          var company_name             = $("#company_name").val();
          var credentials_no           = $("#credentials_no").val();
          var legalbody_name           = $("#legalbody_name").val();
          var legalbody_credentials_no = $("#legalbody_credentials_no").val();
          var legalbody_mobile         = $("#legalbody_mobile").val();
          var registration_address     = $("#registration_address").val();
          var contract_address         = $("#contract_address").val();
          var credit_code_file         = $("#credit_code_file").val();
          var power_attorney_file      = $("#power_attorney_file").val();
          if (company_name == '') {
            layer.msg('请输入企业全称' , {icon:2 , time:2000});
          } else if (credentials_no == '') {
            layer.msg('请输入企业证件号码' , {icon:2 , time:2000});
          } else if (legalbody_name == '') {
            layer.msg('请输入法定代表人姓名' , {icon:2 , time:2000});
          } else if (legalbody_credentials_no == '') {
            layer.msg('请输入法定代表人证件号码' , {icon:2 , time:2000});
          } else if (legalbody_mobile == '') {
            layer.msg('请输入企业联系电话(手机号)' , {icon:2 , time:2000});
          } else if (registration_address == '') {
            layer.msg('请输入企业注册地址' , {icon:2 , time:2000});
          } else if (contract_address == '') {
            layer.msg('请输入企业联系地址' , {icon:2 , time:2000});
          } else if (credit_code_file == '') {
            layer.msg('请上传统一社会信用代码电子版' , {icon:2 , time:2000});
          } else if (power_attorney_file == '') {
            layer.msg('请上传授权委托书电子版' , {icon:2 , time:2000});
          } else {
            $("#my_form").submit();
          }
        }

        function add_file() {
          $("#credit_code_file").click();
        }

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#credit_code_file_name").html(new_name);
        }

        function add_file_a() {
          $("#power_attorney_file").click();
        }

        function change_name_a(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#power_attorney_file_name").html(new_name);
        }

        function check_company_name(value)
        {
          $.ajax({
            url:'/user/Enterprise/CheckCompanyName',
            type:'post',
            dataType:'json',
            data:{'company_name':value},
            success:function(res){
              if (res['code'] == 1) {
                $("#credentials_no").val(res['data']['credentials_no']);
                $("#legalbody_name").val(res['data']['legalbody_name']);
                $("#legalbody_credentials_no").val(res['data']['legalbody_credentials_no']);
                $("#registration_address").val(res['data']['registration_address']);
                $("#contract_address").val(res['data']['contract_address']);
                $("#enterprise_id").val(res['data']['id']);
                $("#user_id").val(res['data']['user_id']);
              } else if (res['code'] > 1) {
                $("#enterprise_id").val('0');
                $("#user_id").val('0');
                layer.msg(res['info'] , {icon:2 , time:5000});
              } else {
                $("#enterprise_id").val('0');
                $("#user_id").val('0');
              }
            }
          });
        }

        function check_credentials_no(value)
        {
          $.ajax({
            url:'/user/Enterprise/CheckCredentialsNo',
            type:'post',
            dataType:'json',
            data:{'credentials_no':value},
            success:function(res){
              if (res['code'] == 1) {
                $("#company_name").val(res['data']['company_name']);
                $("#legalbody_name").val(res['data']['legalbody_name']);
                $("#legalbody_credentials_no").val(res['data']['legalbody_credentials_no']);
                $("#registration_address").val(res['data']['registration_address']);
                $("#contract_address").val(res['data']['contract_address']);
                $("#enterprise_id").val(res['data']['id']);
                $("#user_id").val(res['data']['user_id']);
              } else if (res['code'] > 1) {
                $("#enterprise_id").val('0');
                $("#user_id").val('0');
                layer.msg(res['info'] , {icon:2 , time:5000});
              } else {
                $("#enterprise_id").val('0');
                $("#user_id").val('0');
              }
            }
          });
        }

        function check_legalbody_mobile(value)
        {
          $.ajax({
            url:'/user/Enterprise/CheckLegalbodyMobile',
            type:'post',
            dataType:'json',
            data:{'legalbody_mobile':value},
            success:function(res){
              if (res['code'] > 1) {
                layer.msg(res['info'] , {icon:2 , time:5000});
              }
            }
          });
        }
      </script>
    </body>

</html>