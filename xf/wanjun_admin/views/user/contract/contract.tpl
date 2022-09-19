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
        <form class="layui-form" method="post" action="/user/contract/DownLoadUserContract" id="user_condition_form"
              enctype="multipart/form-data">
              <div class="layui-form-item">
                <label for="bank_mobile" class="layui-form-label">
                    <span class="x-red">*</span>用户ID
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="user_id" required=""  lay-verify="number"
                           autocomplete="off" class="layui-input">
                </div>
                <!-- lay-filter="send_sms"  -->
                <div class="layui-input-inline">
                    <button type="button"  class="layui-btn" onclick="get_user_info()">查询</button>
                </div>
               
            </div>

            <div class="layui-form-item">
                <label  class="layui-form-label">
                    用户姓名
                </label>
               
                <div class="layui-input-inline" style="margin-top: 10px;" id='real_name'>
                </div>
              
            </div>

            <div class="layui-form-item">
                <label  class="layui-form-label">
                    用户证件号
                </label>
               
                <div class="layui-input-inline" style="margin-top: 12px;" id='idno'>
                </div>
              
            </div>
         
           
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label"></label>
                <button type="button" class="layui-btn layui-btn-disabled" id="doh" onclick="user_upload()">导出</button>

            </div>
            <input type="hidden" name="query_user_id" id="query_user_id"  value="">

        </form>
        
    </div>
</div>
<script>


    layui.use(['form', 'layer', 'laydate'], function () {
        var laydate = layui.laydate;
        var form = layui.form;
       
        $("#doh").addClass("disabled")

    });



    function get_user_info(){
     
     var user_id = $('#user_id').val();
     if(user_id==''){
       alert('请输入用户ID！');
       return;
     }
    
     $.ajax({
           url: '/user/contract/getUserInfo',
           data: {user_id:user_id},
           type:"POST",
           dataType:'json',
           success: function (res) {
               if(res.code == 0){
                    // 增加样式
                    $("#real_name").html(res.data.real_name);
                    $("#idno").html(res.data.idno);
                    $("#doh").removeClass("disabled")
                    $("#doh").removeClass('layui-btn-disabled')
                    $("#query_user_id").val(res.data.id);
           
               }else{
                   layer.alert(res.info);
               }
           }
       })

}
    
    function user_upload() {
        if ($("#doh").hasClass("disabled")) {
           return  ;
        }
        if ($("#submit").hasClass("disabled")) {
           return  layer.alert('处理中，请勿重复提交');
        }
        var user_id = $('#user_id').val();
       
        var query_user_id = $('#query_user_id').val();
        if(user_id != query_user_id){
            alert('请重新输入用户ID！');
            return;
        }
        $("#doh").addClass("disabled")
        $("#doh").html("导出中...")
        $("#user_condition_form").submit();
    }
</script>
</body>
</html>