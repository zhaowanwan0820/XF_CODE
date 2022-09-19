<!doctype html>
<html class="x-admin-sm">
    <head>
        <meta charset="UTF-8">
        <title>提示页面</title>
        <meta name="renderer" content="webkit|ie-comp|ie-stand">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <meta http-equiv="Cache-Control" content="no-siteapp" />

        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    </head>
    <body>
          <div class="layui-container">
           <div class="fly-panel">
            <div class="fly-none">
              <{if $type eq 1}>
              <h2><i class="layui-icon">&#xe6af;</i></h2>
              <{/if}>
              <{if $type eq 2}>
              <h2><i class="layui-icon">&#xe69c;</i></h2>
              <{/if}>
              <p><{$msg}>  本页即将关闭：<span id="time"><{$time}></span></p> 
            </div>
           </div>
          </div>
    <script>
    var time = <{$time}>;
    window.onload = function () {
      var a = window.setInterval(function() {
        time--;
        $("#time").html(time);
        if (time == 0) {
          window.clearInterval(a);
          parent.location.reload();
        }
      },1000);
    }
    </script>
    </body>
</html>