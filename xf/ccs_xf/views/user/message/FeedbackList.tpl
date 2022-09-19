<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>意见反馈列表</title>
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
                <a href="">公告消息管理</a>
                <a>
                    <cite>意见反馈列表</cite></a>
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
                                            <label for="mobile" class="layui-form-label">用户手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="mobile" id="mobile" placeholder="请输入用户手机号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="content" class="layui-form-label">内容关键词</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="content" id="content" placeholder="请输入内容关键词" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">提交时间</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="开始时间" name="start" id="start" readonly>
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="截止时间" name="end" id="end" readonly>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">回复状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">请选择回复状态</option>
                                              <option value="1">待回复</option>
                                              <option value="2">处理中</option>
                                              <option value="3">已回复</option>
                                            </select>
                                          </div>
                                        </div>

                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                          <{if $daochu_status == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="daochu()">导出</button>
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
              field : 'id',
              title : 'ID',
              fixed : 'left',
              width : 100
            },
            {
              field : 'content',
              title : '内容',
            },
            {
              field : 'status_name',
              title : '回复状态',
              width : 100
            },
            {
              field : 'user_id',
              title : '用户ID',
              width : 100
            },
            {
              field : 'user_real_name',
              title : '用户姓名',
              width : 100
            },
            {
              field : 'user_mobile',
              title : '用户手机号',
              width : 110
            },
            {
              field : 'add_time',
              title : '提交时间',
              width : 150
            },
            {
              field : 'operation_time',
              title : '回复时间',
              width : 150
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 150
            }
          ]],
          url      : '/user/Message/FeedbackList',
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
              status  : obj.field.status,
              start   : obj.field.start,
              end     : obj.field.end,
              mobile  : obj.field.mobile,
              content : obj.field.content
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('toolbar(list)' , function(obj){
          switch(obj.event){
            case 'batch_edit':
              xadmin.open('批量回复','/user/Message/batchEditFeedback');
              break;
          };
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

          if (layEvent === 'info') {
            xadmin.open('意见反馈详情' , '/user/Message/FeedbackInfo?id='+data.id);
          } else if (layEvent === 'edit') {
            xadmin.open('回复意见反馈' , '/user/Message/EditFeedback?id='+data.id);
          } else if (layEvent === 'set') {
            $.ajax({
              url:'/user/Message/SetNotice',
              type:'post',
              data:{
                id : data.id
              },
              dataType:'json',
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
          }
        });

      });

      function daochu()
      {
        var mobile  = $("#mobile").val();
        var start   = $("#start").val();
        var end     = $("#end").val();
        var status  = $("#status").val();
        var content = $("#content").val();
        if (mobile == '' && start == '' && end == '' && status == '' && content == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Message/Feedback2CSV?mobile="+mobile+"&start="+start+"&end="+end+"&status="+status+"&content="+content , "_blank");
          });
        }
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
        <{if $batch_edit == 1 }>
        <button class="layui-btn layui-btn-xs" title="批量回复" lay-event="batch_edit">批量回复</button>
        <{/if}>
      </div >  
    </script>
    <script type="text/html" id="operate">
      {{# if(d.info_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="查看意见反馈详情" lay-event="info">详情</button>
      {{# } }}
      {{# if(d.edit_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="回复意见反馈" lay-event="edit">回复</button>
      {{# } }}
    </script>
</html>