<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>用户自主修改手机号</title>
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
                <a href="">用户信息维护</a>
                <a>
                    <cite>用户自主修改手机号</cite></a>
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
                                            <label for="user_id" class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" id="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="real_name" class="layui-form-label">用户姓名</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="real_name" id="real_name" placeholder="请输入用户姓名" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="idno" class="layui-form-label">用户证件号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="idno" id="idno" placeholder="请输入用户证件号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="old_mobile" class="layui-form-label">旧手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="old_mobile" id="old_mobile" placeholder="请输入旧手机号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="new_mobile" class="layui-form-label">新手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="new_mobile" id="new_mobile" placeholder="请输入新手机号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">审核状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">待审核</option>
                                              <option value="2">审核通过</option>
                                              <option value="3">审核拒绝</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">类型</label>
                                          <div class="layui-input-inline">
                                            <select name="type" id="type" lay-search="">
                                              <option value="">全部</option>
                                              <option value="2">旧手机号不可用</option>
                                              <option value="3">旧手机号可用</option>
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
              fixed : 'left',
              width : 150
            },
            {
              field : 'user_id',
              title : '用户ID',
              width : 150
            },
            {
              field : 'real_name',
              title : '用户姓名',
              width : 150
            },
            {
              field : 'idno',
              title : '用户证件号',
              width : 200
            },
            {
              field : 'old_mobile',
              title : '旧手机号',
              width : 150
            },
            {
              field : 'new_mobile',
              title : '新手机号',
              width : 150
            },
            {
              field : 'add_time',
              title : '申请时间',
              width : 150
            },
            {
              field : 'status_name',
              title : '审核状态',
              width : 150
            },
            {
              field : 'type_name',
              title : '类型',
              width : 150
            },
            {
              field : 'audit_user_name',
              title : '审核人',
              width : 150
            },
            {
              field : 'audit_time',
              title : '审核时间',
              width : 150
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 150
            }
          ]],
          url      : '/user/Message/UserMobileOneself',
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
              user_id    : obj.field.user_id,
              real_name  : obj.field.real_name,
              idno       : obj.field.idno,
              old_mobile : obj.field.old_mobile,
              new_mobile : obj.field.new_mobile,
              status     : obj.field.status,
              type       : obj.field.type
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

          if (layEvent === 'info') {
            xadmin.open('申请详情' , '/user/Message/UserMobileOneselfInfo?id='+data.id);
          } else if (layEvent === 'edit') {
            xadmin.open('审核申请' , '/user/Message/auditUserMobileOneself?id='+data.id);
          }
        });

      });
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
      </div > 
    </script>
    <script type="text/html" id="operate">
      {{# if(d.info_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="申请详情" lay-event="info">详情</button>
      {{# } }}
      {{# if(d.audit_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="审核申请" lay-event="edit">审核</button>
      {{# } }}
    </script>
</html>