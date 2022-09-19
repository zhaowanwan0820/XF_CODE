<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>下车用户管理</title>
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
                <a href="">商城管理</a>
                <a>
                    <cite>下车用户管理</cite></a>
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
                            <h2 class="layui-colla-title">条件筛选（实时筛选）<i class="layui-icon layui-colla-icon"></i></h2>
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
                                            <label class="layui-form-label">导入批次号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="add_batch_number" id="add_batch_number" placeholder="请输入导入批次号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">导入用户</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="add_user_name" id="add_user_name" placeholder="请输入导入用户" autocomplete="off" class="layui-input">
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

                <div class="layui-card-header">
                    <button class="layui-btn" onclick="xadmin.open('导入用户','/user/Loan/AddXcheUser',800,600)">
                        <i class="layui-icon"></i>导入用户</button>
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
                  title : 'ID',
                  width : 100
              },{
              field : 'user_id',
              title : '用户ID',
              width : 150
            },{
                  field : 'add_batch_number',
                  title : '导入批次号',
                  width : 200
              },
            {
              field : 'add_user_name',
              title : '导入人名称',
              width : 250
            },{
                  field : 'add_time',
                  title : '导入时间',
                  width : 250
              },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 200
            }
          ]],
          url      : '/user/Loan/ShopXcheUserList',
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
                add_user_name         : obj.field.add_user_name,
              user_id           : obj.field.user_id,
                add_batch_number : obj.field.add_batch_number,
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
                add_user_name         : '',
                user_id           : '',
                  add_batch_number : '',
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
              member_join(data.user_id);
          }
        });

      });

      function member_join(id){
        layer.confirm('确认要取消此用户吗？',function(index){
          $.ajax({
              url: "/user/Loan/StopXcheUser?user_id="+id ,
              type:"GET",
              success: function (res) {
                  if(res.code == 0){
                      layer.msg('取消用户成功!',{time:1000,icon:1},function(){
                          location.reload();
                      });
                  }else{
                      layer.alert('取消用户失败!',function(){
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
        var add_user_name         = $("#add_user_name").val();
        var user_id           = $("#user_id").val();
          var add_batch_number           = $("#add_batch_number").val();

          if (add_batch_number == '' && add_user_name == '' && user_id == ''  ) {
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
      <button class="layui-btn layui-btn-xs layui-btn-danger" title="取消用户" lay-event="edit">取消用户</button>
    </script>
</html>