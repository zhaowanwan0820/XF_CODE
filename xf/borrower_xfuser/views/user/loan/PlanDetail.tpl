<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>选择方案明细</title>
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
                <a href="">网信业务数据</a>
                <a>
                    <cite>选择方案明细</cite></a>
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
                                            <label class="layui-form-label">借款标题</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_name" id="deal_name" placeholder="请输入借款标题" autocomplete="off" class="layui-input">
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
                                          <label class="layui-form-label">还款计划方案</label>
                                          <div class="layui-input-inline">
                                            <select name="repay_way" id="repay_way" lay-search="">
                                              <option value="">请选择还款计划方案</option>
                                              <option value="1">现金兑付</option>
                                              <option value="2">实物抵债兑付</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">选择方案时间</label>
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
                                            <{if $PlanDetail2Excel == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="PlanDetail2Excel()">导出</button>
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

        laydate.render({
            elem: '#start'
            ,type: 'datetime'
        });

        laydate.render({
            elem: '#end'
            ,type: 'datetime'
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
              field : 'deal_name',
              title : '借款标题'
            },
            {
              field : 'deal_load_id',
              title : '投资记录ID'
            },
            {
              field : 'user_id',
              title : '用户ID'
            },
            {
              field : 'money',
              title : '待还本金'
            },
            {
              field : 'repay_way_name',
              title : '还款计划方案'
            },
            {
              field : 'confirm_repay_time',
              title : '选择方案时间'
            }
          ]],
          url      : '/user/Loan/PlanDetail',
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
              deal_name    : obj.field.deal_name,
              deal_load_id : obj.field.deal_load_id,
              user_id      : obj.field.user_id,
              repay_way    : obj.field.repay_way,
              start        : obj.field.start,
              end          : obj.field.end
            },
            page:{curr:1}
          });
          return false;
        });

      });

      function PlanDetail2Excel()
      {
        var deal_name    = $("#deal_name").val();
        var deal_load_id = $("#deal_load_id").val();
        var user_id      = $("#user_id").val();
        var repay_way    = $("#repay_way").val();
        var start        = $("#start").val();
        var end          = $("#end").val();
        if (deal_name == '' && deal_load_id == '' && user_id == '' && repay_way == '' && start == '' && end == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/PlanDetail2Excel?deal_name="+deal_name+"&deal_load_id="+deal_load_id+"&user_id="+user_id+"&repay_way="+repay_way+"&start="+start+"&end="+end , "_blank");
          });
        }
      }
    </script>
    <script type="text/html" id="operate">
    </script>
</html>