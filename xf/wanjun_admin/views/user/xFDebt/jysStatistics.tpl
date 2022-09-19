<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>交易所在途数据统计表</title>
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
                <a href="">交易所业务数据</a>
                <a>
                    <cite>交易所在途数据统计表</cite></a>
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
                                          <{if $P2PStatistics2Excel == 1 }>
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
          toolbar        : '<div><i class="iconfont" style="color:orange;">&#xe6b6;</i> 统计截至当日零时全量数据。</div>',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'add_time',
              title : '统计时间',
              fixed : 'left',
              width : 150
            },
            {
              field : 'distinct_user_total',
              title : '去重后总人数',
              width : 180
            },
            {
              field : 'capital_total',
              title : '在途本金总金额',
              width : 180
            },
            {
              field : 'interest_total',
              title : '在途利息总金额',
              width : 180
            },
            {
              field : 'shop_debt_money_total',
              title : '商城累计化债金额',
              width : 180
            },
            {
              field : 'shop_debt_user_total',
              title : '商城累计化债人数',
              width : 180
            },
            {
              field : 'cash_repayment_total',
              title : '现金累计兑付金额',
              width : 180
            },
            {
              field : 'offline_debt_money_total',
              title : '线下咨询权益化债总金额',
              width : 180
            },
            {
              field : 'shop_debt_money',
              title : '当日商城化债金额',
              width : 180
            },
            {
              field : 'cash_repayment',
              title : '当日现金兑付金额',
              width : 180
            },
            {
              field : 'offline_debt_money',
              title : '当日线下咨询权益化债金额',
              width : 180
            },
            {
              field : 'shop_debt_user',
              title : '当日商城兑付人数',
              width : 180
            },
            {
              field : 'cash_repayment_user',
              title : '当日现金兑付人数',
              width : 180
            },
            {
              field : 'offline_debt_user',
              title : '当日线下咨询权益化债人数',
              width : 180
            },
            {
              field : 'repayment_capital_total',
              title : '累计兑付本金',
              width : 180
            },
            {
              field : 'repayment_interest_total',
              title : '累计兑付利息',
              width : 180
            },
            {
              field : 'repayment_capital',
              title : '当日兑付本金',
              width : 180
            },
            {
              field : 'repayment_interest',
              title : '当日兑付利息',
              width : 180
            },
            {
              field : 'repayment_user',
              title : '当日兑付出借人数',
              width : 180
            },
            {
              field : 'repayment_clear_user',
              title : '当日出清出借人数',
              width : 180
            },
            {
              field : 'repayment_clear_user_total',
              title : '累计出清出借人数',
              width : 180
            },
            {
              title   : '当日兑付借款企业',
              width   : 180,
              toolbar : '#company',
            },
            {
              title   : '当日兑付担保企业',
              width   : 180,
              toolbar : '#guarantee_company',
            },
            {
              title   : '当日兑付资产合作机构',
              width   : 180,
              toolbar : '#cooperation_company',
            }
          ]],
          url      : '/user/XFDebt/jysStatistics',
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
              platform : obj.field.platform
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)', function(obj){
          var data = obj.data;
          var layEvent = obj.event;
         
          if (layEvent === 'company') {
            xadmin.open('查看当日兑付借款企业信息详情' , '/user/XFDebt/P2PStatisticsCompany?id='+data.id);
          } else if (layEvent === 'guarantee_company') {
            xadmin.open('查看当日兑付担保企业信息详情' , '/user/XFDebt/P2PStatisticsGuaranteeCompany?id='+data.id);
          } else if (layEvent === 'cooperation_company') {
            xadmin.open('查看当日兑付资产合作机构信息详情' , '/user/XFDebt/P2PStatisticsCooperationCompany?id='+data.id);
          }
        });

      });

      function P2PStatistics2Excel()
      {
        var start    = $("#start").val();
        var end      = $("#end").val();
        var platform = $("#platform").val();
        if (start == '' && end == '' && platform == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/XFDebt/jysStatistics2Excel?start="+start+"&end="+end+"&platform="+platform , "_blank");
          });
        }
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
      </div > 
    </script>
    <script type="text/html" id="company">
      <button class="layui-btn layui-btn-xs" title="查看当日兑付借款企业信息详情" lay-event="company">当日兑付借款企业</button>
    </script>
    <script type="text/html" id="guarantee_company">
      <button class="layui-btn layui-btn-xs" title="查看当日兑付担保企业信息详情" lay-event="guarantee_company">当日兑付担保企业</button>
    </script>
    <script type="text/html" id="cooperation_company">
      <button class="layui-btn layui-btn-xs" title="查看当日兑付资产合作机构信息详情" lay-event="cooperation_company">当日兑付资产合作机构</button>
    </script>
</html>