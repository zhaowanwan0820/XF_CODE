<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>未确认收款债转记录</title>
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
                    <cite>未确认收款债转记录</cite></a>
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
                                            <input type="radio" name="deal_type" value="1" title="尊享" checked>
                                            <input type="radio" name="deal_type" value="2" title="普惠">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">转让状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">转让中</option>
                                              <option value="2">交易成功</option>
                                              <option value="3">交易取消</option>
                                              <option value="4">已过期</option>
                                              <option value="5">待付款</option>
                                              <option value="6">待收款</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">债转类型</label>
                                          <div class="layui-input-inline">
                                            <select name="debt_src" id="debt_src" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">权益兑换</option>
                                              <option value="2">债转交易</option>
                                              <option value="3">债权划扣</option>
                                              <option value="4">一键下车</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">债转编号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="serial_number" id="serial_number" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">转让人ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="user_id" id="user_id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">转让人手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" id="mobile" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">项目ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="borrow_id" id="borrow_id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款标题</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="name" id="name" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">投资记录ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="tender_id" id="tender_id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款人名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="company" id="company" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">受让人ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="t_user_id" id="t_user_id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">受让人手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="t_mobile" id="t_mobile" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">转让完成时间</label>
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
              field : 'number',
              title : '序号',
              fixed : 'left',
              width : 100
            },
            {
              field : 'id',
              title : '债转ID',
              fixed : 'left',
              width : 100
            },
            {
              field : 'serial_number',
              title : '债转编号',
              width : 200
            },
            {
              field : 'user_id',
              title : '转让人ID',
              width : 100
            },
            {
              field : 'real_name',
              title : '转让人姓名',
              width : 100
            },
            {
              field : 'mobile',
              title : '转让人手机号',
              width : 150
            },
            {
              field : 'borrow_id',
              title : '项目ID',
              width : 100
            },
            {
              field : 'name',
              title : '借款标题',
              width : 200
            },
            {
              field : 'tender_id',
              title : '投资记录ID',
              width : 100
            },
            {
              field : 'money',
              title : '发起债转金额',
              width : 150
            },
            {
              field : 'sold_money',
              title : '已转出金额',
              width : 150
            },
            {
              field : 'discount',
              title : '折扣',
              width : 100
            },
            {
              field : 'status',
              title : '转让状态',
              width : 150
            },
            {
              field : 'debt_src',
              title : '债转类型',
              width : 100
            },
            {
              field : 't_user_id',
              title : '受让人ID',
              width : 100
            },
            {
              field : 't_real_name',
              title : '受让人姓名',
              width : 100
            },
            {
              field : 't_mobile',
              title : '受让人手机号',
              width : 150
            },
            {
              field : 'addtime',
              title : '发起时间',
              width : 200
            },
            {
              field : 'successtime',
              title : '转让完成时间',
              width : 200
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 100
            },
          ]],
          url      : '/user/Debt/DebtAppealA',
          method   : 'post',
          where    : {type : 1 , deal_type : 1},
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
            case 'daochu':
              daochu();
              break;
          };
        });

        form.on('submit(sreach)', function(obj){
          table.reload('list', {
            where    :
            {
              deal_type     : obj.field.deal_type,
              status        : obj.field.status,
              debt_src      : obj.field.debt_src,
              serial_number : obj.field.serial_number,
              user_id       : obj.field.user_id,
              mobile        : obj.field.mobile,
              borrow_id     : obj.field.borrow_id,
              name          : obj.field.name,
              tender_id     : obj.field.tender_id,
              company       : obj.field.company,
              t_user_id     : obj.field.t_user_id,
              t_mobile      : obj.field.t_mobile,
              start         : obj.field.start,
              end           : obj.field.end
            }
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'appeal')
          {
            xadmin.open('判定' , '/user/Debt/DebtInfo?id='+data.id+'&deal_type='+data.deal_type+'&operation=2');
          }
        });

      });

      function daochu() {
        layer.confirm('确认要根据当前筛选条件导出吗？',
        function(index) {
          layer.close(index);
          var deal_type     = $('[name="deal_type"]:checked').val();
          var status        = $("#status").val();
          var debt_src      = $("#debt_src").val();
          var serial_number = $("#serial_number").val();
          var user_id       = $("#user_id").val();
          var mobile        = $("#mobile").val();
          var borrow_id     = $("#borrow_id").val();
          var name          = $("#name").val();
          var tender_id     = $("#tender_id").val();
          var company       = $("#company").val();
          var t_user_id     = $("#t_user_id").val();
          var t_mobile      = $("#t_mobile").val();
          var start         = $("#start").val();
          var end           = $("#end").val();
          window.open("/user/Debt/DebtListExcel?deal_type="+deal_type+"&status="+status+"&debt_src="+debt_src+"&serial_number="+serial_number+"&user_id="+user_id+"&name="+name+"&mobile="+mobile+"&borrow_id="+borrow_id+"&tender_id="+tender_id+"&company="+company+"&start="+start+"&end="+end+"&t_user_id="+t_user_id+"&t_mobile="+t_mobile , "_blank");
        });
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" > 
        <!-- <button class="layui-btn" lay-event="daochu"><i class="layui-icon">&#xe60a;</i>导出</button> -->
      </div > 
    </script>
    <script type="text/html" id="operate">
      {{# if(d.appeal_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="判定" lay-event="appeal"><i class="layui-icon">&#xe642;</i>判定</button>
      {{# } else { }}
      <button class="layui-btn layui-btn-disabled layui-btn-xs" title="判定"><i class="layui-icon">&#xe642;</i>判定</button>
      {{# } }}
    </script>
</html>