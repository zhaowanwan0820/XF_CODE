<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>普惠部分还款录入</title>
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
                <form class="layui-form" method="post" action="/user/PartialRepay/AddPartial" id="my_form" enctype="multipart/form-data">

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                        </label>
                        <div class="layui-input-inline">
                          <span class="x-red">*</span>该还款只还本，还本后保留利息 
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="pay_user" class="layui-form-label">
                            <span class="x-red">*</span>付款方</label>
                        <div class="layui-input-inline">
                            <input type="text" id="pay_user" name="pay_user" autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>还款信息</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_template()">上传</button>
                            <span id="template_name"></span>
                            <input type="file" id="template" name="template" autocomplete="off" class="layui-input" style="display: none;" onchange="change_template(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传 xls 文件（数据量不可超过5000行） <a href="/user/PartialRepay/AddPartial?download=1" style="color: blue;">下载模板</a></div>
                    </div>
                    <!--春节后注释出清部分功能-->
                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>是否出清</label>
                        <div class="layui-input-inline">
                            <input type="radio"  name="is_clear" value="1" title="否" checked>
                            <input type="radio"  name="is_clear" value="2" title="是" >
                        </div>
                        <div class="layui-form-mid layui-word-aux"  >选择出清后，依据导入投资记录出清此投资记录下所有待还本金</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="pay_plan_time" class="layui-form-label">
                            计划还款日期</label>
                        <div class="layui-input-inline">
                            <input type="text" id="pay_plan_time" name="pay_plan_time" readonly autocomplete="off" class="layui-input"></div>
                    </div>

                    <!-- <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>还款凭证</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_proof()">上传</button>
                            <span id="proof_name"></span>
                            <input type="file" id="proof" name="proof" autocomplete="off" class="layui-input" style="display: none;" onchange="change_proof(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传压缩文件（ rar，zip，7z ）</div>
                    </div> -->

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">增加</button>
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

        function add_template() {
          $("#template").click();
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
          var pay_user = $("#pay_user").val();
          var template = $("#template").val();
          // var proof    = $("#proof").val();
          if (pay_user == '') {
            layer.alert('请输入付款方');
          } else if (template == '') {
            layer.alert('请选择还款信息');
          // } else if (proof == '') {
            // layer.alert('请选择还款凭证');
          } else {
            $("#my_form").submit();
          }
        }
      </script>
    </body>

</html>