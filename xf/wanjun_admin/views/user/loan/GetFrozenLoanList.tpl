<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>冻结债权列表</title>
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
                <a href="">冻结数据管理</a>
                <a>
                    <cite>冻结债权列表</cite></a>
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
                            <h2 class="layui-colla-title">条件筛选（实时筛选）<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" action="">
                                    <div class="layui-form-item">

                                        <div class="layui-inline">
                                          <label class="layui-form-label">所属平台</label>
                                          <div class="layui-input-inline">
                                            <select name="deal_type" id="deal_type" lay-search="">
                                              <option value="1">尊享</option>
                                              <option value="2">普惠</option>
                                              <option value="4">智多新</option>

                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">冻结批次号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="frozen_batch_number" id="frozen_batch_number" placeholder="请输入冻结批次号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">投资记录ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_load_id" id="deal_load_id" placeholder="请输入投资记录ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" id="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款编号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_id" id="deal_id" placeholder="请输入借款编号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款标题</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name" id="name" placeholder="请输入借款标题" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">项目ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="project_id" id="project_id" placeholder="请输入项目ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">项目名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="project_name" id="project_name" placeholder="请输入项目名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">交易所备案号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="jys_record_number" id="jys_record_number" placeholder="请输入交易所备案号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款方名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="company" id="company" placeholder="请输入借款方名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">融资经办机构名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="advisory_name" id="advisory_name" placeholder="请输入融资经办机构名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">融资担保机构名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="agency_name" id="agency_name" placeholder="请输入融资担保机构名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">用户类型</label>
                                          <div class="layui-input-inline">
                                            <select name="debt_type" id="debt_type" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">原债权人</option>
                                              <option value="2">新债权人</option>
                                            </select>
                                          </div>
                                        </div>
                                        
                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $LoanList2ExcelFrozen == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="LoanList2ExcelA()">导出</button>
                                            <{/if}>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!--div class="layui-card-body">
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">批量条件上传<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content">
                                <form class="layui-form" action="">
                                    <div class="layui-form-item">

                                      <div class="layui-inline">
                                        <label class="layui-form-label">查询类型</label>
                                        <div class="layui-input-inline">
                                          <select name="type" lay-search="">
                                            <option value="">请选择查询类型</option>
                                            <option value="1">上传用户ID</option>
                                            <option value="2">上传借款编号</option>
                                            <option value="3">上传借款标题</option>
                                            <option value="4">上传项目名称</option>
                                            <option value="5">上传交易所备案编号</option>
                                          </select>
                                        </div>
                                      </div>

                                      <div class="layui-inline">
                                        <label class="layui-form-label">当前查询条件</label>
                                        <div class="layui-input-inline">
                                          <input type="text" class="layui-input" id="condition_name" value="" readonly style="width: 400px">
                                        </div>
                                        <input type="hidden" name="condition_id" id="condition_id" value="">
                                      </div>

                                    </div>
                                    <div class="layui-form-item">
                                      <div class="layui-input-block">
                                        <button class="layui-btn" lay-submit="" lay-filter="sreach_a">上传文件</button>
                                        <button class="layui-btn" lay-submit="" lay-filter="sreach_b">立即搜索</button>
                                        <button type="button" class="layui-btn layui-btn-primary" onclick="reset_condition()">重置</button>
                                        <{if $LoanList2Excel == 1 }>
                                        <button type="button" class="layui-btn layui-btn-danger" onclick="LoanList2ExcelB()">导出</button>
                                        <{/if}>
                                      </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div-->
                <div class="layui-card-header">
                    <button class="layui-btn" onclick="xadmin.open('新增冻结','/user/loan/AddFrozenLoad',800,600)">
                        <i class="layui-icon"></i>新增冻结</button>
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
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'id',
              title : '投资记录ID',
              fixed : 'left',
              width : 100
            },{
                  field : 'frozen_batch_number',
                  title : '冻结批次号',
                  width : 150
              },{
                  field : 'frozen_time',
                  title : '冻结时间',
                  width : 146
              },{
                  field : 'frozen_remark',
                  title : '冻结备注',
                  width : 150
              },
            {
              field : 'deal_id',
              title : '借款编号',
              width : 120
            },
            {
              field : 'deal_name',
              title : '借款标题',
              width : 150
            },
            {
              field : 'project_name',
              title : '项目名称',
              width : 150
            },
            {
              field : 'jys_record_number',
              title : '交易所备案编号',
              width : 150
            },
            {
              field : 'user_id',
              title : '用户ID',
              width : 100
            },
            {
              field : 'real_name',
              title : '用户姓名',
              width : 100
            },
            {
              field : 'create_time',
              title : '投资时间',
              width : 150
            },
            {
              field : 'money',
              title : '投资金额',
              width : 120,
              align : 'right'
            },
            {
              field : 'project_product_class',
              title : '产品大类',
              width : 120
            },
            {
              field : 'deal_repay_time',
              title : '借款期限',
              width : 100
            },
            {
              field : 'deal_loantype',
              title : '还款方式',
              width : 150
            },
            {
              field : 'max_repay_time',
              title : '计划最大回款时间',
              width : 130
            },
            {
              field : 'deal_rate',
              title : '年化收益率',
              width : 100,
              align : 'right'
            },
            {
              field : 'deal_repay_start_time',
              title : '计息时间',
              width : 100
            },
            {
              field : 'wait_capital',
              title : '剩余待还本金',
              width : 120,
              align : 'right'
            },
            {
              field : 'wait_interest',
              title : '剩余待还利息',
              width : 120,
              align : 'right'
            },
            {
              field : 'deal_user_id',
              title : '借款方ID',
              width : 100
            },
            {
              field : 'deal_user_real_name',
              title : '借款方名称',
              width : 200
            },
            {
              field : 'deal_advisory_name',
              title : '融资经办机构',
              width : 200
            },
            {
              field : 'deal_agency_name',
              title : '融资担保机构',
              width : 200
            },
            {
              field : 'debt_type',
              title : '用户类型',
              width : 100
            },{
                  field : 'frozen_op_name',
                  title : '操作人',
                  width : 120
              },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 120
            }
          ]],
          url      : '/user/Loan/GetFrozenLoanList',
          method   : 'post',
          where    : {deal_type : 1},
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
              deal_type         : obj.field.deal_type,
                frozen_batch_number: obj.field.frozen_batch_number,
              deal_load_id      : obj.field.deal_load_id,
              user_id           : obj.field.user_id,
              deal_id           : obj.field.deal_id,
              name              : obj.field.name,
              project_id        : obj.field.project_id,
              project_name      : obj.field.project_name,
              jys_record_number : obj.field.jys_record_number,
              company           : obj.field.company,
              advisory_name     : obj.field.advisory_name,
              agency_name       : obj.field.agency_name,
              debt_type         : obj.field.debt_type,
              condition_id      : ''
            },
            page:{curr:1}
          });
          return false;
        });

        form.on('submit(sreach_a)', function(obj){

          if (obj.field.type == 0) {
            layer.msg('请选择查询类型');
          } else if (obj.field.type == 1) {
            xadmin.open('通过上传用户ID查询','/user/Loan/addLoanListCondition?type=1');
          } else if (obj.field.type == 2) {
            xadmin.open('通过上传借款编号查询','/user/Loan/addLoanListCondition?type=2');
          } else if (obj.field.type == 3) {
            xadmin.open('通过上传借款标题查询','/user/Loan/addLoanListCondition?type=3');
          } else if (obj.field.type == 4) {
            xadmin.open('通过上传项目名称查询','/user/Loan/addLoanListCondition?type=4');
          } else if (obj.field.type == 5) {
            xadmin.open('通过上传交易所备案编号查询','/user/Loan/addLoanListCondition?type=5');
          }
          return false;
        });

        form.on('submit(sreach_b)', function(obj){

          if (obj.field.condition_id == '') {
            layer.msg('缺少查询条件，请先上传文件！');
          } else {
            table.reload('list', {
              where    :
              {
                deal_type         : '',
                deal_load_id      : '',
                  frozen_batch_number      : '',
                user_id           : '',
                deal_id           : '',
                name              : '',
                project_id        : '',
                project_name      : '',
                jys_record_number : '',
                company           : '',
                advisory_name     : '',
                agency_name       : '',
                debt_type         : '',
                condition_id      : obj.field.condition_id
              },
              page:{curr:1}
            });
          }
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'edit')
          {
              member_join(data.id,1,data.deal_type);
          }
        });

      });

      function member_join(id,status,deal_type){
        layer.confirm('确认要关闭冻结吗？',function(index){
          $.ajax({
              url: "/user/Loan/StopFrozenLoan?loan_id="+id+"&deal_type="+deal_type ,
              type:"GET",
              success: function (res) {
                  if(res.code == 0){
                      layer.msg('关闭冻结成功!',{time:1000,icon:1},function(){
                          location.reload();
                      });
                  }else{
                      layer.alert('关闭冻结失败!',function(){
                          location.reload();
                      });
                  }
              }
          })
        });
      }

      function reset_condition() {
        $("#condition_id").val('');
        $("#condition_name").val('');
      }

      function show_condition(id , name) {
        $("#condition_id").val(id);
        $("#condition_name").val(name);
      }

      function LoanList2ExcelA()
      {
        var deal_type         = $("#deal_type").val();
        var deal_load_id      = $("#deal_load_id").val();
      var frozen_batch_number      = $("#frozen_batch_number").val();
        var user_id           = $("#user_id").val();
        var deal_id           = $("#deal_id").val();
        var name              = $("#name").val();
        var project_id        = $("#project_id").val();
        var project_name      = $("#project_name").val();
        var jys_record_number = $("#jys_record_number").val();
        var company           = $("#company").val();
        var advisory_name     = $("#advisory_name").val();
        var agency_name       = $("#agency_name").val();
        var debt_type         = $("#debt_type").val();
        if (deal_type != 1 && deal_type != 2 && deal_type != 4) {
          layer.msg('请正确选择所属平台');
        } else if (frozen_batch_number == '' && deal_load_id == '' && user_id == '' && deal_id == '' && name == '' && project_id == '' && project_name == '' && jys_record_number == '' && company == '' && advisory_name == '' && agency_name == '' && debt_type == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/LoanList2ExcelFrozen?deal_type="+deal_type+"&deal_load_id="+deal_load_id+"&frozen_batch_number="+frozen_batch_number+"&user_id="+user_id+"&deal_id="+deal_id+"&name="+name+"&project_id="+project_id+"&project_name="+project_name+"&jys_record_number="+jys_record_number+"&company="+company+"&advisory_name="+advisory_name+"&agency_name="+agency_name+"&debt_type="+debt_type , "_blank");
          });
        }
      }

      function LoanList2ExcelB()
      {
        var condition_id = $("#condition_id").val();
        if (condition_id == '') {
          layer.msg('缺少查询条件，请先上传文件！');
        } else {
          layer.confirm('确认要根据当前上传的批量条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/LoanList2Excel?condition_id="+condition_id , "_blank");
            reset_condition();
          });
        }
      }
    </script>
    <script type="text/html" id="operate">
      <button class="layui-btn layui-btn-xs layui-btn-danger" title="关闭冻结" lay-event="edit">关闭冻结</button>
    </script>
</html>