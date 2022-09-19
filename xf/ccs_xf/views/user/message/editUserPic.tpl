<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>编辑用户证件照</title>
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
                <form class="layui-form" action="/user/message/editUserPic" method="post" enctype="multipart/form-data" id="my_form">
                    <input type="hidden" name="id" value="<{$info['id']}>">

                    <div class="layui-form-item">
                        <label for="project_product_class" class="layui-form-label"> 用户ID</label>
                            <div class="layui-input-inline" style="margin-top: 13px;">
                                <{$info['id']}>
                            </div>
                    </div>
                    <div class="layui-form-item">
                        <label for="project_product_class" class="layui-form-label"> 姓名</label>
                            <div class="layui-input-inline" style="margin-top: 13px;">
                                <{$info['real_name']}>
                            </div>
                    </div>
                    <div class="layui-form-item">
                        <label for="project_product_class" class="layui-form-label"> 证件号</label>
                            <div class="layui-input-inline" style="margin-top: 13px;">
                                <{$info['idno']}>
                            </div>
                    </div>
                    <{if $info['intensive_idcard_face_edit'] !='' && $info['intensive_idcard_back_edit'] !='' }>
                    <div class="layui-form-item">
                        <label for="project_product_class" class="layui-form-label">
                            <span class="x-red">*</span>编辑用户证件-正面</label>
                        <div class="layui-input-inline" style="margin-top: 13px;">
                             <img src="<{$info['intensive_idcard_face_edit']}>" width="400"  >
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="project_product_class" class="layui-form-label">
                            <span class="x-red">*</span>编辑用户证件-反面</label>
                        <div class="layui-input-inline" style="margin-top: 13px;">
                            <img src="<{$info['intensive_idcard_back_edit']}>" width="400"   >
                        </div>
                    </div>
                    <{/if}>
                    <div class="layui-form-item">
                        <label for="project_name" class="layui-form-label">
                            <span class="x-red">*</span>编辑后证件照-正面
                        </label>
                        <div class="layui-input-inline" style="margin-top: 5px;">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                            <span id="file_name"></span>
                            <input type="file" id="intensive_idcard_face" name="intensive_idcard_face" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="project_name" class="layui-form-label">
                            <span class="x-red">*</span>编辑后证件照-反面
                        </label>
                        <div class="layui-input-inline" style="margin-top: 5px;">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file1()">上传</button>
                            <span id="file_name1"></span>
                            <input type="file" id="intensive_idcard_back" name="intensive_idcard_back" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name1(this.value)">
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">保存</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer']);
        layui.use(['laydate', 'form'],
        function() {
            var laydate = layui.laydate;

            //执行一个laydate实例
            laydate.render({
                elem: '#plan_time' //指定元素
            });
        });

        function add_file() {
          $("#intensive_idcard_face").click();
        }

        function add_file1() {
            $("#intensive_idcard_back").click();
        }

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
        }

        function change_name1(name) {
            var string   = name.lastIndexOf("\\");
            var new_name = name.substring(string+1);
            $("#file_name1").html(new_name);
        }

        function do_add() {
          var id = $("#id").val();
          if (id == '') {
            layer.alert('请输入ID');
          } else {
            $("#my_form").submit();
          }
        }
      </script>
    </body>

</html>