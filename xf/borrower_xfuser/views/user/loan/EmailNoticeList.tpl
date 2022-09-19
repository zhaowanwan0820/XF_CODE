<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>债转邮件通知记录</title>
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
                <a href="">化债管理</a>
                <a>
                    <cite>债转邮件通知记录</cite></a>
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
                                          <label class="layui-form-label">所属平台</label>
                                          <div class="layui-input-inline">
                                            <select name="platform_id" id="platform_id" lay-search="">
                                              <option value="1">尊享</option>
                                              <option value="2">普惠</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">担保方名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="agency_name" id="agency_name" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">咨询方名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="advisory_name" id="advisory_name" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">债务方名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="company_name" id="company_name" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">待启动</option>
                                              <option value="2">已启动</option>
                                              <option value="3">发送中</option>
                                              <option value="4">发送完成</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">债转起止时间</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="债转起始时间" name="start" id="start" readonly>
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="债转结束时间" name="end" id="end" readonly>
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
            elem: '#start',
            type: 'datetime'
        });

        laydate.render({
            elem: '#end',
            type: 'datetime'
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
              width : 100
            },
            {
              field : 'platform_id',
              title : '所属平台',
              width : 100
            },
            {
              field : 'agency_name',
              title : '担保方名称',
              width : 250
            },
            {
              field : 'advisory_name',
              title : '咨询方名称',
              width : 250
            },
            {
              field : 'company_name',
              title : '债务方名称',
              width : 250
            },
            {
              field : 'status_name',
              title : '状态',
              width : 100
            },
            {
              field : 'email_address',
              title : '接收邮件邮箱',
              width : 250
            },
            {
              field : 'debt_start_time',
              title : '债转起始时间',
              width : 150
            },
            {
              field : 'debt_end_time',
              title : '债转结束时间',
              width : 150
            },
            {
              field : 'debt_number',
              title : '债转总条数',
              width : 150
            },
            {
              field : 'send_number',
              title : '已发送债转总条数',
              width : 150
            },
            {
              field : 'add_time',
              title : '录入时间',
              width : 150
            },
            {
              field : 'success_time',
              title : '邮件通知完成时间',
              width : 150
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 150
            },
          ]],
          url      : '/user/Loan/EmailNoticeList',
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

        table.on('toolbar(list)' , function(obj){
          var checkStatus = table.checkStatus(obj.config.id);
          switch(obj.event){
            case 'add':
              xadmin.open('新增债转通知','/user/Loan/AddEmailNotice');
              break;
          };
        });

        form.on('submit(sreach)', function(obj){
          table.reload('list', {
            where    :
            {
              platform_id   : obj.field.platform_id,
              agency_name   : obj.field.agency_name,
              advisory_name : obj.field.advisory_name,
              company_name  : obj.field.company_name,
              status        : obj.field.status,
              start         : obj.field.start,
              end           : obj.field.end
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'start') {
            $.ajax({
              url:'/user/Loan/StartEmailNotice',
              type:'post',
              dataType:'json',
              data:{id:data.id},
              success:function(res){
                if (res['code'] === 0) {
                  layer.msg(res['info'] , {time:1000,icon:1} , function(){
                    location.reload();
                  });
                } else {
                  layer.alert(res['info']);
                }
              }
            });
          } else if (layEvent === 'info') {
            xadmin.open('详情','/user/Loan/EmailNoticeInfo?id='+data.id);
          }
        });

      });
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
        <{if $add_status == 1 }>
        <button class="layui-btn" lay-event="add"><i class="layui-icon">&#xe654;</i>新增债转通知</button>
        <{/if}>
      </div > 
    </script>
    <script type="text/html" id="operate">
      {{# if(d.start_status == 1 && d.status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="启动" lay-event="start">启动</button>
      {{# } else if (d.start_status == 1 && d.status != 1) { }}
      <button class="layui-btn layui-btn-xs layui-btn-disabled" title="启动">启动</button>
      {{# } }}
      {{# if(d.info_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="详情" lay-event="info">详情</button>
      {{# } }}
    </script>
</html>