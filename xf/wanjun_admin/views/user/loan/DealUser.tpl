<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>债权列表</title>
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
                    <cite>借款人列表</cite></a>
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
                                          <label class="layui-form-label">借款方ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="id"  id="id" autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款方名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="real_name"  id="real_name"  autocomplete="off" class="layui-input">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" id="mobile"   autocomplete="off" class="layui-input">
                                          </div>
                                        </div>
                                          <div class="layui-inline">
                                              <label class="layui-form-label">证件号</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="idno"  id="idno"  autocomplete="off" class="layui-input">
                                              </div>
                                          </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款方类型</label>
                                          <div class="layui-input-inline">
                                            <select name="user_type" id="user_type" lay-search="">
                                              <option value="">全部</option>
                                              <option value="0">个人</option>
                                              <option value="1">企业</option>
                                            </select>
                                          </div>
                                        </div>
                                          <div class="layui-inline">
                                              <label class="layui-form-label">借款方归属地(省)</label>
                                              <div class="layui-input-inline">
                                                  <select name="province_name" id="province_name" lay-search="">
                                                      <option value="">全部</option>
                                                      <{foreach $province_name_list as $key=>$val}>
                                                      <option value='<{$key}>'><{$val}></option>
                                                      <{/foreach}>
                                                  </select>
                                              </div>
                                          </div>
                                          <div class="layui-inline">
                                              <label class="layui-form-label">银行卡号</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="bankcard" id="bankcard" autocomplete="off" class="layui-input">
                                              </div>
                                          </div>

                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $dealUser2Excel == 1 }>
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
              title : '借款方ID',
              width : 100
            },
            {
              field : 'real_name',
              title : '借款方名称',
                width : 210
            },
            {
              field : 'mobile',
              title : '手机号',
                width : 140
            } ,
              {
                  field : 'idno',
                  title : '证件号',
                  width : 170
              },
              {
                  field : 'user_type_name',
                  title : '借款方类型',
                  width : 100
              },
              {
                  field : 'province_name_name',
                  title : '归属地(省)',
                  width : 100
              },
              {
                  field : 'card_address',
                  title : '详细地址',
                  width : 200
              },
              {
                  field : 'bankcard',
                  title : '银行卡号',
                  width : 170
              },
              {
                  field : 'name',
                  title : '开户行',
                  width : 110
              },
              {
                  field : 'borrow_amount',
                  title : '原普惠借款本金',
                  align : 'right',
                  width : 130
              },
              {
                  field : 'ph_wait_capital',
                  title : '原普惠待还本金',
                  align : 'right',
                  width : 130
              },
              {
                  field : 'sq_amount',
                  title : '投资人置换后持有金额',
                  align : 'right',
                  width : 150
              },
              {
                  field : 'wj_sq_amount',
                  title : '万峻持有金额',
                  align : 'right',
                  width : 140
              },
              {
                  field : 'wait_capital',
                  title : '万峻持有授权金额',
                  align : 'right',
                  width : 140
              }
          ]],
          url      : '/user/Loan/DealUser',
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
              id : obj.field.id,
              real_name : obj.field.real_name,
              mobile : obj.field.mobile,
              idno : obj.field.idno,
              bankcard : obj.field.bankcard,
              user_type : obj.field.user_type,
              province_name : obj.field.province_name,
            },
              page:{curr:1}
          });
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

            if (layEvent === 'send_sms') {
                send_sms(data);
            }
        });

      });
      function DealUser2Excel()
      {
          var id = $("#id").val();
          var real_name = $("#real_name").val();
          var mobile = $("#mobile").val();
          var idno = $("#idno").val();
          var bankcard = $("#bankcard").val();
          var user_type = $("#user_type").val();
          var province_name = $("#province_name").val();
          if (province_name == '' && user_type == '' && bankcard == '' && idno == '' && mobile == '' && real_name == '' && id == '') {
              layer.msg('请输入至少一个查询条件');
          } else {
              layer.confirm('确认要根据当前筛选条件导出吗？',
                  function(index) {
                      layer.close(index);
                      window.open("/user/Loan/DealUser2Excel?mobile="+mobile+"&province_name="+province_name+"&user_type="+user_type+"&bankcard="+bankcard+"&idno="+idno+"&real_name="+real_name+"&id="+id , "_blank");
                  });
          }
      }


    </script>
</html>