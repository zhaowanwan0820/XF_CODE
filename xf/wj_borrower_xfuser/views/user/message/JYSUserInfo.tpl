<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>申请详情</title>
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

                <input type="hidden" name="id" value="<{$res['id']}>">

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        用户ID</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['user_id']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        用户姓名</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['real_name']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        用户证件号</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['idno']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        手机号</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['new_mobile']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        申请时间</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['add_time']}>">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        审核状态</label>
                    <div class="layui-input-inline">
                        <input type="text" autocomplete="off" class="layui-input" readonly value="<{$res['status_name']}>">
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

                <div class="layui-form-item layui-form-text" id="reason_div">
                    <label for="reason" class="layui-form-label">
                        拒绝原因</label>
                    <div class="layui-input-inline">
                        <textarea name="reason" id="reason" class="layui-textarea" style="width: 400px;" disabled><{$res['reason']}></textarea>
                    </div>
                </div>

                <{if $res['id_pic_front'] }>
                <div class="layui-form-item">
                    <label class="layui-form-label">身份证正面照片</label>
                    <div class="layui-input-inline">
                    </div>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$res['id_pic_front_add']}>" width="400px">
                    </div>
                </div>
                <{/if}>
                <{if $res['id_pic_back'] }>
                <div class="layui-form-item">
                    <label class="layui-form-label">身份证背面照片</label>
                    <div class="layui-input-inline">
                    </div>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$res['id_pic_back_add']}>" width="400px">
                    </div>
                </div>
                <{/if}>
                <{if $res['user_pic_front'] }>
                <div class="layui-form-item">
                    <label class="layui-form-label">用户手持身份证正面照片</label>
                    <div class="layui-input-inline">
                    </div>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$res['user_pic_front_add']}>" width="400px">
                    </div>
                </div>
                <{/if}>
                <{if $res['user_pic_back'] }>
                <div class="layui-form-item">
                    <label class="layui-form-label">用户手持身份证背面照片</label>
                    <div class="layui-input-inline">
                    </div>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$res['user_pic_back_add']}>" width="400px">
                    </div>
                </div>
                <{/if}>
                <{if $res['contract_pic'] }>
                <div class="layui-form-item">
                    <label class="layui-form-label">合同照片</label>
                    <div class="layui-input-inline">
                    </div>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$res['contract_pic_add']}>" width="400px">
                    </div>
                </div>
                <{/if}>
                <{if $res['evidence_pic'] }>
                <div class="layui-form-item">
                    <label class="layui-form-label">付款凭证照片</label>
                    <div class="layui-input-inline">
                    </div>
                    <div class="layui-form-mid layui-word-aux img_list">
                        <img src="<{$res['evidence_pic_add']}>" width="400px">
                    </div>
                </div>
                <{/if}>
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