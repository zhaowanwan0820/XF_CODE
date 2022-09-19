<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>受让方列表</title>
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
              <a href="">首页</a>
              <a href="">债转市场管理</a>
                <a>
                    <cite>受让人管理</cite></a>
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
                                          <label class="layui-form-label">用户ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="user_id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">姓名</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="real_name" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">证件号码</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="idno" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">待审核</option>
                                              <option value="2">审核通过</option>
                                              <option value="3">审核通过（暂停）</option>
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
              width : 150
            },
            {
              field : 'area_name',
              title : '专区名称',
              width : 150
            },
            {
              field : 'user_id',
              title : '用户ID',
              width : 150
            },
            {
              field : 'real_name',
              title : '姓名',
              width : 200
            },
            {
              field : 'mobile',
              title : '手机号',
              width : 200
            },
            {
              field : 'idno',
              title : '证件号码',
              width : 200
            },
            {
              field : 'transferability_limit',
              title : '受让额度',
              width : 200
            },
            {
              field : 'transferred_amount',
              title : '已受让债权',
              width : 200
            },
            {
              field : 'trading_amount',
              title : '受让中债权',
              width : 200
            },
            {
              field : 'agreement_url',
              title : '合作框架协议',
              width : 200
            },
            {
              field : 'status_name',
              title : '状态',
              width : 200
            },
            
            {
              field : 'add_time',
              title : '添加时间',
              width : 200
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 320
            },
          ]],
          url      : '/debtMarket/DebtBuyer/AssigneeList',
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
              xadmin.open('添加受让方' , '/debtMarket/DebtBuyer/AddAssignee',800,600);
              break;
          };
        });

        form.on('submit(sreach)', function(obj){
          table.reload('list', {
            where    :
            {
              user_id    : obj.field.user_id,
              real_name  : obj.field.real_name,
              mobile     : obj.field.mobile,
              idno       : obj.field.idno,
              status     : obj.field.status,
              buyer_type : obj.field.buyer_type
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          switch(layEvent){
            case 'edit':
              xadmin.open('编辑受让方' , '/debtMarket/DebtBuyer/EditAssignee?id='+data.id);
              break;
            case 'info':
              xadmin.open('指定借款ID详情' , '/debtMarket/DebtBuyer/AssigneeDeal?user_id='+data.user_id);
              break;
            case 'verify':
              layer.confirm('确认要审核通过此受让方吗？' , function(index) {
                $.ajax({
                  url:'/debtMarket/DebtBuyer/VerifyAssignee',
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
            case 'del':
              layer.confirm('确认要移除此受让方吗？' , function(index) {
                $.ajax({
                  url:'/debtMarket/DebtBuyer/DelAssignee',
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
            case 'suspend_1':
              layer.confirm('确认要暂停此受让方吗？' , function(index) {
                $.ajax({
                  url:'/debtMarket/DebtBuyer/SuspendAssignee',
                  type:'post',
                  dataType:'json',
                  data:{id:data.id,type:1},
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
            case 'suspend_2':
              layer.confirm('确认要取消暂停此受让方吗？' , function(index) {
                $.ajax({
                  url:'/debtMarket/DebtBuyer/SuspendAssignee',
                  type:'post',
                  dataType:'json',
                  data:{id:data.id,type:2},
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
        <{if $add_status == 1}>
        <button class="layui-btn" lay-event="add"><i class="layui-icon">&#xe654;</i>添加</button>
        <{/if}>
      </div > 
    </script>
    <script type="text/html" id="operate">
      {{# if(d.buyer_type == 2){ }}
      <button class="layui-btn layui-btn-xs" title="指定借款ID详情" lay-event="info"><i class="layui-icon">&#xe60a;</i>查看</button>
      {{# } }}
      {{# if(d.edit_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="编辑受让方" lay-event="edit"><i class="layui-icon">&#xe642;</i>编辑</button>
      {{# } }}
      {{# if(d.status == 1){ }}
        {{# if(d.verify_status == 1){ }}
        <button class="layui-btn layui-btn-xs" title="通过审核" lay-event="verify"><i class="layui-icon">&#xe605;</i>通过</button>
        {{# } }}
        {{# if(d.del_status == 1){ }}
        <button class="layui-btn layui-btn-xs layui-btn-danger" title="移除受让方" lay-event="del"><i class="layui-icon">&#xe640;</i>移除</button>
        {{# } }}
      {{# }else if(d.status == 2 && d.suspend_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-danger" title="暂停接受转让" lay-event="suspend_1"><i class="layui-icon">&#xe651;</i>暂停</button>
      {{# }else if(d.status == 3 && d.suspend_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="开始接受转让" lay-event="suspend_2"><i class="layui-icon">&#xe652;</i>取消暂停</button>
      {{# } }}
    </script>
</html>