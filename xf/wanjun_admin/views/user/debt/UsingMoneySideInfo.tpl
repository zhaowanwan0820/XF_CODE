<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>查看借款企业</title>
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

                    <div class="layui-form-item">
                        <label for="name" class="layui-form-label">
                            用款方</label>
                        <div class="layui-input-inline">
                            <input type="hidden" value="<{$res['id']}>" id="id" autocomplete="off" class="layui-input" disabled>
                            <input type="text" value="<{$res['name']}>" id="name" autocomplete="off" class="layui-input" disabled style="background:#c2c2c2;">
                        </div>
                    </div>

                    <div class="layui-card-body ">
                        <table class="layui-table layui-form">
                            <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>借款企业</th>
                                </tr>
                            </thead>
                            <tbody>
                              <{foreach $result as $k => $v}>
                                <tr>
                                    <td><{$v['id']}></td>
                                    <td><{$v['company_name']}></td>
                                </tr>
                              <{/foreach}>
                            </tbody>
                        </table>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn" onclick="set_close()">返回</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer']);

        function set_close()
        {
          var index = parent.layer.getFrameIndex(window.name);
          parent.layer.close(index);
        }
      </script>
    </body>

</html>