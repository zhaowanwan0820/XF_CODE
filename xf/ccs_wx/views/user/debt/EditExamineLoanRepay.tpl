<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>编辑常规还款计划</title>
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
                <form class="layui-form" action="/user/Debt/EditExamineLoanRepay" method="post" enctype="multipart/form-data" id="my_form">
                    <input type="hidden" name="id" value="<{$info['id']}>">
                    <input type="hidden" name="repay_id" value="<{$info['repay_id']}>">
                    <input type="hidden" name="deal_id" value="<{$info['deal_id']}>">
                    <input type="hidden" name="jys_record_number" value="<{$info['jys_record_number']}>">
                    <input type="hidden" name="deal_advisory_id" value="<{$info['deal_advisory_id']}>">
                    <input type="hidden" name="deal_advisory_name" value="<{$info['deal_advisory_name']}>">
                    <input type="hidden" name="deal_user_id" value="<{$info['deal_user_id']}>">
                    <input type="hidden" name="deal_user_real_name" value="<{$info['deal_user_real_name']}>">
                    <input type="hidden" name="loan_repay_type" value="<{$info['loan_repay_type']}>">
                    <input type="hidden" name="old_evidence_pic" value="<{$info['evidence_pic']}>">
                    <div class="layui-form-item">
                        <label for="project_product_class" class="layui-form-label">
                            <span class="x-red">*</span>产品大类</label>
                        <div class="layui-input-inline">
                            <input type="text" name="project_product_class" id="project_product_class" autocomplete="off" class="layui-input" readonly style="background:#c2c2c2;" value="<{$info['project_product_class']}>">
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="deal_name" class="layui-form-label">
                            <span class="x-red">*</span>借款标题</label>
                        <div class="layui-input-inline">
                            <input type="text" name="deal_name" id="deal_name" autocomplete="off" class="layui-input" readonly style="background:#c2c2c2;" value="<{$info['deal_name']}>"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="project_name" class="layui-form-label">
                            <span class="x-red">*</span>项目名称</label>
                        <div class="layui-input-inline">
                            <input type="text" name="project_name" id="project_name" autocomplete="off" class="layui-input" readonly style="background:#c2c2c2;" value="<{$info['project_name']}>"></div>
                    </div>

                    <div class="layui-form-item">
                      <label class="layui-form-label">
                            <span class="x-red">*</span>还款资金类型</label>
                      <div class="layui-input-inline">
                        <input type="radio" value="1" title="本金" <{if $info['loan_repay_type'] eq '1'}>checked<{/if}> readonly>
                        <input type="radio" value="2" title="利息" <{if $info['loan_repay_type'] eq '2'}>checked<{/if}> readonly>
                      </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="normal_time" class="layui-form-label">
                            <span class="x-red">*</span>正常还款日期</label>
                        <div class="layui-input-inline">
                            <input type="text" name="normal_time" id="normal_time" autocomplete="off" class="layui-input" readonly style="background:#c2c2c2;" value="<{$info['normal_time']}>"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="repayment_total" class="layui-form-label">
                            <span class="x-red">*</span>还款金额</label>
                        <div class="layui-input-inline">
                            <input type="text" name="repayment_total" id="repayment_total" autocomplete="off" class="layui-input" readonly style="background:#c2c2c2;" value="<{$info['repayment_total']}>"></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="plan_time" class="layui-form-label">
                            计划还款日期</label>
                        <div class="layui-input-inline">
                          <input type="text" class="layui-input" name="plan_time" id="plan_time" value="<{$info['plan_time']}>" readonly>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>还款凭证</label>
                        <div class="layui-input-inline">
                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                            <span id="file_name"></span>
                            <input type="file" id="file" name="file" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                        </div>
                        <div class="layui-form-mid layui-word-aux">请上传压缩文件（rar，zip，7z）</div>
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

        function change_name(name) {
          var string   = name.lastIndexOf("\\");
          var new_name = name.substring(string+1);  
          $("#file_name").html(new_name);
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