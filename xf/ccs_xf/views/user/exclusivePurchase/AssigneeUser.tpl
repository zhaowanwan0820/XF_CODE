<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>分配用户ID详情</title>
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
                <a href="">债转市场管理</a>
                <a href="">定向收购-受让人管理</a>
                <a>
                    <cite>分配用户ID详情</cite></a>
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
                                            <input type="text" name="user_id" id="user_id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">上传状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">上传成功</option>
                                              <option value="2">上传失败</option>
                                              <option value="3">上传成功（失效）</option>
                                            </select>
                                          </div>
                                        </div>

                                          <div class="layui-inline">
                                              <label class="layui-form-label">收购状态</label>
                                              <div class="layui-input-inline">
                                                  <select name="purchase_status" id="purchase_status" lay-search="">
                                                      <option value="-1">全部</option>
                                                      <option value="0">待收购</option>
                                                      <option value="1">已收购</option>
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
                  title : '序号',
                  width : 100
              }  ,{
              field : 'user_id',
              title : '用户ID',
              width : 150
            }  ,
            {
              field : 'status_name',
              title : '上传状态',
              width : 150
            },
              {
                  field : 'purchase_status_name',
                  title : '收购状态',
                  width : 150
              },
            {
              field : 'add_time',
              title : '上传时间',
              width : 200
            },
            {
              field : 'failed_reason',
              title : '原因'
            }
          ]],
          url      : '/user/exclusivePurchase/AssigneeUser',
          method   : 'post',
          where    : {assignee_user_id : <{$_GET['assignee_user_id']}>},
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
            where    :
            {
                user_id     : obj.field.user_id,
                purchase_status   : obj.field.purchase_status,
              status      : obj.field.status
            }
          });
          return false;
        });

          table.on('toolbar(list)' , function(obj){
              switch(obj.event){
                  case 'daochu':
                      daochu();
                      break;
              };
          });

      });


      function daochu()
      {
          var user_id      = $("#user_id").val();
          var status       = $("#status").val();
          var purchase_status = $("#purchase_status").val();
          layer.confirm('确认要根据当前筛选条件导出吗？',
              function(index) {
                  layer.close(index);
                  window.open("/user/exclusivePurchase/AssigneeUser2Excel?assignee_user_id=<{$_GET['assignee_user_id']}>&user_id="+user_id+"&status="+status+"&purchase_status="+purchase_status , "_blank");
              });
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
          <button class="layui-btn layui-btn-xs" title="导出" lay-event="daochu">导出</button>
      </div > 
    </script>
    <script type="text/html" id="operate">
    </script>
</html>