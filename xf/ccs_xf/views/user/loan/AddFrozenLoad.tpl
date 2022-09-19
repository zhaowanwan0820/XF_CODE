<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>新增冻结</title>
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
                <form class="layui-form" method="post" action="/user/loan/AddFrozenLoad" id="my_form" enctype="multipart/form-data">

                    <!--div class="layui-form-item">
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>第三方公司名称</label>
                        <div class="layui-input-inline">
                            <input type="text" id="company_name" name="company_name" autocomplete="off" class="layui-input"></div>
                    </div-->

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>所属平台</label>
                        <div class="layui-input-block">
                            <div class="layui-input-inline" style="width: 190px">
                                <select name="platform_id"  lay-verify="platform_id" id="platform_id" style="width:20px">
                                    <option value="1">尊享</option>
                                    <option value="2">普惠</option>
                                    <option value="4">智多新</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="layui-form-item"  id="frozen_batch_number_div"  >
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>冻结批次号</label>
                        <div class="layui-input-inline">
                            <input type="text" id="cc" name="frozen_batch_number" autocomplete="off" class="layui-input"></div>
                    </div>



                    <div class="layui-form-item" id="file_path_div"  >
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>上传债权数据</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_template()">上传</button>
                            <span id="template_name"></span>
                            <input type="file" id="file_path" name="file_path" autocomplete="off" class="layui-input" style="display: none;" onchange="change_template(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传 xls 文件（数据量不可超过5000行） <a href="/user/loan/AddFrozenLoad?download=1" style="color: blue;">下载模板</a></div>
                    </div>
                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">添加</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['layedit', 'form', 'layer' , 'laydate'] , function() {
            var layedit = layui.layedit;

        });

        function add_template() {
          $("#file_path").click();
        }

        function change_template(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#template_name").html(new_name);
        }

        // function add_proof() {
        //   $("#proof").click();
        // }

        // function change_proof(name) {
        //   var string   = name.lastIndexOf("\\");
        //   var new_name = name.substring(string+1);  
        //   $("#proof_name").html(new_name);
        // }

        function do_add() {
          var platform_id = $("#platform_id").val();
          var frozen_batch_number = $("#frozen_batch_number").val();
           var file_path = $("#file_path").val();

          // var proof    = $("#proof").val();
          if (platform_id == '') {
            layer.alert('请选择所属平台');
          } else if (frozen_batch_number == '') {
            layer.alert('请输入冻结批次号');
          // } else if (proof == '') {
            // layer.alert('请选择还款凭证');
          } else if (file_path == ''  ) {
              layer.alert('请上传债权数据');
              // } else if (proof == '') {
              // layer.alert('请选择还款凭证');
          } else {
            $("#my_form").submit();
          }
        }
      </script>
    </body>

</html>