<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>用户管理</title>
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
                <a href="">用户信息管理</a>
                <a>
                    <cite>用户管理</cite></a>
            </span>
            <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right" onclick="location.reload()" title="刷新">
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
                                        <!-- <div class="layui-inline">
                                          <label class="layui-form-label">平台</label>
                                          <div class="layui-input-inline">
                                            <select name="platform" lay-search="">
                                              <option value="">全部</option>
                                              <{foreach $platform as $k => $v}>
                                              <option value="<{$v['id']}>"><{$v['name']}></option>
                                              <{/foreach}>
                                            </select>
                                          </div>
                                        </div> -->

                                        <div class="layui-inline">
                                          <label class="layui-form-label">用户ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="id" placeholder="请输入用户ID" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">法大大ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="fdd_customer_id" placeholder="请输入法大大ID" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">用户名</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="user_name" placeholder="请输入用户名" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">真实姓名</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="real_name" placeholder="请输入真实姓名" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">性别</label>
                                          <div class="layui-input-inline">
                                            <select name="sex" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">男</option>
                                              <option value="2">女</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">证件号码</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="idno" placeholder="请输入证件号码" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">手机号码</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" placeholder="请输入手机号码" autocomplete="off" class="layui-input" value="">
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
              title : '用户ID',
              fixed : 'left',
              width : 150
            },
            // {
            //   field : 'platform_id',
            //   title : '平台',
            //   width : 200
            // },
            {
              field : 'user_name',
              title : '用户名',
              width : 200
            },
            {
              field : 'real_name',
              title : '真实姓名',
              width : 200
            },
            {
              field : 'sex',
              title : '性别',
              width : 100
            },
            {
              field : 'idno',
              title : '证件号码',
              width : 200
            },
            {
              field : 'mobile',
              title : '手机号码',
              width : 200
            },
            {
              field : 'create_time',
              title : '注册时间',
              width : 200
            },
            {
              field : 'is_effect',
              title : '账户状态',
              width : 100
            },
            {
              field : 'is_delete',
              title : '是否已删除',
              width : 100
            },
            {
              field : 'email',
              title : '邮箱',
              width : 200
            },
            {
              field : 'fdd_customer_id',
              title : '法大大ID',
              width : 300
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 100
            },
          ]],
          url      : '/user/Debt/GetUserList',
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
              // platform        : obj.field.platform,
              id              : obj.field.id,
              fdd_customer_id : obj.field.fdd_customer_id,
              sex             : obj.field.sex,
              user_name       : obj.field.user_name,
              real_name       : obj.field.real_name,
              idno            : obj.field.idno,
              mobile          : obj.field.mobile
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'info')
          {
            xadmin.open('用户详情' , '/user/Debt/GetUserInfo?id='+data.id);
          }
        });

      });
    </script>
    <script type="text/html" id="toolbar">
    </script>
    <script type="text/html" id="operate">
      {{# if(d.info_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="用户详情" lay-event="info"><i class="layui-icon">&#xe6b2;</i>详情</button>
      {{# } }}
    </script>
</html>