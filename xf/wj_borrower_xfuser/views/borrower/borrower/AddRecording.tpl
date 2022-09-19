<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>新增</title>
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
                <form class="layui-form" method="post" action="/borrower/borrower/AddRecording" id="my_form" enctype="multipart/form-data">


                    <div class="layui-form-item">
                        <label for="pay_plan_time" class="layui-form-label">
                            <span class="x-red">*</span>录音时间</label>
                        <div class="layui-input-inline">
                            <input type="text" id="record_time" name="record_time" readonly autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="pay_plan_time" class="layui-form-label">
                            <span class="x-red">*</span>录音数量</label>
                        <div class="layui-input-inline">
                            <input type="text" id="record_num" name="record_num"   autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item" id="file_path_div"  >
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>电话录音</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_template()">上传</button>
                            <span id="template_name"></span>
                            <input type="file" id="file_path" name="file_path" autocomplete="off" class="layui-input" style="display: none;" onchange="change_template(this.value)">
                        </div>
                    </div>


                    <div class="layui-form-item" id="file_path_div"  >
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>录音名单</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_uid()">上传</button>
                            <span id="uid_name"></span>
                            <input type="file" id="uid_path" name="uid_path" autocomplete="off" class="layui-input" style="display: none;" onchange="change_uid(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传 xls 文件（数据量不可超过10000行） <a href="/borrower/borrower/AddRecording?download=1" style="color: blue;">下载录音名单模板</a></div>
                    </div>



                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">上传</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['layedit', 'form', 'layer' , 'laydate'] , function() {
            var layedit = layui.layedit;
            var laydate = layui.laydate;
            var form    = layui.form;

            laydate.render({
                elem: '#record_time'
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

        function add_uid() {
            $("#uid_path").click();
        }

        function change_uid(name) {
            var string   = name.lastIndexOf("\\");
            var new_name = name.substring(string+1);
            $("#uid_name").html(new_name);
        }

        function do_add() {
          var record_time = $("#record_time").val();
          var file_path = $("#file_path").val();
          var uid_path = $("#uid_path").val();
          var record_num = $('#record_num').val();
          if (record_time == '') {
            layer.alert('请选择录音时间');
          }  else if (file_path == '' ) {
              layer.alert('请选择电话录音文件');
          } else if (uid_path == '' ) {
              layer.alert('请选择录音名单');
          }  else if (record_num == '') {
              layer.alert('请填写录音数量');
          }else{
            $("#my_form").submit();
          }
        }
      </script>
    </body>

</html>