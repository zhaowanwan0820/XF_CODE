<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>待审核列表</title>
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
                <a href="">尊享还款管理</a>
                <a>
                    <cite>待审核列表</cite></a>
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
                                          <label class="layui-form-label">还款形式</label>
                                          <div class="layui-input-inline">
                                            <select name="repayment" lay-search="">
                                              <option value="">请选择还款形式</option>
                                              <option value="1">线下</option>
                                              <option value="2">线上</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">产品大类</label>
                                          <div class="layui-input-inline">
                                            <select name="product_class" lay-search="">
                                              <option value="">请选择产品大类</option>
                                              <option value="嘉汇">嘉汇</option>
                                              <option value="盈嘉">盈嘉</option>
                                              <option value="盈益">盈益</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_id" placeholder="请输入借款ID" autocomplete="off" class="layui-input" value="">
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
                                          <label class="layui-form-label">状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" lay-search="">
                                              <option value="">请选择状态</option>
                                              <option value="1">待审核</option>
                                              <option value="2">审核通过</option>
                                              <option value="3">审核未通过</option>
                                              <option value="4">还款成功</option>
                                              <option value="5">还款失败</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">还款资金类型</label>
                                          <div class="layui-input-inline">
                                            <select name="repay_type" lay-search="">
                                              <option value="">请选择还款资金类型</option>
                                              <option value="1">本金</option>
                                              <option value="2">利息</option>
                                              <option value="3">本息全还</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">还款类型</label>
                                          <div class="layui-input-inline">
                                            <select name="type" lay-search="">
                                              <option value="">请选择还款类型</option>
                                              <option value="1">常规还款</option>
                                              <option value="2">特殊还款</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">正常还款日期</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="请选择正常还款日期" name="start_a" id="start_a" readonly value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">计划还款日期</label>
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

        laydate.render({
            elem: '#start_a'
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
              title : '债转ID',
              fixed : 'left',
              width : 150
            },
            {
              field : 'repayment_form',
              title : '还款形式',
              width : 150
            },
            {
              field : 'project_product_class',
              title : '产品大类',
              width : 150
            },
            {
              field : 'deal_id',
              title : '借款ID',
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
              field : 'normal_time_str',
              title : '正常还款日期',
              width : 150
            },
            {
              field : 'repayment_total',
              title : '还款金额',
              width : 200
            },
            {
              field : 'loan_repay_type',
              title : '还款资金类型',
              width : 150
            },
            {
              field : 'repay_type_name',
              title : '还款类型',
              width : 150
            },
            {
              field : 'plan_time',
              title : '计划还款时间',
              width : 150
            },
            {
              field : 'loan_user_id',
              title : '出借人ID',
              width : 200
            },
            {
              field : 'deal_loan_id',
              title : '投资记录ID',
              width : 200
            },
            {
              field : 'evidence_pic',
              title : '还款凭证',
              width : 150
            },
            {
              field : 'attachments_url',
              title : '附件',
              width : 150
            },
            {
              field : 'status_name',
              title : '状态',
              width : 150
            },
            {
              field : 'task_remark',
              title : '备注',
              width : 200
            },
            {
              field : 'task_success_time',
              title : '完成时间',
              width : 200
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 350
            },
          ]],
          url      : '/user/Debt/ExamineLoanRepayList',
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
              repayment      : obj.field.repayment,
              product_class  : obj.field.product_class,
              deal_id        : obj.field.deal_id,
              deal_name      : obj.field.deal_name,
              approve_number : obj.field.approve_number,
              project_name   : obj.field.project_name,
              advisory_name  : obj.field.advisory_name,
              real_name      : obj.field.real_name,
              status         : obj.field.status,
              repay_type     : obj.field.repay_type,
              type           : obj.field.type,
              t_mobile       : obj.field.t_mobile,
              start_a        : obj.field.start_a,
              start          : obj.field.start,
              end            : obj.field.end
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'edit') {
            if (data.repay_type == 1) {
              xadmin.open('编辑常规还款计划' , '/user/Debt/EditExamineLoanRepay?id='+data.id);
            } else if (data.repay_type == 2) {
              xadmin.open('编辑特殊还款计划' , '/user/Debt/EditSpecialLoanRepay?id='+data.id);
            }
          } else if (layEvent === 'pass') {
            set_ok(data.id);
          } else if (layEvent === 'reject') {
            set_not_ok(data.id);
          }else if (layEvent === 'revoke_status') {
              set_revoke_ok(data.id);
          }
        });

      });
      function set_revoke_ok(id){
          layer.confirm('确认要撤销吗？', {content:'<div><label for="task_remark">请填写撤销原因：</label><div><textarea placeholder="100字以内" id="task_remark" class="layui-textarea"></textarea></div></div>'} ,
                  function(index) {
                      var task_remark = $("#task_remark").val();
                      $.ajax({
                          url:'/user/Loan/Setrevoke',
                          type:'post',
                          data:{
                              'id':id,
                              'task_remark':task_remark
                          },
                          dataType:'json',
                          success:function(res) {
                              if (res['code'] === 0) {
                                  layer.msg(res['info'] , {time:1000,icon:1} , function(){
                                      location.reload();
                                  });
                              } else {
                                  layer.alert(res['info']);
                              }
                          }
                      });
                  });
      }
      function set_ok(id) {
        layer.confirm('确认要通过吗？',
        function(index) {
          $.ajax({
            url:'/user/Debt/PassExamineLoanRepay',
            type:'post',
            data:{
              'id':id
            },
            dataType:'json',
            success:function(res) {
              if (res['code'] === 0) {
                layer.msg(res['info'] , {time:1000,icon:1} , function(){
                    location.reload();
                });
              } else {
                layer.alert(res['info']);
              }
            }
          });
        });
      }

      function set_not_ok(id) {
        layer.confirm('确认要拒绝吗？', {content:'<div><label for="task_remark">请填写拒绝原因：</label><div><textarea placeholder="100字以内" id="task_remark" class="layui-textarea"></textarea></div></div>'} ,
        function(index) {
          var task_remark = $("#task_remark").val();
          if (task_remark == '') {
            layer.alert('请填写拒绝原因');
          } else {
            $.ajax({
              url:'/user/Debt/RejectExamineLoanRepay',
              type:'post',
              data:{
                'id':id,
                'task_remark':task_remark
              },
              dataType:'json',
              success:function(res) {
                if (res['code'] === 0) {
                  layer.msg(res['info'] , {time:1000,icon:1} , function(){
                    location.reload();
                  });
                } else {
                  layer.alert(res['info']);
                }
              }
            });
          }
        });
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" > 
      </div > 
    </script>
    <script type="text/html" id="operate">
      {{# if(d.edit_status == 2){ }}
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="编辑" lay-event="edit"><i class="layui-icon">&#xe642;</i>编辑</button>
      {{# }else if (d.edit_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-disabled" title="编辑"><i class="layui-icon">&#xe642;</i>编辑</button>
      {{# } }}
      {{# if(d.pass_status == 2){ }}
      <button class="layui-btn layui-btn-xs" title="通过" lay-event="pass"><i class="layui-icon">&#xe605;</i>通过</button>
      {{# }else if (d.pass_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-disabled" title="通过"><i class="layui-icon">&#xe605;</i>通过</button>
      {{# } }}
      {{# if(d.reject_status == 2){ }}
      <button class="layui-btn layui-btn-xs layui-btn-danger" title="拒绝" lay-event="reject"><i class="layui-icon">&#x1006;</i>拒绝</button>
      {{# }else if (d.reject_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-disabled" title="拒绝"><i class="layui-icon">&#x1006;</i>拒绝</button>
      {{# } }}
      {{# if(d.status == 0){ }}
      <button class="layui-btn layui-btn-xs layui-btn-warm" title="撤销" lay-event="revoke_status"><i class="layui-icon">&#x1006;</i>撤销</button>
      {{# } }}
    </script>
</html>