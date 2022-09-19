<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>详情</title>
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
                    <label class="layui-form-label">
                        合同编号</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['contract_number']}>">
                    </div>
                </div>

                <{foreach $res['pic_address'] as $k => $v}>
                <div class="layui-form-item">
                    <label class="layui-form-label">合同照片<{$k+1}></label>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$v}>" width="800px">
                    </div>
                </div>
                <{/foreach}>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        <span class="x-red">*</span>审核结果</label>
                    <div class="layui-input-inline">
                        <input type="radio" title="通过" <{if $res['status'] eq 2 }>checked<{/if}>>
                        <input type="radio" title="拒绝" <{if $res['status'] eq 3 }>checked<{/if}>>
                    </div>
                </div>

                <div class="layui-form-item layui-form-text" id="reason_div" <{if $res['status'] eq 2 }>style="display: none;"<{/if}>>
                    <label for="reason" class="layui-form-label">
                        <span class="x-red">*</span>拒绝原因</label>
                    <div class="layui-input-inline">
                        <textarea name="reason" id="reason" class="layui-textarea" readonly style="width: 400px;"><{$res['reason']}></textarea>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        审核人</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['audit_user_name']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        审核时间</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['audit_time']}>">
                    </div>
                </div>

              </form>
            </div>
        </div>
        <script>
        layui.use(['layer' , 'form'] , function(){
          var form  = layui.form;
          var layer = layui.layer;
          layer.photos({
            photos: '.img_list'
          });
        });
      </script>
    </body>

</html>