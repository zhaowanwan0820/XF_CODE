<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>下车用户明细</title>
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
        <style type="text/css">
          .layui-table th {
                border-color: #666666;
          }
          .layui-table td {
                border-color: #666666;
          }
        </style>
    </head>
    
    <body>
        <div class="x-nav">
            <span class="layui-breadcrumb">
                <a href="">下车专栏</a>
                <a>
                    <cite>下车用户明细</cite></a>
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
                                                <input type="text" name="user_id" id="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户姓名</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="real_name" id="real_name" placeholder="请输入用户姓名" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="mobile" id="mobile" placeholder="请输入用户手机号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">下车归属礼包段</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name" id="name" placeholder="请输入礼包名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <!-- <div class="layui-inline">
                                          <label class="layui-form-label">下车状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">待下车</option>
                                              <option value="2">已下车</option>
                                            </select>
                                          </div>
                                        </div> -->

                                        <div class="layui-inline">
                                          <label class="layui-form-label">下车时间</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="开始时间" name="start" id="start" readonly>
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="截止时间" name="end" id="end" readonly>
                                          </div>
                                        </div>

                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                          <{if $UserDetails2Excel == 1 }>
                                          <button type="button" class="layui-btn layui-btn-danger" onclick="UserDetails2Excel()">导出</button>
                                          <{/if}>
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
            elem: '#end'
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
              field : 'user_id',
              title : '用户ID',
              align : 'right',
              width : 100
            },
            {
              field : 'real_name',
              title : '用户姓名',
              align : 'right',
              width : 100
            },
            {
              field : 'mobile',
              title : '用户手机号',
              align : 'right',
              width : 110
            },
            {
              field : 'debt_total',
              title : '初始用户总债权',
              align : 'right',
              style : 'background-color: #ddebf7; color: #666666;',
              width : 120
            },
            {
              field : 'yr_debt_total',
              title : '初始用户悠融债权',
              align : 'right',
              style : 'background-color: #ddebf7; color: #666666;',
              width : 130
            },
            {
              field : 'initial_name',
              title : '初始归属礼包段',
              align : 'right',
              style : 'background-color: #ddebf7; color: #666666;',
              width : 120
            },
            {
              field : 'initial_avg_debt',
              title : '初始礼包段内平均债权',
              align : 'right',
              style : 'background-color: #ddebf7; color: #666666;',
              width : 150
            },
            {
              field : 'initial_avg_liquidation_cost',
              title : '初始礼包成本',
              align : 'right',
              style : 'background-color: #ddebf7; color: #666666;',
              width : 110
            },
            {
              field : 'user_yr_cost',
              title : '初始用户化债可回收成本',
              align : 'right',
              style : 'background-color: #ddebf7; color: #666666;',
              width : 170
            },
            {
              field : 'user_cost',
              title : '初始用户化债成本减少额度',
              align : 'right',
              style : 'background-color: #ddebf7; color: #666666;',
              width : 150
            },
            {
              field : 'real_debt_total',
              title : '实际用户总债权',
              align : 'right',
              style : 'background-color: #e2efda; color: #666666;',
              width : 120
            },
            {
              field : 'real_yr_debt_total',
              title : '实际用户悠融债权',
              align : 'right',
              style : 'background-color: #e2efda; color: #666666;',
              width : 130
            },
            {
              field : 'name',
              title : '实际归属礼包段',
              align : 'right',
              style : 'background-color: #e2efda; color: #666666;',
              width : 120
            },
            {
              field : 'avg_debt',
              title : '实际礼包段内平均债权',
              align : 'right',
              style : 'background-color: #e2efda; color: #666666;',
              width : 150
            },
            {
              field : 'avg_liquidation_cost',
              title : '实际礼包成本',
              align : 'right',
              style : 'background-color: #e2efda; color: #666666;',
              width : 110
            },
            {
              field : 'real_user_yr_cost',
              title : '实际用户化债可回收成本',
              align : 'right',
              style : 'background-color: #e2efda; color: #666666;',
              width : 170
            },
            {
              field : 'real_user_cost',
              title : '实际用户化债成本减少额度',
              align : 'right',
              style : 'background-color: #e2efda; color: #666666;',
              width : 150
            },
            // {
            //   field : 'status',
            //   title : '下车状态',
            //   align : 'right',
            //   width : 150
            // },
            {
              field : 'liquidation_time',
              title : '下车时间',
              align : 'right',
              width : 150
            }
          ]],
          url      : '/user/DebtLiquidation/UserDetails',
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
            where :
            {
              user_id   : obj.field.user_id,
              real_name : obj.field.real_name,
              mobile    : obj.field.mobile,
              name      : obj.field.name,
              status    : obj.field.status,
              start     : obj.field.start,
              end       : obj.field.end
            },
            page:{curr:1}
          });
          return false;
        });

      });

      function UserDetails2Excel()
      {
        var user_id   = $("#user_id").val();
        var real_name = $("#real_name").val();
        var mobile    = $("#mobile").val();
        var name      = $("#name").val();
        var status    = $("#status").val();
        var start     = $("#start").val();
        var end       = $("#end").val();
        if (user_id == '' && real_name == '' && mobile == '' && name == '' && status == '' && start == '' && end == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/DebtLiquidation/UserDetails2Excel?user_id="+user_id+"&real_name="+real_name+"&mobile="+mobile+"&name="+name+"&status="+status+"&start="+start+"&end="+end , "_blank");
          });
        }
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
      </div > 
    </script>
</html>