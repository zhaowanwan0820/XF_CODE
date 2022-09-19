<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>在途出借人明细(尊享+普惠)</title>
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
                <a href="">网信业务数据</a>
                <a>
                    <cite>在途出借人明细(尊享+普惠)</cite></a>
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
                            <h2 class="layui-colla-title">条件筛选（实时筛选）<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" action="">
                                    <div class="layui-form-item">

                                        <div class="layui-inline">
                                          <label class="layui-form-label">所属平台</label>
                                          <div class="layui-input-inline">
                                            <select name="platform" id="platform" lay-search="">
                                              <option value="1">尊享</option>
                                              <option value="2">普惠</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" id="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_mobile" id="user_mobile" placeholder="请输入用户手机号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户证件号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_idno" id="user_idno" placeholder="请输入用户证件号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $DealLoadBYUser2Excel == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="DealLoadBYUser2Excel()">导出</button>
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
                                            <option value="1">上传用户ID</option>
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
                                        <!-- <button class="layui-btn" lay-submit="" lay-filter="sreach_b">立即搜索</button> -->
                                        <button type="button" class="layui-btn layui-btn-primary" onclick="reset_condition()">重置</button>
                                        <{if $DealLoadBYUser2Excel == 1 }>
                                        <button type="button" class="layui-btn layui-btn-danger" onclick="DealLoadBYUser2ExcelA()">导出</button>
                                        <{/if}>
                                      </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-card-body">
                  <button class="layui-btn layui-btn-danger"   lay-submit="" lay-filter="download_contract">导出合同</button>
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
              title : '用户ID',
              fixed : 'left',
              width : 100
            },
            {
              field : 'real_name',
              title : '用户姓名',
              width : 100
            },
            // {
            //   field : 'mobile_b',
            //   title : '用户手机号',
            //   width : 120
            // },
            // {
            //   field : 'idno',
            //   title : '用户证件号',
            //   width : 150
            // },
            {
              field : 'group_name',
              title : '用户所属组别名称',
              width : 130
            },
            {
              field : 'sex',
              title : '性别',
              width : 100
            },
            {
              field : 'byear',
              title : '年龄',
              width : 100
            },
            {
              field : 'mobile_area',
              title : '手机号所在地',
              width : 120
            },
            {
              field : 'refer_user_id',
              title : '服务人ID',
              width : 100
            },
            {
              field : 'refer_name',
              title : '服务人姓名',
              width : 100
            },
            {
              field : 'short_alias',
              title : '服务人邀请码',
              width : 120
            },
            {
              field : 'refer_group_name',
              title : '服务人所属组别名称',
              width : 140
            },
            {
              field : 'money',
              title : '账户余额',
              width : 120,
              align : 'right'
            },
            {
              field : 'lock_money',
              title : '账户冻结金额',
              width : 120,
              align : 'right'
            },
            {
              field : 'recharge_money',
              title : '历史充值金额',
              width : 120,
              align : 'right'
            },
            {
              field : 'withdraw_money',
              title : '历史提现金额',
              width : 120,
              align : 'right'
            },
            {
              field : 'wait_capital',
              title : '在途本金(含冻结)',
              width : 120,
              align : 'right'
            },
              {
                  field : 'no_frozen_wait_capital',
                  title : '在途本金(排除冻结)',
                  width : 120,
                  align : 'right'
              },
            {
              field : 'wait_interest',
              title : '在途利息',
              width : 120,
              align : 'right'
            },
            {
              field : 'revenue',
              title : '历史累计收益额',
              width : 120,
              align : 'right'
            }
          ]],
          url      : '/user/Loan/DealLoadBYUser',
          method   : 'post',
          where    : {platform : 1},
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
              platform     : obj.field.platform,
              user_id      : obj.field.user_id,
              user_mobile  : obj.field.user_mobile,
              user_idno    : obj.field.user_idno,
              condition_id : ''
            },
            page:{curr:1}
          });
          return false;
        });

        form.on('submit(download_contract)', function (obj) {
            xadmin.open('导出合同', '/user/Contract/getUserInfo',900,380);
            return false;
        });


        form.on('submit(sreach_a)', function(obj){

          if (obj.field.type == 0) {
            layer.msg('请选择查询类型');
          } else if (obj.field.type == 1) {
            xadmin.open('通过上传用户ID查询','/user/Loan/addDealLoadBYUserCondition?type=1');
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
                platform     : '',
                user_id      : '',
                user_mobile  : '',
                user_idno    : '',
                condition_id : obj.field.condition_id
              },
              page:{curr:1}
            });
          }
          return false;
        });

      });

      function reset_condition() {
        $("#condition_id").val('');
        $("#condition_name").val('');
      }

      function show_condition(id , name) {
        $("#condition_id").val(id);
        $("#condition_name").val(name);
      }

      function DealLoadBYUser2Excel()
      {
        var platform    = $("#platform").val();
        var user_id     = $("#user_id").val();
        var user_mobile = $("#user_mobile").val();
        var user_idno   = $("#user_idno").val();
        if (user_id == '' && user_mobile == '' && user_idno == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/DealLoadBYUser2Excel?platform="+platform+"&user_id="+user_id+"&user_mobile="+user_mobile+"&user_idno="+user_idno , "_blank");
          });
        }
      }

      function DealLoadBYUser2ExcelA()
      {
        var condition_id = $("#condition_id").val();
        if (condition_id == '') {
          layer.msg('缺少查询条件，请先上传文件！');
        } else {
          layer.confirm('确认要根据当前上传的批量条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/DealLoadBYUser2Excel?condition_id="+condition_id , "_blank");
            reset_condition();
          });
        }
      }
    </script>
    <script type="text/html" id="operate">
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" > 
      </div > 
    </script>
</html>