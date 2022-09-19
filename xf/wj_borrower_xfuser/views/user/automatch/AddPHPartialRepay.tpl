<!DOCTYPE html>
<html class="x-admin-sm">
    
  <head>
    <meta charset="UTF-8">
    <title>普惠匹配债权还本录入</title>
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
    <![endif]-->
  </head>
    
  <body>
    <div class="layui-fluid">
      <div class="layui-row">
        <form class="layui-form" method="post" action="/user/Automatch/AddPHPartialRepay" id="my_form" enctype="multipart/form-data">

          <div class="layui-form-item">
            <label class="layui-form-label"></label>
            <div class="layui-input-inline">
              <span class="x-red">*</span>该还款只还本，还本后保留利息 
            </div>
          </div>

          <div class="layui-form-item">
            <label for="pay_user" class="layui-form-label">
              <span class="x-red">*</span>付款方
            </label>
            <div class="layui-input-inline">
              <input type="text" id="pay_user" name="pay_user" autocomplete="off" class="layui-input">
            </div>
          </div>

          <div class="layui-form-item">
            <label class="layui-form-label"><span class="x-red">*</span>还款信息</label>
            <div class="layui-input-inline">
              <button type="button" class="layui-btn layui-btn-normal"  onclick="add_template()">上传</button>
              <span id="template_name"></span>
              <input type="file" id="template" name="template" autocomplete="off" class="layui-input" style="display: none;" onchange="change_template(this.value)">
            </div>
            <div class="layui-form-mid layui-word-aux">
              <span class="x-red">*</span>请上传 xls 文件（数据量不可超过5000行） <a href="/user/Automatch/AddPHPartialRepay?download=1" style="color: blue;">下载模板</a>
            </div>
          </div>
          
          <div class="layui-form-item">
            <label for="agency_name" class="layui-form-label">咨询方名称</label>
            <div class="layui-input-inline">
              <input type="text" id="agency_name" name="agency_name" autocomplete="off" class="layui-input" onchange="check_agency_name(this.value)">
            </div>
            <div class="layui-form-mid layui-word-aux" id="agency_name_iconfont">
              <i class="iconfont" style="color: #259ed8;">&#xe6b1;</i>
            </div>
            <input type="hidden" id="agency_name_status" value="1">
          </div>

          <div class="layui-form-item">
            <label for="pay_plan_time" class="layui-form-label">计划还款日期</label>
            <div class="layui-input-inline">
              <input type="text" id="pay_plan_time" name="pay_plan_time" readonly autocomplete="off" class="layui-input">
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

      function check_agency_name(value) {
        if (value == '') {
          $("#agency_name_status").val(1);
          $("#agency_name_iconfont").html('<i class="iconfont" style="color: #259ed8;">&#xe6b1;</i>');
        } else {
          $.ajax({
            url:'/user/Automatch/CheckAgencyName',
            type:'post',
            data:{'platform_id':2,'agency_name':value},
            dataType:'json',
            success:function(res) {
              if (res['code'] == 0) {
                $("#agency_name_status").val(1);
                $("#agency_name_iconfont").html('<i class="iconfont" style="color: #259ed8;">&#xe6b1;</i>');
              } else {
                $("#agency_name_status").val(2);
                $("#agency_name_iconfont").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                layer.alert(res['info']);
              }
            }
          });
        }
      }

      function do_add() {
        var pay_user    = $("#pay_user").val();
        var template    = $("#template").val();
        var agency_name = $("#agency_name").val();
        var status      = $("#agency_name_status").val();
        if (pay_user == '') {
          layer.alert('请输入付款方');
        } else if (template == '') {
          layer.alert('请上传还款信息');
        } else if (agency_name != '' && status != 1) {
          layer.alert('通过此咨询方名称未查询到对应信息');
        } else {
          $("#my_form").submit();
        }
      }
    </script>
  </body>

</html>