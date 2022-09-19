<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>待还款列表</title>
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
                <a href="">首页</a>
                <a href="">普惠还款管理</a>
                <a>
                    <cite>待还款列表</cite></a>
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
                                          <label class="layui-form-label">产品大类</label>
                                          <div class="layui-input-inline">
                                            <select name="product_class" lay-search="">
                                              <option value="">请选择产品大类</option>
                                              <option value="个体经营贷">个体经营贷</option>
                                              <option value="企业经营贷">企业经营贷</option>
                                              <option value="供应链">供应链</option>
                                              <option value="消费贷">消费贷</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款编号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_id" placeholder="请输入借款编号" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款标题</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_name" placeholder="请输入借款标题" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">交易所备案号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="approve_number" placeholder="请输入交易所备案号" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">项目名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="project_name" placeholder="请输入项目名称" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">融资经办机构</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="advisory_name" placeholder="请输入融资经办机构" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款人姓名</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="real_name" placeholder="请输入借款人姓名" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">还款资金类型</label>
                                          <div class="layui-input-inline">
                                            <select name="repay_type" lay-search="">
                                              <option value="">请选择还款资金类型</option>
                                              <option value="1">本金</option>
                                              <option value="2">利息</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">还款状态</label>
                                          <div class="layui-input-inline">
                                            <select name="repay_status" lay-search="">
                                              <option value="">请选择还款状态</option>
                                              <option value="1">待还</option>
                                              <option value="2">已还</option>
                                            </select>
                                          </div>
                                        </div>
                                    
                                        <div class="layui-inline">
                                          <label class="layui-form-label">正常还款日期</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="请选择开始日期" name="start" id="start" readonly value="">
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="请选择截止日期" name="end" id="end" readonly  value="">
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
              field : 'id',
              title : 'ID',
              fixed : 'left',
              width : 150
            },
            {
              field : 'project_product_class',
              title : '产品大类',
              width : 150
            },
            {
              field : 'deal_id',
              title : '借款编号',
              width : 150
            },
            {
              field : 'deal_name',
              title : '借款标题',
              width : 200
            },
            {
              field : 'jys_record_number',
              title : '交易所备案编号',
              width : 200
            },
            {
              field : 'project_name',
              title : '项目名称',
              width : 200
            },
            {
              field : 'borrow_amount',
              title : '借款金额',
              width : 200
            },
            {
              field : 'deal_rate',
              title : '年化借款利率',
              width : 150
            },
            {
              field : 'deal_repay_time',
              title : '借款期限',
              width : 150
            },
            {
              field : 'deal_loantype',
              title : '还款方式',
              width : 150
            },
            {
              field : 'deal_repay_start_time',
              title : '计息日',
              width : 150
            },
            {
              field : 'deal_advisory_name',
              title : '融资经办机构',
              width : 300
            },
            {
              field : 'deal_user_real_name',
              title : '借款人姓名',
              width : 300
            },
            {
              field : 'loan_repay_time',
              title : '正常还款日期',
              width : 150
            },
            {
              field : 'repay_amount',
              title : '还款总额',
              width : 200
            },
            {
              field : 'repaid_amount',
              title : '已还金额',
              width : 200
            },
            {
              field : 'real_amount',
              title : '待还金额',
              width : 200
            },
            {
              field : 'repay_type',
              title : '还款资金类型',
              width : 150
            },
            {
              field : 'repay_status_name',
              title : '还款状态',
              width : 150
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 250
            },
          ]],
          url      : '/user/PartialRepay/LoanRepayList',
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
            where    :
            {
              product_class  : obj.field.product_class,
              deal_id        : obj.field.deal_id,
              deal_name      : obj.field.deal_name,
              approve_number : obj.field.approve_number,
              project_name   : obj.field.project_name,
              advisory_name  : obj.field.advisory_name,
              real_name      : obj.field.real_name,
              repay_type     : obj.field.repay_type,
              repay_status   : obj.field.repay_status,
              start          : obj.field.start,
              end            : obj.field.end
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'add')
          {
            xadmin.open('添加线下还款' , '/user/PartialRepay/StartLoanRepay?id='+data.id);
          } else if (layEvent === 'daochu') {
            dao_chu(data.id);
          }
        });

      });

      function dao_chu(id) {
        layer.confirm('确认要导出此数据的出借人信息吗？',
        function(index) {
          layer.close(index);
          window.open("/user/PartialRepay/LoanRepayListExcel?stat_repay_id="+id , "_blank");
        });
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" > 
      </div > 
    </script>
    <script type="text/html" id="operate">
      {{# if(d.repay_status == 0 && d.add_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="添加线下还款" lay-event="add"><i class="layui-icon">&#xe654;</i>添加线下还款</button>
      <button class="layui-btn layui-btn-xs" title="导出出借人信息" lay-event="daochu"><i class="layui-icon">&#xe60a;</i>导出</button>
      {{# }else if(d.repay_status == 1 && d.daochu_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-disabled" title="添加线下还款"><i class="layui-icon">&#xe654;</i>添加线下还款</button>
      <button class="layui-btn layui-btn-xs layui-btn-disabled" title="导出出借人信息"><i class="layui-icon">&#xe60a;</i>导出</button>
      {{# } }}
    </script>
</html>