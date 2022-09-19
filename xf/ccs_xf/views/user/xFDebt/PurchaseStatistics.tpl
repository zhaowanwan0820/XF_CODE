<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>定向收购-数据统计</title>
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
                    <cite>定向收购-数据统计</cite></a>
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
                                          <label class="layui-form-label">日期</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="时间选择" name="start" id="start" readonly>
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="时间选择" name="end" id="end" readonly>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">受让方</label>
                                          <div class="layui-input-inline">
                                              <select name="purchase_user_id" id="purchase_user_id" lay-verify="required" lay-search="">
                                                  <option value="ALL" <{if $user eq 'ALL' }>selected<{/if}>>全部</option>
                                                  <option value="0" >合计</option>
                                                  <{foreach $user_arr as $k => $v}>
                                                  <option value="<{$v['id']}>" <{if $user eq $v['id'] }>selected<{/if}>><{$v['name']}></option>
                                                  <{/foreach}>
                                              </select>
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
              title : '日期',
              fixed : 'left',
              width : 100
            },
            {
              field : 'purchase_name',
              title : '受让方',
              width : 290
            },
            {
              field : 'day_capital_total',
              title : '当日收购债权(在途)',
              width : 135
            },{
                  field : 'day_rw_total',
                  title : '当日收购债权(充提差)',
                  width : 150
              },
            {
              field : 'day_user_number',
              title : '当日收购人数',
              width : 115
            },{
                  field : 'day_money_total',
                  title : '当日收购支付金额',
                  width : 130
              },
            {
              field : 'day_debt_money_ratio',
              title : '当日现金/债权(在途)兑付比例',
              width : 200
            },
              {
                  field : 'capital_total',
                  title : '截止当日收购总债权(在途)',
                  width : 180
              },
            {
              field : 'rw_total',
              title : '截止当日收购总债权(充提差)',
              width : 200
            },
            {
              field : 'user_number',
              title : '截止当日收购总人数',
              width : 150
            },
            {
              field : 'money_total',
              title : '截止当日总收购支付金额',
              width : 180
            },
            {
              field : 'debt_money_ratio',
              title : '截至当日总现金/债权(在途)兑付比例',
              width : 240
            }
          ]],
          url      : '/user/XFDebt/PurchaseStatistics',
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
        var purchase_user_id = $("#purchase_user_id").val();
        if (start == '' && end == '' && purchase_user_id == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/XFDebt/PurchaseStatistics2Excel?start="+start+"&end="+end+"&purchase_user_id="+purchase_user_id , "_blank");
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