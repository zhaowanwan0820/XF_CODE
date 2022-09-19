<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>下车用户综合统计(日统计)</title>
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
                <a href="">下车专栏</a>
                <a>
                    <cite>下车用户综合统计(日统计)</cite></a>
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
                                          <label class="layui-form-label">统计时间</label>
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
                                          <{if $DebtLiquidationStatistics2Excel == 1 }>
                                          <button type="button" class="layui-btn layui-btn-danger" onclick="DebtLiquidationStatistics2Excel()">导出</button>
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
            elem  : '#start',
            value : '<{$now_time}>'
        });

        laydate.render({
            elem  : '#end',
            value : '<{$now_time}>'
        });

        table.render({
          elem           : '#list',
          toolbar        : '<div><i class="iconfont" style="color:orange;">&#xe6b6;</i> 默认展示昨日统计数据</div>',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 40,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'num',
              title : '序号',
              align : 'right',
              width : 50
            },
            {
              field : 'add_time',
              title : '统计时间',
              align : 'right',
              width : 100
            },
            {
              field : 'name',
              title : '礼包名称',
              align : 'right',
              width : 85
            },
              {
                  field : 'exchange_min_max',
                  title : '兑换区间',
                  align : 'right',
                  width : 130
              },
            {
              field : 'plan_liquidation_user',
              title : '计划下车人数',
              align : 'right',
              width : 110
            },
            {
              field : 'liquidation_user_day',
              title : '当日下车人数',
              align : 'right',
              width : 110
            },
            {
              field : 'liquidation_user',
              title : '累计下车人数',
              align : 'right',
              width : 110
            },
            {
              field : 'liquidation_user_day_percent',
              title : '当日下车人数占比',
              align : 'right',
              width : 150
            },
            {
              field : 'liquidation_user_percent',
              title : '累计下车人数占比',
              align : 'right',
              width : 150
            },
            {
              field : 'plan_debt_total',
              title : '计划回收总债权',
              align : 'right',
              width : 120
            },
            {
              field : 'debt_total_day',
              title : '当日回收总债权',
              align : 'right',
              width : 120
            },
            {
              field : 'debt_total',
              title : '累计回收总债权',
              align : 'right',
              width : 120
            },
            {
              field : 'debt_total_day_percent',
              title : '当日回收总债权占比',
              align : 'right',
              width : 160
            },
            {
              field : 'debt_total_percent',
              title : '累计回收总债权占比',
              align : 'right',
              width : 160
            },
            {
              field : 'plan_yr_debt_total',
              title : '计划回收悠融债权',
              align : 'right',
              width : 130
            },
            {
              field : 'yr_debt_total_day',
              title : '当日回收悠融债权',
              align : 'right',
              width : 130
            },
            {
              field : 'yr_debt_total',
              title : '累计回收悠融债权',
              align : 'right',
              width : 130
            },
            {
              field : 'yr_debt_total_day_percent',
              title : '当日回收悠融债权占比',
              align : 'right',
              width : 170
            },
            {
              field : 'yr_debt_total_percent',
              title : '累计回收悠融债权占比',
              align : 'right',
              width : 170
            },
            {
              field : 'plan_liquidation_cost',
              title : '计划化债成本',
              align : 'right',
              width : 110
            },
            {
              field : 'liquidation_cost_day',
              title : '当日化债成本',
              align : 'right',
              width : 110
            },
            {
              field : 'liquidation_cost',
              title : '累计化债成本',
              align : 'right',
              width : 110
            },
            {
              field : 'liquidation_cost_day_percent',
              title : '当日化债成本占比',
              align : 'right',
              width : 150
            },
            {
              field : 'liquidation_cost_percent',
              title : '累计化债成本占比',
              align : 'right',
              width : 150
            },
            {
              field : 'liquidation_cost_fluctuation_day',
              title : '当日化债成本增减',
              align : 'right',
              width : 130
            },
            {
              field : 'liquidation_cost_fluctuation',
              title : '累计化债成本增减',
              align : 'right',
              width : 130
            }
          ]],
          url      : '/user/DebtLiquidation/DebtLiquidationStatistics',
          method   : 'post',
          where    : {start : '<{$now_time}>' , end : '<{$now_time}>'},
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
              start : obj.field.start,
              end   : obj.field.end
            },
            page:{curr:1}
          });
          return false;
        });

      });

      function DebtLiquidationStatistics2Excel()
      {
        var start     = $("#start").val();
        var end       = $("#end").val();
        if (start == '' && end == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/DebtLiquidation/DebtLiquidationStatistics2Excel?start="+start+"&end="+end , "_blank");
          });
        }
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
      </div > 
    </script>
</html>