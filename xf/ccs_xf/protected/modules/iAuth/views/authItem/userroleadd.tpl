<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>欢迎页面-</title>
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
                <label for="name" class="layui-form-label">
                    <span class="x-red">*</span>角色名
                </label>
                <div class="layui-input-inline">
                    <input type="text" id="name" name="name" required="" lay-verify="required"
                           autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label">
                    拥有权限
                </label>
                <table  class="layui-table layui-input-block">
                    <tbody>
                    <{foreach $ret as $key => $val}>
                    <tr>
                        <td>
                            <input type="checkbox"  name="topItemId[]" value="<{$val['id']}>" lay-skin="primary" lay-filter="father" title="<{$val['name']}>">
                        </td>
                        <td>
                            <div class="layui-input-block">
                                <{foreach $val['listrolename'] as $k => $v}>
                                <input name="childids[]" lay-skin="primary"  type="checkbox" lay-filter="child" title="<{$v['name']}>" value="<{$v['id']}>">
                                <{/foreach}>
                            </div>
                        </td>
                    </tr>
                    <{/foreach}>
                    </tbody>
                </table>
            </div>
            <div class="layui-form-item layui-form-text">
                <label for="desc" class="layui-form-label">
                    描述
                </label>
                <div class="layui-input-block">
                    <textarea placeholder="请输入内容" id="desc" name="desc" class="layui-textarea"></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <button class="layui-btn" lay-submit="" lay-filter="add">增加</button>
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
            //发异步，把数据提交给php
            $.ajax({
                url: '/iauth/AuthItem/UserRoleAdd',
                data: data.field,
                type:"POST",
                success: function (res) {
                    if(res.code == 0){
                        layer.alert("添加成功",
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
        form.on('checkbox(father)', function(data){
            if(data.elem.checked){
                $(data.elem).parent().siblings('td').find('input').prop("checked", true);
                form.render();
            }else{
                $(data.elem).parent().siblings('td').find('input').prop("checked", false);
                form.render();
            }
        });
        form.on('checkbox(child)', function(data){
            if(data.elem.checked){
                //获取同行第一列的节点
                console.log($(data.elem).parent());
//                console.info($(data.elem).parent().find(':input:last').val());
            }
        })

    });
</script>
</body>

</html>