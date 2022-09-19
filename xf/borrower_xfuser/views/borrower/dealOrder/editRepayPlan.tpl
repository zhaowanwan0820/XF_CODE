<!doctype html>
<html  class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>网信客服后台</title>
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="stylesheet" href="./css/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/login.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script type="text/javascript" src="<{$CONST.jsPath}>/jquery.min.js"></script>
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
</head>
<body>
<div class="layui-fluid">
    <div class="layui-row">
        <form class="layui-form">
            <div class="layui-form-item">
                <label for="principal" class="layui-form-label">
                    原待还本金
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="principal" name="principal" readonly="readonly" style="background:#CCCCCC" value="<{$principal}>"
                         class="layui-input">
                </div>
             
            </div>
            <div class="layui-form-item">
                <label for="interest" class="layui-form-label">
                    原待还利息
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="interest" name="interest" readonly="readonly" style="background:#CCCCCC" value="<{$interest}>"
                         class="layui-input">
                </div>
             
            </div>
            <div class="layui-form-item">
                <label for="new_principal" class="layui-form-label">
                    <span class="x-red">*</span>修改后待还本金
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="new_principal" name="new_principal" lay-verify="number"   required="" value="<{$new_principal}>" 
                         class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    修改金额不得大于原待还本金
                </div>
            </div>
            <div class="layui-form-item">
                <label for="new_interest" class="layui-form-label">
                    <span class="x-red">*</span>修改后待还利息
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="new_interest" name="new_interest" lay-verify="number"   required="" value="<{$new_interest}>" 
                         class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    修改金额不得大于原待还利息
                </div>
            </div>
            <div class="layui-form-item">
                <label for="repay_flag" class="layui-form-label">
                    <span class="x-red">*</span>是否已还
                </label>
                <div class="layui-input-inline">
                    <input  type="radio" name="repay_flag" value="0" title="未还" checked>
                    <input  type="radio" name="repay_flag" value="1" title="已还">
                </div>
                
            </div>
            <div class="layui-form-item">
                <input type="hidden" id="id" name="id"  value="<{$id}>"
                         class="layui-input">
                <label for="L_repass" class="layui-form-label">
                </label>
                <button  class="layui-btn" lay-filter="update" lay-submit="">
                    更改
                </button>
            </div>
        </form>
    </div>
</div>
<script>layui.use(['form', 'layer'],
            function() {
                $ = layui.jquery;
                var form = layui.form,
                layer = layui.layer;
               
                //监听提交
                form.on('submit(update)',
                        function(data) {
                             //发异步，把数据提交给php
                           
                            var principal = $("#principal").val()
                            var interest = $("#interest").val();
                            var new_principal = $("#new_principal").val()
                            var new_interest = $("#new_interest").val();
                            
                            if(new_principal < 0){
                                layer.msg('修改后待还本金不得小于0元' , {icon:2 , time:2000}); 
                                return false;
                            } 
                            if(new_interest < 0){
                                layer.msg('修改后待还本金不得小于0元' , {icon:2 , time:2000}); 
                                return false;
                            } 
                            if(new_principal > principal){
                                layer.msg('修改后待还本金不得大于原待还本金' , {icon:2 , time:2000}); 
                                return false;
                            } 
                            if(new_interest > interest){
                                layer.msg('修改后待还本金不得大于原待还本金' , {icon:2 , time:2000}); 
                                return false;
                            } 

                            //发异步，把数据提交给php
                            $.ajax({
                                url: '/borrower/DealOrder/updateRepayPlan',
                                data: data.field,
                                type:"POST",
                                dataType:'json',
                                success: function (res) {
                                    if(res.code == 0){
                                        layer.alert(res.info, function () {
                                            window.parent.location.reload();
                                        })
                                      
                                    }else{
                                        layer.alert(res.info);
                                    }
                                }
                            })
                            return false;
                        });

            });</script>
</body>

</html>
