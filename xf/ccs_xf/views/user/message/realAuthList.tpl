<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>用户实名认证记录</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
        <!--[if lt IE 9]>
          <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
          <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    
    <body>
        <div class="x-nav">
            <span class="layui-breadcrumb">
                <a href="">用户信息维护</a>
                <a>
                    <cite>用户实名认证记录</cite></a>
            </span>
            <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
                <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
            </a>
        </div>
        <div class="layui-fluid">
            <div class="layui-row layui-col-space15">

                <div class="layui-col-md12">

                    <div class="layui-card">
                        <div class="layui-card-body">
                            <div class="layui-collapse" lay-filter="test">
                                <div class="layui-colla-item">
                                <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                                <div class="layui-colla-content layui-show">
                                  <form class="layui-form" action="">

                                      <div class="layui-form-item">

                                          <div class="layui-inline">
                                              <label class="layui-form-label">用户ID</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="id"  id="id"  autocomplete="off" class="layui-input">
                                              </div>
                                          </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">姓名</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="real_name"  id="real_name"  autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" id="mobile"   autocomplete="off" class="layui-input">
                                          </div>
                                        </div>


                                          <div class="layui-inline">
                                              <label class="layui-form-label">实名认证时间</label>
                                              <div class="layui-input-inline">
                                                  <input class="layui-input" placeholder="开始时间" name="start" id="start" readonly>
                                              </div>
                                              <div class="layui-input-inline" style="width: 5px;">
                                                  ~
                                              </div>
                                              <div class="layui-input-inline">
                                                  <input class="layui-input" placeholder="截止时间" name="end" id="end" readonly>
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label class="layui-form-label">授权时间</label>
                                              <div class="layui-input-inline">
                                                  <input class="layui-input" placeholder="开始时间" name="start01" id="start01" readonly>
                                              </div>
                                              <div class="layui-input-inline" style="width: 5px;">
                                                  ~
                                              </div>
                                              <div class="layui-input-inline">
                                                  <input class="layui-input" placeholder="截止时间" name="end01" id="end01" readonly>
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label class="layui-form-label">是否授权集约诉讼</label>
                                              <div class="layui-input-inline">
                                                  <select name="sign_status" id="sign_status" lay-search="">
                                                      <option value="0">全部</option>
                                                      <option value="1">是</option>
                                                      <option value="2">否</option>
                                                  </select>
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label class="layui-form-label">是否提交补充文件</label>
                                              <div class="layui-input-inline">
                                                  <select name="pic_status" id="pic_status" lay-search="">
                                                      <option value="0">全部</option>
                                                      <option value="1">是</option>
                                                      <option value="2">否</option>
                                                  </select>
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label class="layui-form-label">是否已编辑证件照片</label>
                                              <div class="layui-input-inline">
                                                  <select name="pic_edit_status" id="pic_edit_status" lay-search="">
                                                      <option value="0">全部</option>
                                                      <option value="1">是</option>
                                                      <option value="2">否</option>
                                                  </select>
                                              </div>
                                          </div>

                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                        </div>
                        <div class="layui-card-body">
                          <table class="layui-table layui-form" lay-filter="list" id="list">
                          </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script>
      layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;
          laydate = layui.laydate;

          laydate.render({
              elem: '#start'
          });

          laydate.render({
              elem: '#end',
          });
          laydate.render({
              elem: '#start01'
          });

          laydate.render({
              elem: '#end01',
          });
        table.render({
          elem           : '#list',
          toolbar        : '#toolbar',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
              {
                  field : 'id',
                  title : '用户ID',
                  width : 100
              },
            {
                field : 'real_name',
                title : '姓名',
                width : 100
            },
            {
                field : 'mobile',
                title : '手机号',
                width : 120
            },
            {
                field : 'idno',
                title : '证件号',
                width : 160
            } ,
              {
                  field : 'bank_card',
                  title : '银行卡号',
                  width : 160
              },

              {
                  field : 'fdd_real_time',
                  title : '实名认证时间',
                  width : 160
              },
              {
                  field : 'intensive_sign_time',
                  title : '集约诉讼授权时间',
                  width : 160
              },
              {
                  field : 'intensive_idcard_time',
                  title : '补充文件提交时间',
                  width : 160
              },{
                  field : 'intensive_idcard_face',
                  title : '用户上传证件-正面',
                  width : 160
              },{
                  field : 'intensive_idcard_back',
                  title : '用户上传证件-反面',
                  width : 160
              },{
                  field : 'intensive_idcard_face_edit',
                  title : '编辑用户证件-正面',
                  width : 160
              },{
                  field : 'intensive_idcard_back_edit',
                  title : '编辑用户证件-反面',
                  width : 160
              },{
                  field : 'op_user_name',
                  title : '用户证件照编辑人',
                  width : 130
              },
              {
                  field : 'op_text',
                  title : '操作',
                  fixed   : 'right',
                  width : 350
              },
          ]],
          url : '/user/message/realAuthList',
          method   : 'post',
          response :
          {
            statusName : 'code',
            statusCode : 0,
            msgName    : 'info',
            countName  : 'count',
            dataName   : 'data'
          }
        });
        form.on('submit(sreach)', function(obj){
          table.reload('list', {
            where:
            {
                id : obj.field.id,
              real_name : obj.field.real_name,
              mobile : obj.field.mobile,
                start : obj.field.start,
                end : obj.field.end,
                start01 : obj.field.start01,
                end01 : obj.field.end01,
                sign_status: obj.field.sign_status,
                pic_status: obj.field.pic_status,
                pic_edit_status:obj.field.pic_edit_status,
            },
              page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
            if (layEvent === 'info') {
                window.location.href=data.intensive_oss_contract_url;
            }

        });

      });


function edit_user_pic(id){
    xadmin.open('编辑用户证件照', '/user/message/editUserPic?id='+id,900,600);
}
    </script>
    <script type="text/html" id="operate">
        {{# if(d.intensive_oss_contract_url != '' ){ }}
        <a class="layui-btn layui-btn-xs" title="集约诉讼合同" lay-event="info" >集约诉讼合同</a>
        {{# } }}
    </script>
</html>