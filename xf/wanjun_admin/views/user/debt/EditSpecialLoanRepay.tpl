<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>编辑特殊还款计划</title>
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
                <form class="layui-form" action="/user/Debt/EditSpecialLoanRepay" method="post" enctype="multipart/form-data" id="my_form">
                    <input type="hidden" name="id" value="<{$info['id']}>">
                    <input type="hidden" name="deal_id" id="deal_id">
                    <input type="hidden" name="jys_record_number" id="jys_record_number">
                    <input type="hidden" name="deal_advisory_id" id="deal_advisory_id">
                    <input type="hidden" name="deal_user_id" id="deal_user_id">
                    <input type="hidden" name="deal_user_real_name" id="deal_user_real_name">
                    <input type="hidden" name="old_evidence_pic" value="<{$info['evidence_pic']}>">
                    <input type="hidden" name="old_attachments_url" value="<{$info['attachments_url']}>">
                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>产品大类</label>
                        <div class="layui-input-inline">
                            <select name="project_product_class" id="project_product_class" lay-search="" lay-filter="project_product_class">
                              <option value="">请选择产品大类</option>
                              <option value="嘉汇" <{if $info['project_product_class'] eq '嘉汇'}>selected<{/if}>>嘉汇</option>
                              <option value="盈嘉" <{if $info['project_product_class'] eq '盈嘉'}>selected<{/if}>>盈嘉</option>
                              <option value="盈益" <{if $info['project_product_class'] eq '盈益'}>selected<{/if}>>盈益</option>
                            </select>
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="project_product_class_status"><i class="iconfont" style="color: #259ed8;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="deal_name" class="layui-form-label">
                            <span class="x-red">*</span>借款标题</label>
                        <div class="layui-input-inline">
                            <input type="text" name="deal_name" id="deal_name" autocomplete="off" class="layui-input" onchange="change_deal_name(this.value)" value="<{$info['deal_name']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="deal_name_status"><i class="iconfont" style="color: #259ed8;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="project_name" class="layui-form-label">
                            <span class="x-red">*</span>项目名称</label>
                        <div class="layui-input-inline">
                            <input type="text" name="project_name" id="project_name" autocomplete="off" class="layui-input" readonly value="<{$info['project_name']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="project_name_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>还款资金类型</label>
                        <div class="layui-input-inline">
                            <input type="radio" class="loan_repay_type" name="loan_repay_type" value="1" title="本金" lay-filter="loan_repay_type" <{if $info['loan_repay_type'] eq '1'}>checked<{/if}>>
                            <input type="radio" class="loan_repay_type" name="loan_repay_type" value="2" title="利息" lay-filter="loan_repay_type" <{if $info['loan_repay_type'] eq '2'}>checked<{/if}>>
                            <input type="radio" class="loan_repay_type" name="loan_repay_type" value="3" title="本息全还" lay-filter="loan_repay_type" <{if $info['loan_repay_type'] eq '3'}>checked<{/if}>>
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="loan_repay_type_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>正常还款日期</label>
                        <div class="layui-input-inline" id="normal_time_radio">

                        </div>
                        <div class="layui-form-mid layui-word-aux" id="normal_time_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">
                            <span class="x-red">*</span>指定信息</label>
                        <div class="layui-input-inline">
                            <input type="radio" class="type" name="type" value="1" title="出借人ID" lay-filter="type" <{if $info['loan_user_id']}>checked<{/if}>>
                            <input type="radio" class="type" name="type" value="2" title="投资记录ID" lay-filter="type" <{if $info['deal_loan_id']}>checked<{/if}>>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="loan_user_id" class="layui-form-label">
                            <span class="x-red">*</span>出借人ID</label>
                        <div class="layui-input-inline">
                            <{if $info['loan_user_id']}>
                            <textarea name="loan_user_id" id="loan_user_id" class="layui-textarea" onchange="change_loan_user_id(this.value)"><{$info['loan_user_id']}></textarea>
                            <{else}>
                            <textarea name="loan_user_id" id="loan_user_id" class="layui-textarea" disabled style="background: #c2c2c2;" onchange="change_loan_user_id(this.value)"><{$info['loan_user_id']}></textarea>
                            <{/if}>
                        </div>
                        <{if $info['loan_user_id']}>
                        <div class="layui-form-mid layui-word-aux" id="loan_user_id_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                        <{else}>
                        <div class="layui-form-mid layui-word-aux" id="loan_user_id_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                        <{/if}>
                    </div>

                    <div class="layui-form-item">
                        <label for="deal_loan_id" class="layui-form-label">
                            <span class="x-red">*</span>投资记录ID</label>
                        <div class="layui-input-inline">
                            <{if $info['deal_loan_id']}>
                            <textarea name="deal_loan_id" id="deal_loan_id" class="layui-textarea" onchange="change_deal_loan_id(this.value)"><{$info['deal_loan_id']}></textarea>
                            <{else}>
                            <textarea name="deal_loan_id" id="deal_loan_id" class="layui-textarea" disabled style="background: #c2c2c2;" onchange="change_deal_loan_id(this.value)"><{$info['deal_loan_id']}></textarea>
                            <{/if}>
                        </div>
                        <{if $info['deal_loan_id']}>
                        <div class="layui-form-mid layui-word-aux" id="deal_loan_id_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                        <{else}>
                        <div class="layui-form-mid layui-word-aux" id="deal_loan_id_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                        <{/if}>
                    </div>

                    <div class="layui-form-item">
                        <label for="repayment_total" class="layui-form-label">
                            <span class="x-red">*</span>还款金额</label>
                        <div class="layui-input-inline">
                            <input type="text" name="repayment_total" id="repayment_total" autocomplete="off" class="layui-input" onchange="change_repayment_total(this.value)" value="<{$info['repayment_total']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="repayment_total_status"><i class="iconfont" style="color: #009688;">&#xe6b1;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="plan_time" class="layui-form-label">
                            计划还款日期</label>
                        <div class="layui-input-inline">
                            <input type="text" class="layui-input" name="plan_time" id="plan_time" readonly value="<{$info['plan_time']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="plan_time_status"></div>
                        
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">还款凭证</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                            <span id="file_name"><{$info['evidence_pic_a']}></span>
                            <input type="file" id="file" name="file" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux">请上传压缩文件（rar，zip，7z）</div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">附件</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file_csv()">上传</button>
                            <span id="file_name_csv"><{$info['attachments_url_a']}></span>
                            <input type="file" id="file_csv" name="file_csv" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name_csv(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux">请上传CSV文件</div>
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
          $("#file").click();
        }

        function add_file_csv() {
          $("#file_csv").click();
        }

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
        }

        function change_name_csv(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name_csv").html(new_name);
        }

        layui.use(['form'], function() {
          form=layui.form;

          form.on('select(project_product_class)' , function(data){   
            var val=data.value;
            if (val == '嘉汇' || val == '盈嘉' || val == '盈益') {
              $("#project_product_class_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
            } else {
              $("#project_product_class_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            }
            $("#deal_name").val('');
            $("#deal_name_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            $("#project_name").val('');
            $("#project_name_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            $("#normal_time_radio").html('');
            $("#normal_time_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
          });

          form.on('radio(loan_repay_type)', function(data){
            var val=data.value;
            if (val == 1 || val == 2 || val == 3) {
              $("#loan_repay_type_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
            } else {
              $("#loan_repay_type_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            }
          });

          form.on('radio(normal_time)', function(data){
            var val=data.value;
            if (val != '') {
              $("#normal_time_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
            } else {
              $("#normal_time_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
            }
          });

          form.on('radio(type)', function(data){
            var val=data.value;
            if (val == 1) {
              $("#loan_user_id").removeAttr('disabled');
              $("#loan_user_id").prop('style','');
              $("#deal_loan_id").prop('disabled','disabled');
              $("#deal_loan_id").prop('style','background:#c2c2c2;');
            } else if (val == 2) {
              $("#loan_user_id").prop('disabled','disabled');
              $("#loan_user_id").prop('style','background:#c2c2c2;');
              $("#deal_loan_id").removeAttr('disabled');
              $("#deal_loan_id").prop('style','');
            }
          });
        });

        function change_deal_name(value , old = '') {
          var project_product_class = $("#project_product_class").val();
          if (project_product_class == '') {
            layer.alert('请选择产品大类');
          } else {
            $.ajax({
              url:'/user/Debt/CheckDealName',
              type:'post',
              data:{'project_product_class':project_product_class,'deal_name':value},
              dataType:'json',
              success:function(res) {
                if (res['code'] == 0) {
                  $("#deal_name_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                  $("#project_name").val(res['data']['project_name']);
                  $("#project_name_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                  var str   = '';
                  var check = false;
                  for (var i in res['data']['deal_loan_repay']) {
                    if (old == res['data']['deal_loan_repay'][i]['time']) {
                      str += '<input type="radio" class="normal_time" name="normal_time" value="'+res['data']['deal_loan_repay'][i]['time']+'" title="'+res['data']['deal_loan_repay'][i]['time_name']+'" lay-filter="normal_time" checked>';
                      check = true;
                    } else {
                      str += '<input type="radio" class="normal_time" name="normal_time" value="'+res['data']['deal_loan_repay'][i]['time']+'" title="'+res['data']['deal_loan_repay'][i]['time_name']+'" lay-filter="normal_time">';
                    }
                  }
                  $("#normal_time_radio").html(str);
                  $("#normal_time_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  if (check) {
                    $("#normal_time_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
                  }
                  layui.use(['form'], function() { form.render(); });

                  $('#deal_id').val(res['data']['deal']['id']);
                  $('#jys_record_number').val(res['data']['deal']['jys_record_number']);
                  $('#deal_advisory_id').val(res['data']['deal']['advisory_id']);
                  $('#deal_user_id').val(res['data']['deal']['user_id']);
                  $('#deal_user_real_name').val(res['data']['deal']['user_real_name']);
                } else {
                  $("#deal_name_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  $("#project_name").val('');
                  $("#project_name_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  $("#normal_time_radio").html('');
                  $("#normal_time_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
                  layer.alert(res['info']);
                }
              }
            });
          }
        }

        function change_loan_user_id(value) {
          if (value != '') {
            $("#loan_user_id_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
          } else {
            $("#loan_user_id_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
          }
        }

        function change_deal_loan_id(value) {
          if (value != '') {
            $("#deal_loan_id_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
          } else {
            $("#deal_loan_id_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
          }
        }

        function change_repayment_total(value) {
          if (value != '' && !isNaN(value)) {
            $("#repayment_total_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
          } else {
            $("#repayment_total_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
          }
        }

        function do_add() {
          var project_product_class = $("#project_product_class").val();
          var deal_name             = $("#deal_name").val();
          var project_name          = $("#project_name").val();
          var loan_repay_type       = $(".loan_repay_type:checked").val();
          var normal_time           = $(".normal_time:checked").val();
          var type                  = $(".type:checked").val();
          var loan_user_id          = $("#loan_user_id").val();
          var deal_loan_id          = $("#deal_loan_id").val();
          var repayment_total       = $("#repayment_total").val();
          if (project_product_class == '') {
            layer.alert('请选择产品大类');
          } else if (deal_name == '') {
            layer.alert('请输入借款标题');
          } else if (project_name == '') {
            layer.alert('请输入项目名称');
          } else if (loan_repay_type != 1 && loan_repay_type != 2 && loan_repay_type != 3) {
            layer.alert('请正确选择还款资金类型');
          } else if (normal_time == undefined || normal_time == '') {
            layer.alert('请选择正常还款日期');
          } else if (type != 1 && type != 2) {
            layer.alert('请正确选择指定信息');
          } else if (type == 1 && loan_user_id == '') {
            layer.alert('请输入出借人ID');
          } else if (type == 2 && deal_loan_id == '') {
            layer.alert('请输入投资记录ID');
          } else if (repayment_total == '' || isNaN(repayment_total)) {
            layer.alert('请正确输入还款金额');
          } else {
            $("#my_form").submit();
          }
        }

        window.onload = function(){
          window.setTimeout(function() {
            change_deal_name('<{$info['deal_name']}>' , '<{$info['normal_time']}>');
          },500);
        };
      </script>
    </body>

</html>