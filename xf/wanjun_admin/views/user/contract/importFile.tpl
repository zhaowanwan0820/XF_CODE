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
        <{if $end == 0 }>
        <form class="layui-form" method="post" action="/user/contract/upload" id="user_condition_form"
              enctype="multipart/form-data">
            <div class="layui-form-item">
                <label class="layui-form-label"><span class="x-red">*</span>上传文件</label>
                <div class="layui-input-inline">
                    <button type="button" class="layui-btn layui-btn-normal" onclick="add_template()">上传</button>
                    <span id="template_name"></span>
                    <input type="file" id="template" name="offline" autocomplete="off" class="layui-input"
                           style="display: none;" onchange="change_template(this.value)">
                </div>

                <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传 xlsx 文件（建议数据量不超过10行,否则可能超时）。
                    <a href="https://admin.xfuser.com/upload/合同导出模板.xlsx" style="color: blue;">下载模板</a>
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label"></label>
                <button type="button" class="layui-btn" id="doh" onclick="user_upload()">提交</button>

            </div>
        </form>
        <{/if}>
        <{if !empty($msg)}>
        <form class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label"><span class="x-red">*</span>上传结果</label>
                <div class="layui-input-block">
                            <textarea class="layui-textarea" readonly style="height: 100px;width: 500px;">
                                错误信息：<{$msg}>
                            </textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label"></label>
                <button type="button" class="layui-btn layui-btn-primary" onclick="var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);">关闭</button>
            </div>
        </form>
        <{/if}>
        
    </div>
</div>
<script>


    layui.use(['form', 'layer', 'laydate'], function () {
        var laydate = layui.laydate;
        var form = layui.form;
        form.on('select(out_area)',function(data){

            $.ajax({
                url: '/shop/SpecialArea/GetSpecialAreaByAppId',
                data: {appid:data.value},
                type: "POST",

                success:function(res){
                    var res = JSON.parse(res);
                    $("#area_id").empty();
                    for(var i =0;i<res.list.length;i++){
                        $("#area_id").append("<option value=\""+res.list[i].id+"\">"+res.list[i].name+"</option>");
                    }
                    layui.form.render("select");
                }
            });

        });

    });



    function add_template() {
        $("#template").click();
    }

    function change_template(name) {
        var string = name.lastIndexOf("\\");
        var new_name = name.substring(string + 1);
        $("#template_name").html(new_name);
    }

    function user_upload() {
        if ($("#doh").hasClass("disabled")) {
           return  layer.alert('处理中，请勿重复提交');
        }
        var template = $("#template").val();
        if (template == '') {
            layer.alert('请选择上传文件');
        } else {
            $("#doh").addClass("disabled")
            $("#doh").html("上传中...")
            $("#user_condition_form").submit();
        }
    }
</script>
</body>
</html>