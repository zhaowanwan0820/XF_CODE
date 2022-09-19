<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>查看授权债转协议图片</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
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
                <form class="layui-form">

                    <!-- <div class="layui-form-item">
                        <label class="layui-form-label"><span class="x-red">*</span>图片</label>
                        <input type="file" style="display: none;" onchange="on_change(this)" id="pic">
                        <input type="hidden" id="agreement_pic" value="">
                        <div class="layui-input-inline">
                            <div class="layui-upload-list" style="margin:0">
                                <img src="/<{$res['agreement_pic']}>" id="img" class="layui-upload-img" width="100%">
                            </div>
                        </div>
                        <div class="layui-form-mid layui-word-aux">授权债转协议图片</div>
                    </div> -->

                    <a href="/<{$res['agreement_pic']}>">下载</a>

                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer']);
      </script>
    </body>

</html>