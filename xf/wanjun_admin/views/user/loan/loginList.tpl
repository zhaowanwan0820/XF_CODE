<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>用户登录记录</title>
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
                    <cite>用户登录记录</cite></a>
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
                                          <label class="layui-form-label">出借人ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="user_id"  id="user_id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">出借人姓名</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="real_name"  id="real_name"  autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">出借人手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" id="mobile"   autocomplete="off" class="layui-input">
                                          </div>
                                        </div>
                                          <div class="layui-inline">
                                              <label class="layui-form-label">出借人证件号</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="idno"  id="idno"  autocomplete="off" class="layui-input">
                                              </div>
                                          </div>


                                          <div class="layui-inline">
                                              <label class="layui-form-label">最后登录时间</label>
                                              <div class="layui-input-inline">
                                                  <input class="layui-input" placeholder="开始时间" name="start" id="start" readonly>
                                              </div>
                                              <div class="layui-input-inline" style="width: 5px;">
                                                  ~
                                              </div>
                                              <div class="layui-input-inline">
                                                  <input class="layui-input" placeholder="截止时间" name="end" id="end" readonly>
                                              </div>
                                          </div>

                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $loginList2Excel == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="DealUser2Excel()">导出</button>
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
          });

          laydate.render({
              elem: '#end'
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
                  field : 'user_id',
                  title : '出借人ID',
                  width : 100
              },

              {
                  field : 'real_name',
                  title : '出借人姓名',
                  width : 100
              },
              {
                  field : 'mobile_phone',
                  title : '出借人手机号',
                  width : 120
              },
              {
                  field : 'idno',
                  title : '出借人证件号'
              },{
                  field : 'login_count',
                  title : '登录次数',
                  width : 120
              },

              {
              field : 'login_time',
              title : '最后登录时间',
              width : 160
            },
              {title: '操作', toolbar: '#operate', width: 190},
          ]],
          url : '/user/Loan/loginList',
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
            where:
            {
              user_id : obj.field.user_id,
              real_name : obj.field.real_name,
              mobile : obj.field.mobile,
              idno : obj.field.idno,
                start : obj.field.start,
                end : obj.field.end,
            },
              page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
            if (layEvent === 'detail') {
                xadmin.open('详情', '/user/Loan/loginView?user_id=' + data.user_id );
            }
        });

      });
      function DealUser2Excel()
      {
          var user_id = $("#user_id").val();
          var real_name = $("#real_name").val();
          var mobile = $("#mobile").val();
          var idno = $("#idno").val();
          var start = $("#start").val();
          var end = $("#end").val();
          if (start == '' && end == '' && idno == '' && mobile == '' && user_id == '' && real_name == '') {
              layer.msg('请输入至少一个查询条件');
          } else {
              layer.confirm('确认要根据当前筛选条件导出吗？',
                  function(index) {
                      layer.close(index);
                      window.open("/user/Loan/loginList2Excel?start="+start+"&end="+end+"&idno="+idno+"&mobile="+mobile+"&user_id="+user_id+"&real_name="+real_name , "_blank");
                  });
          }
      }


    </script>

    <script type="text/html" id="operate">
        <button class="layui-btn" title="详情" lay-event="detail">详情</button>
    </script>
</html>