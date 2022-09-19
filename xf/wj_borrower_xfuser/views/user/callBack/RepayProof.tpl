<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>借款人还款凭证</title>
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
                <a href="">借款人呼叫管理</a>
                <a>
                    <cite>借款人还款凭证</cite></a>
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
                                            <label for="mobile" class="layui-form-label">手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="mobile" id="mobile" placeholder="请输入手机号" autocomplete="off" class="layui-input">
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
              title : 'ID'
            },
            {
              field : 'user_id',
              title : '用户ID'
            },
            {
              field : 'real_name',
              title : '用户姓名'
            },
            {
              field : 'idno',
              title : '用户证件号'
            },
            {
              field : 'mobile',
              title : '手机号'
            },
            {
              field : 'add_time',
              title : '上传时间'
            },
            {
              title   : '操作',
              toolbar : '#operate'
            }
          ]],
          url      : '/user/CallBack/RepayProof',
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
              mobile     : obj.field.mobile
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

          if (layEvent === 'info') {
            xadmin.open('借款人还款凭证详情' , '/user/CallBack/RepayProofInfo?id='+data.id);
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
      <button class="layui-btn layui-btn-xs" title="借款人还款凭证详情" lay-event="info">详情</button>
      {{# } }}
    </script>
</html>