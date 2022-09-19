<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>出借人回呼记录</title>
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
                <a href="">下车专栏</a>
                <a>
                    <cite>出借人回呼记录</cite></a>
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
                                            <label for="mobile" class="layui-form-label">用户手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="mobile" id="mobile" placeholder="请输入用户手机号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="idno" class="layui-form-label">用户证件号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="idno" id="idno" placeholder="请输入用户证件号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">性别</label>
                                          <div class="layui-input-inline">
                                            <select name="sex" id="sex" lay-search="">
                                              <option value="">全部</option>
                                              <option value="0">女</option>
                                              <option value="1">男</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">年龄段</label>
                                          <div class="layui-input-inline" style="width: 90px">
                                            <input class="layui-input" name="age_min" id="age_min" type="number" min="1" max="150">
                                          </div>
                                          <div class="layui-input-inline" style="width: 90px">
                                            <input class="layui-input" name="age_max" id="age_max" type="number" min="1" max="150">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="province" class="layui-form-label">省</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="province" id="province" placeholder="请输入省" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="city" class="layui-form-label">市</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="city" id="city" placeholder="请输入市" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="region" class="layui-form-label">区</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="region" id="region" placeholder="请输入区" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label for="call_times" class="layui-form-label">拨打次数</label>
                                            <div class="layui-input-inline">
                                                <input type="number" name="call_times" id="call_times" placeholder="请输入拨打次数" autocomplete="off" class="layui-input" min="0">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">最后一次拨打时间段</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="开始时间" name="start" id="start" readonly>
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
            elem : '#start',
            type : 'datetime'
        });

        laydate.render({
            elem : '#end',
            type : 'datetime'
        });

        table.render({
          elem           : '#list',
          toolbar        : '<div><i class="iconfont" style="color:orange;">&#xe6b6;</i> 省市区均为用户证件号所在地</div>',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'user_id',
              title : '用户ID',
              width : 100
            },
            {
              field : 'real_name',
              title : '用户姓名',
              width : 150
            },
            {
              field : 'mobile',
              title : '用户手机号',
              width : 150
            },
            {
              field : 'idno',
              title : '用户证件号',
              width : 150
            },
            {
              field : 'sex',
              title : '性别',
              width : 100
            },
            {
              field : 'age',
              title : '年龄',
              width : 100
            },
            {
              field : 'province',
              title : '省',
              width : 100
            },
            {
              field : 'city',
              title : '市',
              width : 100
            },
            {
              field : 'region',
              title : '区',
              width : 100
            },
            {
              field : 'call_times',
              title : '拨打次数',
              width : 100
            },
            {
              field : 'last_call_time',
              title : '最后一次拨打时间',
              width : 150
            },
            {
              field : 'remark',
              title : '备注'
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 100
            }
          ]],
          url      : '/user/DebtLiquidation/CallBackUserList',
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
              mobile     : obj.field.mobile,
              sex        : obj.field.sex,
              age_min    : obj.field.age_min,
              age_max    : obj.field.age_max,
              province   : obj.field.province,
              city       : obj.field.city,
              region     : obj.field.region,
              call_times : obj.field.call_times,
              start      : obj.field.start,
              end        : obj.field.end
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

          if (layEvent === 'info') {
            xadmin.open('呼叫记录' , '/user/DebtLiquidation/CallBackLogList?user_id='+data.user_id);
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
      <button class="layui-btn layui-btn-xs" title="呼叫记录" lay-event="info">呼叫记录</button>
      {{# } }}
    </script>
</html>