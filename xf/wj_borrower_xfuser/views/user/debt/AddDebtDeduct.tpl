<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>添加债权扣除记录</title>
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
                <form class="layui-form" method="post" action="/user/Debt/AddDebtDeduct" id="my_form" enctype="multipart/form-data">

                    <div class="layui-form-item">
                      <label class="layui-form-label">
                            <span class="x-red">*</span>所属平台</label>
                      <div class="layui-input-inline">
                        <select name="deal_type" id="deal_type" lay-search="">
                          <option value="1">尊享</option>
                          <option value="2">普惠</option>
                          <option value="3">工场微金</option>
                          <option value="4">智多新</option>
                          <option value="5">交易所</option>
                        </select>
                      </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="user_id" class="layui-form-label">
                            <span class="x-red">*</span>用户ID</label>
                        <div class="layui-input-inline">
                            <input type="text" id="user_id" name="user_id" autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="tender_id" class="layui-form-label">
                            <span class="x-red">*</span>投资记录ID</label>
                        <div class="layui-input-inline">
                            <input type="text" id="tender_id" name="tender_id" autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="deal_id" class="layui-form-label">
                            借款ID</label>
                        <div class="layui-input-inline">
                            <input type="text" id="deal_id" name="deal_id" autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">
                          非必填,与借款标题二选一
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="deal_name" class="layui-form-label">
                            借款标题</label>
                        <div class="layui-input-inline">
                            <input type="text" id="deal_name" name="deal_name" autocomplete="off" class="layui-input"></div>
                        <div class="layui-form-mid layui-word-aux">
                          非必填,与借款ID二选一
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="buyback_user_id" class="layui-form-label">
                            <span class="x-red">*</span>回购用户ID</label>
                        <div class="layui-input-inline">
                            <input type="text" id="buyback_user_id" name="buyback_user_id" autocomplete="off" class="layui-input"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="debt_account" class="layui-form-label">
                            <span class="x-red">*</span>债权划扣金额</label>
                        <div class="layui-input-inline">
                            <input type="text" id="debt_account" name="debt_account" autocomplete="off" class="layui-input"></div>
                    </div>

                    <!-- <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>凭证</label>
                        <input type="file" style="display: none;" onchange="on_change(this)" id="pic">
                        <input type="hidden" id="agreement_pic" value="">
                        <div class="layui-input-inline">
                            <div class="layui-upload-list" style="margin:0">
                                <img src="/images/timg.png" id="img" class="layui-upload-img" width="100%" onclick="on_click()">
                            </div>
                        </div>
                        <div class="layui-form-mid layui-word-aux">授权债转协议凭证</div>
                    </div> -->

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>凭证</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                            <span id="file_name"></span>
                            <input type="file" id="file" name="file" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux">请上传压缩文件（rar，zip，7z）</div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">增加</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer']);

        function on_click() {
          $("#pic").click();
        }

        function on_change(img) {
          var file = img.files[0];
          var url  = null ;
          if (window.createObjectURL != undefined) { // basic
            url = window.createObjectURL(file);
          } else if (window.URL != undefined) { // mozilla(firefox)
            url = window.URL.createObjectURL(file);
          } else if (window.webkitURL != undefined) { // webkit or chrome
            url = window.webkitURL.createObjectURL(file);
          }
          $("#img").prop('src',url);

          var reader = new FileReader();
          var imgBase64;
          if (file) {
            imgBase64     = reader.readAsDataURL(file);
            reader.onload = function (e) {
              $("#agreement_pic").val(reader.result);
            }
          }          
        }

        function add_file() {
          $("#file").click();
        }

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
        }

        function do_add() {
          var deal_type       = $("#deal_type").val();
          var user_id         = $("#user_id").val();
          var tender_id       = $("#tender_id").val();
          var deal_id         = $("#deal_id").val();
          var deal_name       = $("#deal_name").val();
          var buyback_user_id = $("#buyback_user_id").val();
          var debt_account    = $("#debt_account").val();
          var file            = $("#file").val();
          if (deal_type != 1 && deal_type != 2 && deal_type != 3 && deal_type != 4 && deal_type != 5) {
            layer.alert('请选择所属平台');
          } else if (user_id == '' || isNaN(user_id)) {
            layer.alert('请正确输入用户ID');
          } else if (tender_id == '' || isNaN(tender_id)) {
            layer.alert('请正确输入投资记录ID');
          } else if (deal_id == '' && deal_name == '') {
            layer.alert('借款ID与借款标题请至少填写一项');
          } else if (deal_id != '' && isNaN(deal_id)) {
            layer.alert('请正确输入借款ID');
          } else if (buyback_user_id == '' || isNaN(buyback_user_id)) {
            layer.alert('请正确输入回购用户ID');
          } else if (debt_account == '' || isNaN(debt_account)) {
            layer.alert('请正确输入债权划扣金额');
          } else if (file == '') {
            layer.alert('请选择凭证');
          } else {
            $("#my_form").submit();
          }
        }
      </script>
    </body>

</html>