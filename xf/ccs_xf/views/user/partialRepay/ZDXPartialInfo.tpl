<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>智多新部分还款详情</title>
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
                <a><cite>智多新部分还款详情</cite></a>
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
                                                <input type="text" name="name" id="name" placeholder="请输入借款标题" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">投资记录ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_loan_id" id="deal_loan_id" placeholder="请输入投资记录ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" id="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">导入状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">成功</option>
                                              <option value="2">失败</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">还款状态</label>
                                          <div class="layui-input-inline">
                                            <select name="repay_status" id="repay_status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">待还</option>
                                              <option value="2">已还</option>
                                            </select>
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
              title : '序号',
              fixed : 'left',
              width : 100
            },
            {
              field : 'name',
              title : '借款标题',
              width : 150
            },
            {
              field : 'end_time',
              title : '到期日',
              width : 150
            },
            {
              field : 'deal_loan_id',
              title : '投资记录ID',
              width : 150
            },
            {
              field : 'user_id',
              title : '用户ID',
              width : 150
            },
            {
              field : 'repay_money',
              title : '还款金额',
              width : 150
            },
            {
              field : 'status',
              title : '导入状态',
              width : 150
            },
            {
              field : 'repay_yestime',
              title : '实际还款时间',
              width : 150
            },
            {
              field : 'repay_status',
              title : '还款状态',
              width : 150
            },
            {
              field : 'remark',
              title : '失败原因',
              width : 200
            }
          ]],
          url      : '/user/PartialRepay/XFPartialInfo',
          method   : 'post',
          where    : {id:<{$_GET['id']}> , platform_id:<{$_GET['platform_id']}>},
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
              name         : obj.field.name,
              deal_loan_id : obj.field.deal_loan_id,
              user_id      : obj.field.user_id,
              status       : obj.field.status,
              repay_status : obj.field.repay_status
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('toolbar(list)' , function(obj){
          switch(obj.event){
            case 'daochu':
              daochu();
              break;
          };
        });

      });

      function daochu()
      {
        var name         = $("#name").val();
        var deal_loan_id = $("#deal_loan_id").val();
        var user_id      = $("#user_id").val();
        var status       = $("#status").val();
        var repay_status = $("#repay_status").val();
        layer.confirm('确认要根据当前筛选条件导出吗？',
        function(index) {
          layer.close(index);
          window.open("/user/PartialRepay/ZDXPartial2Excel?id=<{$_GET['id']}>&platform_id=<{$_GET['platform_id']}>&name="+name+"&deal_loan_id="+deal_loan_id+"&user_id="+user_id+"&status="+status+"&repay_status="+repay_status , "_blank");
        });
      }

    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
        <{if $daochu_status == 1 }>
        <button class="layui-btn layui-btn-xs" title="导出" lay-event="daochu">导出</button>
        <{/if}>
      </div > 
    </script>
</html>