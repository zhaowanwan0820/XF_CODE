<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>在途投资明细</title>
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
                    <cite>在途投资明细</cite></a>
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
                                            <label class="layui-form-label">产品编号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_id" id="deal_id" placeholder="请输入产品编号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">产品名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name" id="name" placeholder="请输入产品名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">发行人/融资方简称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="company" id="company" placeholder="请输入发行人/融资方简称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">审核状态</label>
                                          <div class="layui-input-inline">
                                            <select name="audit_status" id="audit_status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="no">未上传</option>
                                              <option value="1">待审核</option>
                                              <option value="2">审核通过</option>
                                              <option value="3">审核未通过</option>
                                            </select>
                                          </div>
                                        </div>
                                        
                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $LoanList2Excel == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="LoanList2ExcelA()">导出</button>
                                            <{/if}>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-card-body">
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
                                            <option value="2">上传产品编号</option>
                                            <option value="3">上传产品名称</option>
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
            },
            {
              field : 'deal_id',
              title : '产品编号',
              width : 120
            },
            {
              field : 'deal_name',
              title : '产品名称',
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
              field : 'deal_repay_time',
              title : '期限',
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
              field : 'deal_user_real_name',
              title : '发行人/融资方简称',
              width : 200
            },
            {
              field : 'audit_status_name',
              title : '审核状态',
              fixed : 'right',
              width : 100
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 170
            }
          ]],
          url      : '/user/JYSDebt/LoanList',
          method   : 'post',
          where    : {deal_type : 5},
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
              deal_load_id      : obj.field.deal_load_id,
              user_id           : obj.field.user_id,
              deal_id           : obj.field.deal_id,
              name              : obj.field.name,
              company           : obj.field.company,
              audit_status      : obj.field.audit_status,
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
            xadmin.open('通过上传用户ID查询','/user/JYSDebt/addLoanListCondition?deal_type=5&type=1');
          } else if (obj.field.type == 2) {
            xadmin.open('通过上传产品编号查询','/user/JYSDebt/addLoanListCondition?deal_type=5&type=2');
          } else if (obj.field.type == 3) {
            xadmin.open('通过上传产品名称查询','/user/JYSDebt/addLoanListCondition?deal_type=5&type=3');
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
                deal_load_id      : '',
                user_id           : '',
                deal_id           : '',
                name              : '',
                jys_record_number : '',
                company           : '',
                audit_status      : '',
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
            if (data.black_status == 1) {
              xadmin.open('加入黑名单','/user/JYSDebt/JYSBlackEditAdd?loan_id='+data.id+'&deal_type='+data.deal_type+'&status=2');
            } else if (data.black_status == 2) {
              member_join(data.id,1,data.deal_type);
            }
          } else if (layEvent === 'audit') {
            xadmin.open('审核','/user/JYSDebt/JYSDealLoadAudit?id='+data.audit_id);
          } else if (layEvent === 'info') {
            xadmin.open('详情','/user/JYSDebt/JYSDealLoadInfo?id='+data.audit_id);
          }
        });

      });

      function member_join(id,status,deal_type){
        if(status == 2){
            str = '加入黑名单';
        }else{
            str = '取消黑名单';
        }
        layer.confirm('确认要'+str+'吗？',function(index){
          $.ajax({
              url: "/user/Loan/EditLoad?status="+status+"&loan_id="+id+"&deal_type="+deal_type ,
              type:"GET",
              success: function (res) {
                  if(res.code == 0){
                      layer.msg(str+'成功!',{time:1000,icon:1},function(){
                          location.reload();
                      });
                  }else{
                      layer.alert(str+'失败!',function(){
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
        var deal_load_id      = $("#deal_load_id").val();
        var user_id           = $("#user_id").val();
        var deal_id           = $("#deal_id").val();
        var name              = $("#name").val();
        var company           = $("#company").val();
        var audit_status      = $("#audit_status").val();
        if (deal_load_id == '' && user_id == '' && deal_id == '' && name == '' && company == '' && audit_status == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/JYSDebt/LoanList2Excel?deal_type=5&deal_load_id="+deal_load_id+"&user_id="+user_id+"&deal_id="+deal_id+"&name="+name+"&company="+company+"&audit_status="+audit_status , "_blank");
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
            window.open("/user/JYSDebt/LoanList2Excel?deal_type=5&condition_id="+condition_id , "_blank");
            reset_condition();
          });
        }
      }
    </script>
    <script type="text/html" id="operate">
      {{# if(d.audit_id !== null && d.audit_status == 1 && d.audit_edit_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="审核" lay-event="audit">审核</button>
      {{# } }}
      {{# if(d.audit_id !== null && (d.audit_status == 2 || d.audit_status == 3) && d.info_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="详情" lay-event="info">详情</button>
      {{# } }}
      {{# if(d.edit_status == 1 && d.black_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-danger" title="加入黑名单" lay-event="edit">加入黑名单</button>
      {{# } else if (d.edit_status == 1 && d.black_status == 2) { }}
      <button class="layui-btn layui-btn-xs" title="取消黑名单" lay-event="edit">取消黑名单</button>
      {{# } }}
    </script>
</html>