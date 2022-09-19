<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>用户管理 批量条件上传</title>
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
        <form class="layui-form" method="post" action="/debtMarket/DebtPurchase/create" enctype="multipart/form-data" id="my_form">

        <!-- <form class="layui-form"> -->
            <div class="layui-form-item">
                <!-- <div class="layui-inline">
                    <label class="layui-form-label">所属平台：</label>
                    <div class="layui-inline " style="width: 190px;">
                        <select name="deal_type"  lay-verify="required"  >
                            <option value=''>请选择</option>
                            <option value="1">尊享</option>
                            <option value="2">普惠</option>
                        </select>
                    </div>
                </div> -->

                <!-- <div class="layui-inline">
                    <label class="layui-form-label">选择专区：</label>
                    <div class="layui-inline " style="width: 190px;">
                        <select name="area_id"  lay-verify="required"  >
                            <option value=''>请选择</option>
                            <{foreach $area_list as $key => $val}>
                            <option value="<{$val['id']}>" ><{$val['name']}></option>
                            <{/foreach}>
                        </select>
                    </div>
                </div> -->
                <div class="layui-inline">
                    <label class="layui-form-label">求购总额（元）：</label>
                    <div class="layui-input-inline">
                        <input type="text" onkeyup="to_calculate()" name="total_amount" id="total_amount"  lay-verify="number" autocomplete="off"  class="layui-input" value="<{$_GET['total_amount']}>">
                    </div>
                </div>

                <div class="layui-inline">
                    <label class="layui-form-label">折扣：</label>
                    <div class="layui-input-inline">
                        <input type="text" onkeyup="to_calculate()" name="discount" id="discount"  lay-verify="number" placeholder="折扣金额0.01~10" autocomplete="off"  class="layui-input" value="<{$_GET['discount']}>">
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">预算金额（元）：</label>
                    <div class="layui-input-inline" style="margin-top: 10px;" id='budget_amount'>
                    </div>
                   
                </div>

                <div class="layui-inline">
                    <label class="layui-form-label">有效期（天）：</label>
                    <div class="layui-input-inline">
                        <input type="text" name="period_validity" id="period_validity" lay-verify="number" autocomplete="off"  class="layui-input" value="<{$_GET['period_validity']}>">
                    </div>
                    <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>有效期最长30天</div>

                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">受让人：</label>
                    <div class="layui-inline layui-input-block">
                        <{foreach $buyer_list as $key => $val}>
                            <input type="radio" class="buyer_people" name="buyer_people" value="<{$val['id']}>" title=<{$val['info']}> >
                        <{/foreach}>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">指定借款ID文件</label>
                    <div class="layui-input-inline">
                        <button type="button" class="layui-btn layui-btn-normal"  onclick="add_file()">上传</button>
                        <span id="file_name"></span>
                        <input type="file" id="file" name="file" autocomplete="off" class="layui-input" style="display: none;" onchange="change_name(this.value)">
                    </div>
                    <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>仅支持普惠项目，请上传 xls 文件（数据量不可超过一万行）。
                        <a href="/upload/求购项目导入模板.xlsx" style="color: blue;">下载模板</a>
                    </div>

                </div>
            </div>
           
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label"></label>
                <!-- <button  class="layui-btn" lay-filter="add" lay-submit="">
                    提交
                </button> -->
                <button type="button" class="layui-btn" onclick="add()">增加</button>

                <button type="reset" class="layui-btn layui-btn-primary">重置</button>

            </div>
        </form>
       
    </div>
</div>
<script>


    layui.use(['form', 'layer', 'laydate'], function () {
        $ = layui.jquery;
        var laydate = layui.laydate;
        var form = layui.form;       
         //监听提交
         form.verify({
                    
                    itemId: function(value) {
                        if (value == 0) {
                            return '请选择专区';
                        }
                    },
                   
                });

         form.on('submit(add)',
                        function(data) {
                            console.log(data);
                            //发异步，把数据提交给php
                            var buyer_people         = $(".buyer_people:checked").val();
                            console.log(buyer_people,buyer_people == undefined);
                            var period_validity = $("#period_validity").val()
                            var discount = $("#discount").val();
                            
                            if(buyer_people == undefined){
                                layer.msg('请选择受让人' , {icon:2 , time:2000}); 
                                return false;
                            } 
                            if(discount >10 || discount<0.01){
                                layer.msg('请确保折扣值在0.01~10范围' , {icon:2 , time:2000}); 
                                return false;
                            }
                            if(period_validity>30){
                                layer.msg('有效期最长30天' , {icon:2 , time:2000});
                                return false;
                            }
                            if(period_validity<0){
                                layer.msg('有效期1~30天' , {icon:2 , time:2000});
                                return false;
                            }
                            $.ajax({
                                url: '/debtMarket/DebtPurchase/create?area_id='+<{$_GET['area_id']}>,
                                data: data.field,
                                type:"POST",
                                dataType:'json',
                                success: function (res) {
                                    
                                    if(res.code == 0){
                                        layer.alert("添加成功",
                                                function(data,item){
                                                    var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                                                    parent.layer.close(index); //再执行关闭
                                                    parent.location.reload();
                                                });
                                    }else{
                                        layer.alert(res.info);
                                    }
                                }
                            })
                            return false;
                            
                        });



    });

    function add() {
      
                           
        var buyer_people         = $(".buyer_people:checked").val();
        console.log(buyer_people,buyer_people == undefined);
        var period_validity = $("#period_validity").val()
        var discount = $("#discount").val();
        var file    = $("#file").val();

        if(buyer_people == undefined){
            layer.msg('请选择受让人' , {icon:2 , time:2000}); 
            return false;
        } 
        if(discount >10 || discount<0.01){
            layer.msg('请确保折扣值在0.01~10范围' , {icon:2 , time:2000}); 
            return false;
        }
        if(period_validity>30){
            layer.msg('有效期最长30天' , {icon:2 , time:2000});
            return false;
        }
        if(period_validity<0){
            layer.msg('有效期1~30天' , {icon:2 , time:2000});
            return false;
        }
        if(file==''){
            layer.msg('请上传文件' , {icon:2 , time:2000});
            return false;
        }
        $("#my_form").submit();
    }

  
    function add_file() {
        $("#file").click();
    }

    function change_name(name) {
        var string   = name.lastIndexOf("\\");
        var new_name = name.substring(string+1);  
        $("#file_name").html(new_name);
        $("#file_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
    }

    function to_calculate(){
        var total_amount = $("#total_amount").val();
        if(total_amount<0){
            $("#total_amount").val("");
            return false;
        }
        var discount = $("#discount").val();
        if(discount<0 || discount>10){
            $("#discount").val("");
            return false;
        }
        str = Math.round(parseFloat(total_amount * discount * 0.1) * 100) / 100;
        $("#budget_amount").html(str);
    }

    function add_template() {
        $("#template").click();
    }

    function change_template(name) {
        var string = name.lastIndexOf("\\");
        var new_name = name.substring(string + 1);
        $("#template_name").html(new_name);
    }

    function purchase_create() {
        if ($("#doh").hasClass("disabled")) {
            layer.alert('处理中，请勿重复提交');
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