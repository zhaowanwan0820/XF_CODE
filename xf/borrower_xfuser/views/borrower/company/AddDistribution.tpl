<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>添加分配</title>
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
                <form class="layui-form" method="post" action="/borrower/company/AddDistribution" id="my_form" enctype="multipart/form-data">

                    <!--div class="layui-form-item">
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>第三方公司名称</label>
                        <div class="layui-input-inline">
                            <input type="text" id="company_name" name="company_name" autocomplete="off" class="layui-input"></div>
                    </div-->

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>归属第三方公司</label>
                        <div class="layui-input-block">
                            <div class="layui-input-inline" style="width: 190px">
                                <select name="company_id"  lay-verify="company_id" id="company_id" style="width:20px">
                                    <{foreach $company_list as $key => $v}>
                                    <option value="<{$v['id']}>"><{$v['name']}></option>
                                    <{/foreach}>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="pay_plan_time" class="layui-form-label">
                            <span class="x-red">*</span>生效时间</label>
                        <div class="layui-input-inline">
                            <input type="text" id="start_time" name="start_time" readonly autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="pay_plan_time" class="layui-form-label">
                            <span class="x-red">*</span>失效时间</label>
                        <div class="layui-input-inline">
                            <input type="text" id="end_time" name="end_time" readonly autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item" id="s_type_div">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>分配借款人</label>
                        <div class="layui-input-inline">
                            <input type="radio" name="s_type" value="1" title="单独分配" lay-filter="s_type" checked>
                            <input type="radio" name="s_type" value="2" title="批量分配" lay-filter="s_type">
                        </div>
                    </div>

                    <div class="layui-form-item" id="file_path_div" style="display: none;">
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>选择数据文件</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_template()">上传</button>
                            <span id="template_name"></span>
                            <input type="file" id="file_path" name="file_path" autocomplete="off" class="layui-input" style="display: none;" onchange="change_template(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传 xls 文件（数据量不可超过20000行） <a href="/borrower/company/AddDistribution?download=1" style="color: blue;">下载模板</a></div>
                    </div>

                    <div class="layui-form-item"  id="user_id_div"  >
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>借款人ID</label>
                        <div class="layui-input-inline">
                            <input type="text" id="user_id" name="user_id" autocomplete="off" class="layui-input"></div>
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
            var laydate = layui.laydate;
            var form    = layui.form;

            form.on('radio(s_type)', function(data){
                var val=data.value;
                $("#user_id").val('');
                if (val == 1) {
                    $("#file_path_div").prop('style','display: none;');
                    $("#user_id_div").prop('style','');
                } else if (val == 2) {
                    $("#file_path_div").prop('style','');
                    $("#user_id_div").prop('style','display: none;');
                }
            });



            laydate.render({
                elem: '#start_time'
                ,type: 'datetime'
            });

            laydate.render({
                elem: '#end_time'
                ,type: 'datetime'
            });

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
          var company_id = $("#company_id").val();
          var start_time = $("#start_time").val();
          var end_time = $("#end_time").val();
            var file_path = $("#file_path").val();
            var user_id = $("#user_id").val();

          // var proof    = $("#proof").val();
          if (company_id == '') {
            layer.alert('请选择第三方公司');
          } else if (start_time == '') {
            layer.alert('请输入生效时间');
          // } else if (proof == '') {
            // layer.alert('请选择还款凭证');
          } else if (end_time == '') {
              layer.alert('请输入失效时间');
              // } else if (proof == '') {
              // layer.alert('请选择还款凭证');
          }  else if (file_path == '' && user_id == '') {
              layer.alert('请至少选择一种分配类型录入数据');
              // } else if (proof == '') {
              // layer.alert('请选择还款凭证');
          } else {
            $("#my_form").submit();
          }
        }
      </script>
    </body>

</html>