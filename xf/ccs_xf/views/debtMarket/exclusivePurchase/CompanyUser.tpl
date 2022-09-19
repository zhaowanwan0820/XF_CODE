<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>定向收购-已分配用户列表</title>
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
                <a>
                    <cite>定向收购-已分配用户列表</cite></a>
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
                                            <input type="text" name="user_id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">姓名</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="real_name" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>



                                        <div class="layui-inline">
                                          <label class="layui-form-label">收购状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="6">待收购</option>
                                              <option value="0">待签约</option>
                                              <option value="1">待付款</option>
                                                <option value="2">已付款待债转</option>
                                                <option value="3">已债转待生成合同</option>
                                                <option value="4">交易完成</option>
                                                <option value="5">已失效待收购</option>
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
              field : 'user_id',
              title : '用户ID',
              width : 100
            },
            {
              field : 'real_name',
              title : '姓名',
            },
            {
              field : 'mobile',
              title : '手机号',
            } ,
              {
                  field : 'wait_capital',
                  title : '在途债权',
              },
              {
                  field : 'ph_increase_reduce',
                  title : '普惠充提差',
              },
              {
                  field : 'purchase_amount',
                  title : '预收购金额',
              },
              {
                  field : 'assignee_name',
                  title : '受让人',
                  width : 350
              },
              {
                  field : 'status_name',
                  title : '收购状态',
              },
               {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 100
               }
          ]],
          url      : '/debtMarket/exclusivePurchase/companyUser',
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

        table.on('toolbar(list)' , function(obj){
          var checkStatus = table.checkStatus(obj.config.id);
          switch(obj.event){
            case 'add':
              xadmin.open('添加受让方' , '/user/exclusivePurchase/AddAssignee');
              break;
          };
        });

        form.on('submit(sreach)', function(obj){
          table.reload('list', {
            where    :
            {
              user_id    : obj.field.user_id,
              real_name  : obj.field.real_name,
              mobile     : obj.field.mobile,
              status     : obj.field.status
            },
              page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

            if (layEvent === 'send_sms') {
                send_sms(data);
            }
        });

      });

      function send_sms(data) {
          layer.confirm('确认要下发短信吗？', function (index) {
              $.ajax({
                  url: '/debtMarket/ExclusivePurchase/SendSms',
                  data: {id:data.user_id },
                  type: "POST",
                  dataType:'json',
                  success: function (res) {
                      if (res.code == 0) {
                          layer.alert(res.info);
                          location.reload()
                      } else {
                          layer.alert(res.info);
                      }
                  }
              });
          })
      }
    </script>

    <script type="text/html" id="operate">
        {{# if(d.status == 0 && d.audit_status == 1){ }}
        <button class="layui-btn" title="下发短信" lay-event="send_sms">下发短信</button>
        {{# } }}
    </script>
</html>