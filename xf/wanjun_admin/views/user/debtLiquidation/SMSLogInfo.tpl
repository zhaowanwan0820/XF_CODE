<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>呼叫记录问题详情</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
        <style type="text/css">
          .layui-form-item {
            margin-bottom: 7px;
          }
        </style>
        <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
        <!--[if lt IE 9]>
            <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
            <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
        <![endif]--></head>
    
    <body>
        <div class="layui-fluid">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                      <form class="layui-form">

                        <div class="layui-form-item">
                            <label class="layui-form-label">
                                <{$res['type_name']}></label>
                            <div class="layui-input-inline">
                                <div style="padding: 11px;"><{$res['send_mobile']}></div>
                            </div>
                        </div>

                        <div class="layui-form-item">
                            <label class="layui-form-label">
                                时间</label>
                            <div class="layui-input-inline">
                                <div style="padding: 11px;"><{$res['add_time']}></div>
                            </div>
                        </div>

                        <div class="layui-form-item">
                            <label class="layui-form-label">
                                对接号码</label>
                            <div class="layui-input-inline">
                                <div style="padding: 11px;"><{$res['receive_mobile']}></div>
                            </div>
                        </div>

                        <div class="layui-form-item">
                            <label class="layui-form-label">
                                短信内容</label>
                            <div class="layui-input-inline">
                                <div id="gift_name" style="padding: 11px;width: 300px"><{$res['content']}></div>
                            </div>
                        </div>

                      </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
        layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
          form    = layui.form;
          layer   = layui.layer;
          table   = layui.table;
          laydate = layui.laydate;
        });
      </script>
    </body>
    <script type="text/html" id="operate">
    </script>
</html>