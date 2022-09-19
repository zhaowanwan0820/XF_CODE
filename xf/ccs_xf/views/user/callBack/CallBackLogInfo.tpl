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
          .layui-form-label {
            width: 190px
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

                        <div class="layui-card-body res_div">
                          <div class="layui-collapse" lay-filter="test">
                            <div class="layui-colla-item">
                              <h2 class="layui-colla-title">用户状态<i class="layui-icon layui-colla-icon"></i></h2>
                              <div class="layui-colla-content layui-show">
                                <form class="layui-form">

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">号码状态</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_1']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">接听人是否本人</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_2']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">拨打工具</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_3']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">客户状态</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_4']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">客户标签</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_5']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">是否添加微信</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_6']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">关联公司</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_7']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">公司是否存续</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_7_status']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">支付宝认证</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['question_8']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">跟进记录</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['remark']}></div>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label class="layui-form-label">问题类型</label>
                                      <div class="layui-input-inline">
                                          <div style="line-height: 20px;padding: 8px 10px;resize: vertical;"><{$res['type']}></div>
                                      </div>
                                  </div>

                                </form>
                              </div>
                            </div>
                          </div> 
                        </div>
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