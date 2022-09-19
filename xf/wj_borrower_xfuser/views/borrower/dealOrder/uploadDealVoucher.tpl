<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title></title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi"/>
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
        <form class="layui-form" method="post" action="/borrower/DealOrder/uploadDealVoucher" id="user_condition_form"
              enctype="multipart/form-data">
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label"><span class="x-red">*</span>放款金额</label>
                    <div class="layui-inline layui-show-xs-block">
                         <input type="text"  id="make_loan_amount" name="make_loan_amount" value="" class="layui-input">
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label"><span class="x-red">*</span>放款时间</label>
                    <div class="layui-inline layui-show-xs-block">
                        <input type="text" id="voucher_time" name="voucher_time" readonly autocomplete="off" class="layui-input">
                        <input type="hidden" name="id" value="<{$_GET['deal_id']}>">
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label"><span class="x-red">*</span>放款流水</label>
                <div class="layui-input-inline">
                    <button type="button" class="layui-btn layui-btn-normal" onclick="add_template()">上传</button>
                    <span id="template_name"></span>
                    <input type="file" id="template" name="voucher_url" autocomplete="off" class="layui-input"
                           style="display: none;" onchange="change_template(this.value)">
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label"></label>
                <button type="button" class="layui-btn" id="doh" onclick="user_upload()">提交</button>
            </div>
        </form>
    </div>
</div>
<script>
    layui.use(['form', 'layer', 'laydate'], function () {
        var laydate = layui.laydate;
        var form = layui.form;
        laydate.render({
            elem: '#voucher_time'
        });

    });

    function user_upload() {
        $("#user_condition_form").submit();
    }
    function add_template() {
        $("#template").click();
    }

    function change_template(name) {
        var string = name.lastIndexOf("\\");
        var new_name = name.substring(string + 1);
        $("#template_name").html(new_name);
    }

</script>
</body>
</html>