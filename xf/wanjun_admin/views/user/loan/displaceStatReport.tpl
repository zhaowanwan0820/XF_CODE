<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>数据报表</title>
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
                <a href="">数据统计</a>
                <a>
                    <cite>数据报表</cite></a>
            </span>
            <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8" onclick="location.reload()" title="刷新">
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
                                          <label class="layui-form-label">起止时间</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="时间选择" name="start" id="start" readonly>
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="时间选择" name="end" id="end" readonly>
                                          </div>
                                        </div>
                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                          <{if $displaceStatReport2Excel == 1 }>
                                          <button type="button" class="layui-btn layui-btn-danger" onclick="P2PStatistics2Excel()">导出</button>
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
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'add_time',
              title : '日期',
              fixed : 'left',
              width : 100
            },
            {
              field : 'user_number',
              title : '持有在途债权总人数',
                width : 155
            },
            {
              field : 'fdd_sign_user_number',
              title : '法大大签约置换人数',
              width : 155
            },{
                  field : 'confirm_user_number',
                  title : '用户点击确认置换人数',
                  width : 150
              },
            {
              field : 'other_user_number',
              title : '用户其他方式置换人数',
              width : 150
            },{
                  field : 'system_batch_user_number',
                  title : '系统批量操作人数',
                  width : 130
              },
            {
              field : 'total_wait_capital',
              title : '在途合计金额',
              width : 110
            } ,
            {
              field : 'not_wj_wait_capital',
              title : '在途金额（排除万峻）',
              width : 150
            },
            {
              field : 'wj_wait_capital',
              title : '万峻在途合计',
              width : 110
            },
              {
                  field : 'fdd_displace_amount',
                  title : '法大大签约置换在途金额',
                  width : 180
              } ,
              {
                  field : 'other_displace_amount',
                  title : '非法大大签约置换在途金额'
              }
          ]],
          url      : '/user/loan/DisplaceStatReport',
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
              start    : obj.field.start,
              end      : obj.field.end,
                purchase_user_id : obj.field.purchase_user_id
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)', function(obj){
          var data = obj.data;
          var layEvent = obj.event;
         

        });

      });

      function P2PStatistics2Excel()
      {
        var start    = $("#start").val();
        var end      = $("#end").val();
        if (start == '' && end == ''  ) {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/loan/DisplaceStatReport2Excel?start="+start+"&end="+end  , "_blank");
          });
        }
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
      </div > 
    </script>
</html>