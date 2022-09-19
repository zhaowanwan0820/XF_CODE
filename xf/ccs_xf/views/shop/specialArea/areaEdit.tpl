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
                <label for="name" class="layui-form-label">
                    商城名称
                </label>

                <div class="layui-input-inline">
                    <input readonly="readonly" style="background:#CCCCCC ;"  type="text" id="name" name="name" lay-verify="required" value="<{$areaInfo['p_name']}>"
                            class="layui-input">
                </div>

            </div>
            <div class="layui-form-item">
                <label for="name" class="layui-form-label">
                    <span class="x-red">*</span>专区名称
                </label>

                <div class="layui-input-inline">
                    <input type="text" id="name" name="name" lay-verify="required" value="<{$areaInfo['name']}>"
                           class="layui-input">
                </div>

            </div>
            <!--div class="layui-form-item">
                <label for="buyer_uid" class="layui-form-label">
                    <span class="x-red">*</span>受让人用户ID
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="buyer_uid" name="buyer_uid" lay-verify="required|number"   required="" value="<{$areaInfo['buyer_uid']}>"
                           autocomplete="off" class="layui-input">
                </div>
                <div class="layui-form-mid layui-word-aux">
                    <span class="x-red">*</span>
                </div>
            </div-->
            <div class="layui-form-item">
                <label for="L_email" class="layui-form-label">
                    专区代码
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="code" name="code" lay-verify="required" value="<{$areaInfo['code']}>"
                          class="layui-input">
                </div>

            </div>

            <div class="layui-form-item">
                <label for="L_email" class="layui-form-label">
                    <span class="x-red">*</span>状态
                </label>
                <div class="layui-input-inline">
                    <input lay-verify="userType" type="radio" name="status" value="0" title="禁用" <{if $areaInfo['status'] == 0}>checked<{/if}>>
                    <input lay-verify="userType" type="radio" name="status" value="1" title="启用" <{if $areaInfo['status'] == 1}>checked<{/if}>>
                </div>

            </div>
            <input type="hidden" name = "id" value="<{$areaInfo['id']}>">
            <div class="layui-form-item">
                <label for="L_repass" class="layui-form-label">
                </label>
                <button  class="layui-btn" lay-filter="add" lay-submit="">
                    更改
                </button>
            </div>
        </form>
    </div>
</div>
<script>layui.use(['form', 'layer'],
            function() {
                $ = layui.jquery;
                var form = layui.form, layer = layui.layer;
                //自定义验证规则
                form.verify({
                    name: function(value) {
                        if (value.length < 3) {
                            return '名称至少得3个字符啊';
                        }
                    },

                });
                //监听提交
                form.on('submit(add)',
                        function(data) {
                            //发异步，把数据提交给php
                            $.ajax({
                                url: '/shop/SpecialArea/areaEdit',
                                data: data.field,
                                type:"POST",
                                success: function (res) {
                                    if(res.code == 0){
                                        layer.alert(res.info,
                                                function(data,item){
                                                    var index = parent.layer.getFrameIndex(window.name);
                                                    parent.layer.close(index);
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
