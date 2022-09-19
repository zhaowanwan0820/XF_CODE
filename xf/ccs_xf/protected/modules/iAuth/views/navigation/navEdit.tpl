<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>导航栏编辑-</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
</head>
<body>
<div class="layui-fluid">
    <div class="layui-row">
        <form action="" method="post" class="layui-form layui-form-pane">
            <div class="layui-form-item">
                <label class="layui-form-label">所属导航栏</label>
                <div class="layui-input-block">
                    <div class="layui-input-inline" style="width: 190px">
                        <select name="parent_id" lay-filter="parent_id">
                            <option value="0">顶级栏目</option>
                            <{foreach $top as $k => $v}>
                            <option value="<{$v.id}>" <{if $v.id == $navigationInfo.parent_id}> selected = "selected"<{/if}>><{$v.n_name}></option>
                            <{/foreach}>
                        </select>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <label for="username" class="layui-form-label">
                    <span class="x-red"></span>导航栏名称
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="n_name" name="n_name"  value="<{$navigationInfo['n_name']}>" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label for="username" class="layui-form-label">
                    <span class="x-red"></span>导航栏规则
                </label>

                <div class="layui-input-inline">
                    <input type="text" id="code" name="code" value="<{$navigationInfo['code']}>" class="layui-input">
                    <input type="hidden" name = "id" value="<{$navigationInfo['id']}>">
                </div>

            </div>
            <{if $navigationInfo['parent_id'] == 0}>
            <div class="layui-form-item">

                <label for="username" class="layui-form-label">
                    <span class="x-red"></span>导航栏图标
                </label>
                <div class="layui-input-inline">
                    <input type="text" name="icon" id="icon" placeholder="导航栏图标" onclick="xadmin.open('编辑','/iauth/Navigation/NavIconEdit')" autocomplete="off"
                           class="layui-input" value="<{$navigationInfo['icon']}>">
                </div>
            </div>
            <{/if}>
            <div class="layui-form-item">
                <button class="layui-btn" lay-submit="" lay-filter="add">编辑</button>
            </div>
        </form>
    </div>
</div>
<script>
    layui.use(['form','layer'], function(){
        $ = layui.jquery;
        var form = layui.form
                ,layer = layui.layer;
        //监听提交
        form.on('submit(add)', function(data){
            console.log(data);
            //发异步，把数据提交给php
            $.ajax({
                url: '/iauth/Navigation/NavEdit',
                data: data.field,
                type:"POST",
                success: function (res) {
                    if(res.code == 0){
                        layer.alert("编辑成功",
                                function(data,item){
                                    location.reload();
                                });
                    }else{
                        layer.alert(res.info);
                    }
                }
            })
            return false;
        });
    });
</script>
</body>

</html>