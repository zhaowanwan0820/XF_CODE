<!doctype html>
<html  class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>后台登录</title>
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
                <div class="layui-inline">
                    <label class="layui-form-label"><span class="x-red">*</span>选择商城</label>
                    <div class="layui-inline layui-show-xs-block">
                        <select name="appid">
                            <{foreach $shopList as $key => $val}>
                            <option value="<{$val['id']}>" ><{$val['name']}></option>
                            <{/foreach}>
                        </select>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">
                    <span class="x-red">*</span>专区名称
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="name" name="name" value="" lay-verify="required"    required="" lay-verify="required"
                           autocomplete="off" class="layui-input">
                </div>

            </div>
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">
                    <span class="x-red">*</span>专区代码
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="code" name="code" value="" lay-verify="required"    required="" lay-verify="required"
                           autocomplete="off" class="layui-input">
                </div>

            </div>
            <!--div class="layui-form-item">
                <label for="buyer_uid" class="layui-form-label">
                    <span class="x-red">*</span>受让人用户ID
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="buyer_uid" name="buyer_uid" lay-verify="required|number"     required="" value="<{$shopInfo['buyer_uid']}>"
                           autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    <span class="x-red">*</span>
                </div>
            </div-->

            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label">
                </label>
                <button  class="layui-btn" lay-filter="add" lay-submit="">
                    增加
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
                form.on('submit(add)',
                        function(data) {
                            console.log(data);
                            //发异步，把数据提交给php
                            $.ajax({
                                url: '/shop/SpecialArea/areaAdd',
                                data: data.field,
                                type:"POST",
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

            });</script>
</body>

</html>
