<!doctype html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>先锋贷后管理系统</title>
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <!-- <link rel="stylesheet" href="<{$CONST.cssPath}>/theme5.css"> -->
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <script>
        // 是否开启刷新记忆tab功能
//        var is_remember = false;
    </script>
</head>
<body class="index">
<!-- 顶部开始 -->
<div class="container">
    <div class="logo">
        <a href="#">先锋贷后管理系统</a></div>
    <div class="left_open">
        <a><i title="展开左侧栏" class="iconfont">&#xe699;</i></a>
    </div>
    <ul class="layui-nav left fast-add" lay-filter="">

    </ul>
    <ul class="layui-nav right" lay-filter="">
        <li class="layui-nav-item">
            <a href="javascript:;"><{$uname}></a>
            <dl class="layui-nav-child">
                <!-- 二级菜单 -->
                <dd>
                    <a onclick="xadmin.open('修改密码','/user/Debt/EditPassword',800,600)">修改密码</a></dd>
                <dd>
                    <a href="/logout">退出</a></dd>
            </dl>
        </li>
    </ul>
</div>
<!-- 顶部结束 -->
<!-- 中部开始 -->
<!-- 左侧菜单开始 -->
<div class="left-nav">
    <div id="side-nav">
        <ul id="nav">
            <{if !empty($navigationNewList)}>
            <{foreach $navigationNewList as $key => $val}>
            <li>
                <a href="javascript:;">
                    <i class="iconfont left-nav-li" lay-tips="<{$val.n_name}>">&#<{$val.icon}></i>
                    <cite><{$val.n_name}></cite>
                    <i class="iconfont nav_right"></i></a>
                <ul class="sub-menu">
                    <{foreach $val.children as $k => $v}>
                    <li>
                        <a onclick="xadmin.add_tab('<{$v.n_name}>','<{$v.code}>')">
                            <i class="iconfont">&#xe6a7;</i>
                            <cite><{$v.n_name}></cite></a>
                    </li>
                    <{/foreach}>
                </ul>
            </li>
            <{/foreach}>
            <{/if}>
        </ul>
    </div>
</div>
<!-- <div class="x-slide_left"></div> -->
<!-- 左侧菜单结束 -->
<!-- 右侧主体开始 -->
<div class="page-content">
    <div class="layui-tab tab" lay-filter="xbs_tab" lay-allowclose="false">
        <ul class="layui-tab-title">
            <li class="home">
                <i class="layui-icon">&#xe68e;</i>首页</li></ul>
        <div class="layui-unselect layui-form-select layui-form-selected" id="tab_right">
            <dl>
                <dd data-type="this">关闭当前</dd>
                <dd data-type="other">关闭其它</dd>
                <dd data-type="all">关闭全部</dd></dl>
        </div>
        <div class="layui-tab-content">
            <div class="layui-tab-item layui-show">
                <iframe src='/default/index/welcome' frameborder="0" scrolling="yes" class="x-iframe"></iframe>
            </div>
        </div>
        <div id="tab_show"></div>
    </div>
</div>
<div class="page-content-bg"></div>
<style id="theme_style"></style>
<!-- 右侧主体结束 -->
<!-- 中部结束 -->
</body>

</html>

<script>
    window.onload = function() {
        window.location.reload = new_fun.reload
    }
    const new_fun = {
        reload: function() {
                    console.log('reload')
                    var now_frame = $('.layui-show').children()
                    now_frame.context.location.reload(true);
                }
    }
    
</script>