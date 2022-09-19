<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>呼叫记录</title>
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
                <a><cite></cite></a>
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
                                            <label class="layui-form-label">客服姓名</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="add_user_name" id="add_user_name" placeholder="请输入客服姓名" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">呼叫时间</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="开始时间" name="start" id="start" readonly>
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="截止时间" name="end" id="end" readonly>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">是否已失联</label>
                                          <div class="layui-input-inline">
                                            <select name="question_1" id="question_1" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">否</option>
                                              <option value="2">是-空号</option>
                                              <option value="3">是-停机</option>
                                              <option value="4">是-非本人</option>
                                              <option value="5">其他</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">可联系状态</label>
                                          <div class="layui-input-inline">
                                            <select name="question_2" id="question_2" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">关机</option>
                                              <option value="2">未接通挂断</option>
                                              <option value="3">占线</option>
                                              <option value="4">接通后5秒内挂断</option>
                                              <option value="5">接通后10秒内挂断</option>
                                              <option value="6">接通后已详细通知</option>
                                              <option value="7">其他</option>
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
              field : 'add_user_name',
              title : '客服姓名'
            },
            {
              field : 'add_time',
              title : '呼叫时间'
            },
            {
              field : 'question_1',
              title : '是否已失联'
            },
            {
              field : 'question_2',
              title : '可联系状态'
            },
            {
              field : 'question_3',
              title : '客户（出借人）对当前债权的态度'
            },
            {
              field : 'question_4',
              title : '客户（出借人）换购意愿'
            },
            {
              field : 'question_5',
              title : '客户（出借人）对商品的选择偏好'
            },
            {
              title   : '操作',
              toolbar : '#operate',
              width   : 100
            }
          ]],
          url      : '/user/DebtLiquidation/CallBackLogList',
          method   : 'post',
          where    : {'user_id':'<{$_GET['user_id']}>'},
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
              add_user_name : obj.field.add_user_name,
              start         : obj.field.start,
              end           : obj.field.end,
              question_1    : obj.field.question_1,
              question_2    : obj.field.question_2
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

          if (layEvent === 'info') {
            xadmin.open('呼叫记录问题详情' , '/user/DebtLiquidation/CallBackLogInfo?id='+data.id);
          }
        });

      });
    </script>
    <script type="text/html" id="operate">
      {{# if(d.info_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="呼叫记录问题详情" lay-event="info">详情</button>
      {{# } }}
    </script>
</html>