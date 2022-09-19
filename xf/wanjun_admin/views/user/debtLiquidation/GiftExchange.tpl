<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>礼包兑换统计</title>
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
                    <cite>礼包兑换统计</cite></a>
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
                  <table class="layui-table layui-form" lay-filter="list" id="list">
                  </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    
</script>
    <script>
      layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;
        laydate = layui.laydate;

        table.render({
          elem           : '#list',
          toolbar        : '#toolbar',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 40,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'name',
              title : '礼包名称',
              fixed : 'left',
              align : 'right',
              width : 100
            },
            {
              field : 'exchange_min_max',
              title : '兑换区间',
              align : 'right',
              width : 150
            },
            {
              field : 'plan_liquidation_user',
              title : '计划下车人数',
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
              field : 'plan_debt_total',
              title : '计划回收总债权合计',
              align : 'right',
              width : 150
            },
            {
              field : 'debt_total',
              title : '累计回收总债权合计',
              align : 'right',
              width : 150
            },
            {
              field : 'plan_yr_debt_total',
              title : '计划回收悠融债权合计',
              align : 'right',
              width : 150
            },
            {
              field : 'yr_debt_total',
              title : '累计回收悠融债权合计',
              align : 'right',
              width : 150
            },
            {
              field : 'avg_liquidation_cost',
              title : '礼包成本',
              align : 'right',
              width : 100
            },
            {
              field : 'plan_liquidation_cost',
              title : '计划化债成本',
              align : 'right',
              width : 120
            },
            {
              field : 'liquidation_cost',
              title : '累计化债成本',
              align : 'right',
              width : 120
            },
            // {
            //   field : 'avg_proportion',
            //   title : '平均占比(%)',
            //   align : 'right',
            //   width : 100
            // },
            // {
            //   field : 'avg_debt',
            //   title : '人均债权数',
            //   align : 'right',
            //   width : 100
            // },
            {
              field : 'kpi_1_min_max',
              title : 'KPI记数分区1区间',
              align : 'right',
              width : 150
            },
            {
              field : 'kpi_1_plan_user',
              title : 'KPI记数分区1计划下车人数',
              align : 'right',
              width : 180
            },
            {
              field : 'kpi_2_min_max',
              title : 'KPI记数分区2区间',
              align : 'right',
              width : 150
            },
            {
              field : 'kpi_2_plan_user',
              title : 'KPI记数分区2计划下车人数',
              align : 'right',
              width : 180
            },
            {
              field : 'kpi_3_min_max',
              title : 'KPI记数分区3区间',
              align : 'right',
              width : 150
            },
            {
              field : 'kpi_3_plan_user',
              title : 'KPI记数分区3计划下车人数',
              align : 'right',
              width : 180
            }
          ]],
          url      : '/user/DebtLiquidation/GiftExchange',
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

      });
    </script>
    <script type="text/html" id="operate">
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" > 
      </div > 
    </script>
</html>