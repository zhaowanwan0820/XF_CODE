<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>有解化债数据查询</title>
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
                <a href="">工场微金业务数据</a>
                <a>
                    <cite>有解化债数据查询</cite></a>
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
                                <h2 class="layui-colla-title">条件筛选（实时筛选）<i class="layui-icon layui-colla-icon"></i></h2>
                                <div class="layui-colla-content layui-show">
                                  <form class="layui-form" action="">

                                      <div class="layui-form-item">

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
                                              <option value="5">一键下车退回</option>
                                              <option value="6">权益兑换退回</option>
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
                                          <label class="layui-form-label">借款编号</label>
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
                                          <label class="layui-form-label">融资方名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="company" id="company" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">融资经办机构</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="advisory" id="advisory" autocomplete="off" class="layui-input">
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
                                          <{if $daochu_status == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="daochu()">导出</button>
                                          <{/if}>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                        </div>
                        <div class="layui-card-body">
                            <div class="layui-collapse" lay-filter="test">
                                <div class="layui-colla-item">
                                    <h2 class="layui-colla-title">批量条件上传<i class="layui-icon layui-colla-icon"></i></h2>
                                    <div class="layui-colla-content">
                                        <form class="layui-form" action="">
                                            <div class="layui-form-item">

                                              <div class="layui-inline">
                                                <label class="layui-form-label">查询类型</label>
                                                <div class="layui-input-inline">
                                                  <select name="type" lay-search="">
                                                    <option value="">请选择查询类型</option>
                                                    <option value="1">上传用户ID</option>
                                                    <option value="2">上传融资经办机构名称</option>
                                                    <option value="3">上传借款标题</option>
                                                  </select>
                                                </div>
                                              </div>

                                              <div class="layui-inline">
                                                <label class="layui-form-label">当前查询条件</label>
                                                <div class="layui-input-inline">
                                                  <input type="text" class="layui-input" id="condition_name" value="" readonly style="width: 400px">
                                                </div>
                                                <input type="hidden" name="condition_id" id="condition_id" value="">
                                              </div>

                                            </div>
                                            <div class="layui-form-item">
                                              <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="sreach_a">上传文件</button>
                                                <button class="layui-btn" lay-submit="" lay-filter="sreach_b">立即搜索</button>
                                                <button type="button" class="layui-btn layui-btn-primary" onclick="reset_condition()">重置</button>
                                                <{if $daochu_status == 1 }>
                                                <button type="button" class="layui-btn layui-btn-danger" onclick="DebtListExcel()">导出</button>
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
              title : '借款编号',
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
              field : 'deal_load_money',
              title : '投资金额',
              width : 150,
              align : 'right'
            },
            {
              field : 'money',
              title : '发起债转金额',
              width : 150,
              align : 'right'
            },
            {
              field : 'sold_money',
              title : '已转出金额',
              width : 150,
              align : 'right'
            },
            {
              field : 'discount',
              title : '折扣',
              width : 100,
              align : 'right'
            },
            {
              field : 'status',
              title : '转让状态',
              width : 150
            },
            {
              field : 'debt_src',
              title : '债转类型',
              width : 150
            },
            {
              field : 'contract_number',
              title : '债转合同编号',
              width : 250
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
            }
          ]],
          url      : '/user/XFDebt/DebtList',
          method   : 'post',
          where    : {deal_type : 3},
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
              status        : obj.field.status,
              debt_src      : obj.field.debt_src,
              serial_number : obj.field.serial_number,
              user_id       : obj.field.user_id,
              mobile        : obj.field.mobile,
              borrow_id     : obj.field.borrow_id,
              name          : obj.field.name,
              tender_id     : obj.field.tender_id,
              company       : obj.field.company,
              advisory      : obj.field.advisory,
              t_user_id     : obj.field.t_user_id,
              t_mobile      : obj.field.t_mobile,
              start         : obj.field.start,
              end           : obj.field.end,
              condition_id  : ''
            },
            page:{curr:1}
          });
          return false;
        });

        form.on('submit(sreach_a)', function(obj){

          if (obj.field.type == 0) {
            layer.msg('请选择查询类型');
          } else if (obj.field.type == 1) {
            xadmin.open('通过上传用户ID查询','/user/XFDebt/addDebtListCondition?type=1&deal_type=3');
          } else if (obj.field.type == 2) {
            xadmin.open('通过上传融资经办机构名称查询','/user/XFDebt/addDebtListCondition?type=2&deal_type=3');
          } else if (obj.field.type == 3) {
            xadmin.open('通过上传借款标题查询','/user/XFDebt/addDebtListCondition?type=3&deal_type=3');
          }
          return false;
        });

        form.on('submit(sreach_b)', function(obj){

          if (obj.field.condition_id == '') {
            layer.msg('缺少查询条件，请先上传文件！');
          } else {
            table.reload('list', {
              where    :
              {
                status        : '',
                debt_src      : '',
                serial_number : '',
                user_id       : '',
                mobile        : '',
                borrow_id     : '',
                name          : '',
                tender_id     : '',
                company       : '',
                advisory      : '',
                t_user_id     : '',
                t_mobile      : '',
                start         : '',
                end           : '',
                condition_id  : obj.field.condition_id
              },
              page:{curr:1}
            });
          }
          return false;
        });

      });

      function daochu() {
        var status        = $("#status").val();
        var debt_src      = $("#debt_src").val();
        var serial_number = $("#serial_number").val();
        var user_id       = $("#user_id").val();
        var mobile        = $("#mobile").val();
        var borrow_id     = $("#borrow_id").val();
        var name          = $("#name").val();
        var tender_id     = $("#tender_id").val();
        var company       = $("#company").val();
        var advisory      = $("#advisory").val();
        var t_user_id     = $("#t_user_id").val();
        var t_mobile      = $("#t_mobile").val();
        var start         = $("#start").val();
        var end           = $("#end").val();
        if (status == '' && debt_src == '' && serial_number == '' && user_id == '' && mobile == '' && borrow_id == '' && name == '' && tender_id == '' && company == '' && advisory == '' && t_user_id == '' && t_mobile == '' && start == '' && end == '') {
            layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/XFDebt/DebtListExcel?deal_type=3&status="+status+"&debt_src="+debt_src+"&serial_number="+serial_number+"&user_id="+user_id+"&name="+name+"&mobile="+mobile+"&borrow_id="+borrow_id+"&tender_id="+tender_id+"&company="+company+"&advisory="+advisory+"&start="+start+"&end="+end+"&t_user_id="+t_user_id+"&t_mobile="+t_mobile , "_blank");
          });
        }
      }

      function reset_condition() {
        $("#condition_id").val('');
        $("#condition_name").val('');
      }

      function show_condition(id , name) {
        $("#condition_id").val(id);
        $("#condition_name").val(name);
      }

      function DebtListExcel()
      {
        var condition_id = $("#condition_id").val();
        if (condition_id == '') {
          layer.msg('缺少查询条件，请先上传文件！');
        } else {
          layer.confirm('确认要根据当前上传的批量条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/XFDebt/DebtListExcel?deal_type=3&condition_id="+condition_id , "_blank");
            reset_condition();
          });
        }
      }
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
      </div > 
    </script>
    <script type="text/html" id="operate">
    </script>
</html>