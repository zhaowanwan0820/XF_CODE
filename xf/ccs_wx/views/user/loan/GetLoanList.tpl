<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>在途投资记录</title>
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
                <a href="">债权管理</a>
                <a>
                    <cite>在途投资记录</cite></a>
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

                                        <div class="layui-inline">
                                          <label class="layui-form-label">所属平台</label>
                                          <div class="layui-input-inline">
                                            <select name="deal_type" id="deal_type" lay-search="">
                                              <option value="1">尊享</option>
                                              <option value="2">普惠</option>
                                              <!-- <option value="3">金融工场</option> -->
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">债转状态</label>
                                            <div class="layui-input-inline">
                                                <select name="debt_status" lay-search="">
                                                    <option value="20">全部</option>
                                                    <option value="0">未债转</option>
                                                    <option value="1">新创建转让中</option>
                                                    <option value="15">全部转让成功</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">黑名单状态</label>
                                            <div class="layui-input-inline">
                                                <select name="black_status" lay-search="">
                                                    <option value="">全部</option>
                                                    <option value="1">未加入</option>
                                                    <option value="2">已加入</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">投资记录状态</label>
                                            <div class="layui-input-inline">
                                                <select name="deal_src" lay-search="">
                                                    <option value="">全部</option>
                                                    <option value="1">在途</option>
                                                    <option value="2">已结清</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">确权状态</label>
                                            <div class="layui-input-inline">
                                                <select name="is_debt_confirm" lay-search="">
                                                    <option value="2">全部</option>
                                                    <option value="0">未确权</option>
                                                    <option value="1">已确权</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款标题</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name" placeholder="请输入借款标题" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="mobile" placeholder="请输入用户手机号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_id" placeholder="请输入借款ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">投资记录ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="load_id" placeholder="请输入投资记录ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款人名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="company" placeholder="请输入借款人名称" autocomplete="off" class="layui-input">
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
              title : '投资记录ID',
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
              title : '用户实名',
              width : 150
            },
            {
              field : 'mobile',
              title : '用户手机号',
              width : 150
            },
            {
              field : 'deal_id',
              title : '借款ID',
              width : 150
            },
            {
              field : 'name',
              title : '借款标题',
              width : 300
            },
            {
              field : 'money',
              title : '投标金额',
              width : 150
            },
            {
              field : 'wait_capital',
              title : '在途本金',
              width : 150
            },
            {
              field : 'status_name',
              title : '投资记录状态',
              width : 150
            },
            {
              field : 'debt_name',
              title : '债转状态',
              width : 150
            },
            {
              field : 'black_status_name',
              title : '黑名单状态',
              width : 150
            },
            {
              field : 'create_time',
              title : '投资时间',
              width : 150
            },
            {
              field : 'time',
              title : '计划还款时间',
              width : 150
            },
            {
              field : 'is_debt_confirm',
              title : '确权状态',
              width : 150
            },
            {
              field : 'join_reason',
              title : '备注',
              width : 300
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 150
            },
          ]],
          url      : '/user/Loan/GetLoanList',
          method   : 'post',
          where    : {deal_type : 1},
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
              deal_type       : obj.field.deal_type,
              debt_status     : obj.field.debt_status,
              black_status    : obj.field.black_status,
              deal_src        : obj.field.deal_src,
              is_debt_confirm : obj.field.is_debt_confirm,
              user_id         : obj.field.user_id,
              name            : obj.field.name,
              mobile          : obj.field.mobile,
              deal_id         : obj.field.deal_id,
              load_id         : obj.field.load_id,
              company         : obj.field.company,
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'edit')
          {
            if (data.black_status == 1) {
              xadmin.open('加入黑名单','/user/Loan/BlackEditAdd?loan_id='+data.id+'&deal_type='+data.deal_type+'&status=2');
            } else if (data.black_status == 2) {
              member_join(data.id,1,data.deal_type);
            }
          }
        });

      });

      function member_join(id,status,deal_type){
        if(status == 2){
            str = '加入黑名单';
        }else{
            str = '取消黑名单';
        }
        layer.confirm('确认要'+str+'吗？',function(index){
          $.ajax({
              url: "/user/Loan/EditLoad?status="+status+"&loan_id="+id+"&deal_type="+deal_type ,
              type:"GET",
              success: function (res) {
                  if(res.code == 0){
                      layer.msg(str+'成功!',{time:1000,icon:1},function(){
                          location.reload();
                      });
                  }else{
                      layer.alert(str+'失败!',function(){
                          location.reload();
                      });
                  }
              }
          })
        });
      }
    </script>
    <script type="text/html" id="operate">
      {{# if(d.edit_status == 1 && d.black_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-danger" title="加入黑名单" lay-event="edit">加入黑名单</button>
      {{# } else if (d.edit_status == 1 && d.black_status == 2) { }}
      <button class="layui-btn layui-btn-xs" title="取消黑名单" lay-event="edit">取消黑名单</button>
      {{# } }}
    </script>
</html>