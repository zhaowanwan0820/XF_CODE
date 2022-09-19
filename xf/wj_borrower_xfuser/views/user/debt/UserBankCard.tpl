<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>用户银行卡管理</title>
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
                    <cite>用户银行卡管理</cite></a>
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
                                            <input type="text" name="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">用户手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" placeholder="请输入用户手机号" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">银行卡号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="bankcard" placeholder="请输入银行卡号" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">有效</option>
                                              <option value="2">无效</option>
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
              field : 'user_id',
              title : '用户ID',
              fixed : 'left',
              width : 150
            },
            // {
            //   field : 'platform_id',
            //   title : '平台',
            //   width : 150
            // },
            {
              field : 'mobile',
              title : '用户手机号',
              width : 150
            },
            {
              field : 'name',
              title : '银行名称',
              width : 200
            },
            {
              field : 'bankzone',
              title : '开户行名称',
              width : 350
            },
            {
              field : 'province',
              title : '开户行所在省',
              width : 200
            },
            {
              field : 'city',
              title : '开户行所在市',
              width : 200
            },
            {
              field : 'card_name',
              title : '开户人姓名',
              width : 200
            },
            {
              field : 'bankcard_real',
              title : '银行卡号',
              width : 200
            },
            {
              field : 'branch_no',
              title : '银行联行号',
              width : 200
            },
            {
              field : 'bankcard',
              title : '加密银行卡号',
              width : 400
            },
            {
              field : 'verify_status_name',
              title : '验证状态',
              width : 100
            },
            {
              field : 'update_time',
              title : '更新时间',
              width : 200
            },
            {
              field : 'notes',
              title : '备注',
              width : 200
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 100
            },
          ]],
          url      : '/user/Debt/UserBankCard',
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
              // platform : obj.field.platform,
              user_id  : obj.field.user_id,
              bankcard : obj.field.bankcard,
              mobile   : obj.field.mobile,
              status   : obj.field.status
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'edit')
          {
            xadmin.open('编辑' , '/user/Debt/EditUserBankCard?id='+data.id);
          }
        });

      });
    </script>
    <script type="text/html" id="toolbar"> 
    </script>
    <script type="text/html" id="operate">
      <{if $edit_status == 1 }>
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="编辑" lay-event="edit"><i class="layui-icon">&#xe642;</i>编辑</button>
      <{/if}>
    </script>
</html>