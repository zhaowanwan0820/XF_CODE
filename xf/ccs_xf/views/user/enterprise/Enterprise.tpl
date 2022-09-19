<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>法大大企业信息认证</title>
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
                <a href="">受让方信息看板</a>
                <a>
                    <cite>法大大企业信息认证</cite></a>
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
                                          <label class="layui-form-label">企业全称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="company_name" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">企业证件号码</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="credentials_no" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">企业联系电话(手机号)</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="legalbody_mobile" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">待审核</option>
                                              <option value="2">审核未通过</option>
                                              <option value="3">审核通过</option>
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
              field : 'id',
              title : 'ID',
              fixed : 'left',
              width : 100
            },
            {
              field : 'company_name',
              title : '企业全称',
              width : 200
            },
            {
              field : 'credentials_no',
              title : '企业证件号码',
              width : 200
            },
            {
              field : 'legalbody_name',
              title : '法定代表人姓名',
              width : 120
            },
            {
              field : 'legalbody_credentials_no',
              title : '法定代表人证件号码',
              width : 160
            },
            {
              field : 'legalbody_mobile',
              title : '企业联系电话(手机号)',
              width : 140
            },
            {
              field : 'registration_address',
              title : '企业注册地址',
              width : 200
            },
            {
              field : 'contract_address',
              title : '企业联系地址',
              width : 200
            },
            {
              field : 'status_name',
              title : '状态',
              width : 100
            },
            {
              field : 'add_time',
              title : '申请时间',
              width : 150
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 250
            }
          ]],
          url      : '/user/Enterprise/Enterprise',
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
              xadmin.open('新增企业' , '/user/Enterprise/AddEnterprise');
              break;
          };
        });

        form.on('submit(sreach)', function(obj){
          table.reload('list', {
            where    :
            {
              company_name     : obj.field.company_name,
              credentials_no   : obj.field.credentials_no,
              legalbody_mobile : obj.field.legalbody_mobile,
              status           : obj.field.status
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          switch(layEvent){
            case 'edit':
              xadmin.open('编辑企业信息' , '/user/Enterprise/EditEnterprise?id='+data.id);
              break;
            case 'verify':
              xadmin.open('审核企业信息' , '/user/Enterprise/VerifyEnterprise?id='+data.id);
              break;
            case 'del':
              layer.confirm('确认要移除此企业信息认证申请吗？' , function(index) {
                $.ajax({
                  url:'/user/Enterprise/DelEnterprise',
                  type:'post',
                  dataType:'json',
                  data:{id:data.id},
                  success:function(res){
                    if (res['code'] == 0) {
                      table.reload('list');
                      layer.msg(res['info'] , {icon:1 , time:2000});
                    } else {
                      table.reload('list');
                      layer.msg(res['info'] , {icon:2 , time:5000});
                    }
                  }
                });
              });
              break;
          };
        });

      });
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" > 
        <{if $AddEnterprise == 1}>
        <button class="layui-btn" lay-event="add"><i class="layui-icon">&#xe654;</i>新增企业</button>
        <{/if}>
      </div > 
    </script>
    <script type="text/html" id="operate">
      {{# if(d.edit_status == 1 && (d.status == 1 || d.status == 2) ){ }}
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="编辑企业信息" lay-event="edit"><i class="layui-icon">&#xe642;</i>编辑</button>
      {{# } }}
      {{# if(d.verify_status == 1 && d.status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="审核企业信息" lay-event="verify"><i class="layui-icon">&#xe605;</i>审核</button>
      {{# } }}
      {{# if(d.del_status == 1 && (d.status == 1 || d.status == 2)){ }}
      <button class="layui-btn layui-btn-xs layui-btn-danger" title="移除" lay-event="del"><i class="layui-icon">&#xe640;</i>移除</button>
      {{# } }}
    </script>
</html>