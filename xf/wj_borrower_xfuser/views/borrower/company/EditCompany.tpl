<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>编辑催收公司</title>
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
                <form class="layui-form" method="post" action="/borrower/company/EditCompany" enctype="multipart/form-data" id="my_form">
                    <input type="hidden" name="id" value="<{$res['id']}>">
                    <input type="hidden" name="tax_number" value="<{$res['tax_number']}>">
                    <input type="hidden" name="name" value="<{$res['name']}>">

                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>公司名称</label>
                        <div class="layui-input-inline">
                            <input type="text" id="name" name="name" disabled autocomplete="off" style="background:#CCCCCC"  class="layui-input" value="<{$res['name']}>"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>公司类型</label>
                        <div class="layui-input-inline">
                            <input type="text" id="type" name="type" disabled autocomplete="off"  style="background:#CCCCCC"  class="layui-input"  value="<{$res['type_cn']}>" ></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>统一识别码</label>
                        <div class="layui-input-inline">
                            <input type="text" id="tax_number" name="tax_number" disabled autocomplete="off"  style="background:#CCCCCC"  class="layui-input"  value="<{$res['tax_number']}>" ></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>营业执照扫描件</label>
                        <div class="layui-input-inline">
                            <a href='<{$res['business_license']}>' download>
                            <button type="button" class="layui-btn layui-btn-normal"  >下载 <{$res['business_license_name']}></button>
                            </a>
                            <span id="file_name"></span>
                            <input type="hidden" name="business_license" value="<{$res['business_license']}>">
                        </div>

                    </div>

                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>企业联系人</label>
                        <div class="layui-input-inline">
                            <input type="text" id="contract_name" name="contract_name" autocomplete="off" class="layui-input"  value="<{$res['contract_name']}>" ></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>联系人电话</label>
                        <div class="layui-input-inline">
                            <input type="text" id="contract_mobile" name="contract_mobile" autocomplete="off" class="layui-input"  value="<{$res['contract_mobile']}>" ></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>联系人邮箱</label>
                        <div class="layui-input-inline">
                            <input type="text" id="contract_email" name="contract_email" autocomplete="off" class="layui-input"  value="<{$res['contract_email']}>" ></div>
                    </div>
                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>合作状态</label>
                        <div class="layui-input-inline">
                            <input type="text" id="status" name="status" disabled autocomplete="off"  style="background:#CCCCCC"  class="layui-input"  value="<{$res['status_cn']}>" ></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn" onclick="add()">保存</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer'] , function(){
          form=layui.form;
        });

        var old_id     = '<{$res['id']}>';

        function add() {
            var name = $("#name").val();
            var tax_number   = $("#tax_number").val();
            var contract_name    = $("#contract_name").val();
            var contract_mobile  = $("#contract_mobile").val();
            var contract_email  = $("#contract_email").val();

            if (name == '') {
                layer.msg('请输入公司名称' , {icon:2 , time:2000});
            } else if (tax_number == '') {
                layer.msg('请输入公司统一识别码' , {icon:2 , time:2000});
            } else if (contract_name == '') {
                layer.msg('请输入企业联系人' , {icon:2 , time:2000});
            }else if (contract_mobile == '') {
                layer.msg('请输入联系电话' , {icon:2 , time:2000});
            }else if (contract_email == '') {
                layer.msg('请输入联系邮箱' , {icon:2 , time:2000});
            } else {
                $("#my_form").submit();
            }
        }

        function add_file() {
          $("#business_license").click();
        }

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
          $("#file_status").html('<i class="iconfont" style="color: #259ed8;">&#xe6b1;</i>');
        }

      </script>
    </body>

</html>