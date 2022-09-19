<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>出借人充提差</title>
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
                    <cite>出借人充提差</cite></a>
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
                                            <label class="layui-form-label">用户ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_id" id="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="mobile" id="mobile" placeholder="请输入用户手机号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">用户证件号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="idno" id="idno" placeholder="请输入用户证件号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">账户类型</label>
                                          <div class="layui-input-inline">
                                            <select name="user_purpose" id="user_purpose" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">借贷混合用户</option>
                                              <option value="2">投资户</option>
                                              <option value="3">融资户</option>
                                              <option value="4">咨询户</option>
                                              <option value="5">担保/代偿I户</option>
                                              <option value="6">渠道户</option>
                                              <option value="7">渠道虚拟户</option>
                                              <option value="8">资产收购户</option>
                                              <option value="9">担保/代偿II-b户</option>
                                              <option value="10">受托资产管理户</option>
                                              <option value="11">交易中心（所）</option>
                                              <option value="12">平台户</option>
                                              <option value="13">保证金户</option>
                                              <option value="14">支付户</option>
                                              <option value="15">投资券户</option>
                                              <option value="16">红包户</option>
                                              <option value="17">担保/代偿II-a户</option>
                                              <option value="18">放贷户</option>
                                              <option value="19">垫资户</option>
                                              <option value="20">管理户</option>
                                              <option value="21">商户账户</option>
                                              <option value="22">营销补贴户</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">在途平台</label>
                                          <div class="layui-input-inline">
                                            <select name="platform" id="platform" lay-search="">
                                              <option value="">全量出借人</option>
                                              <option value="1">全量在途出借人</option>
                                              <option value="2">尊享在途出借人</option>
                                              <option value="3">普惠(含智多新)在途出借人</option>
                                              <option value="4">普惠(不含智多新)在途出借人</option>
                                              <option value="5">智多新在途出借人</option>
                                            </select>
                                          </div>
                                        </div>

                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $RechargeWithdraw2Excel == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="RechargeWithdraw2ExcelA()">导出</button>
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
                                        <button class="layui-btn" lay-submit="" lay-filter="sreach_b">立即搜索</button>
                                        <button type="button" class="layui-btn layui-btn-primary" onclick="reset_condition()">重置</button>
                                        <{if $RechargeWithdraw2Excel == 1 }>
                                        <button type="button" class="layui-btn layui-btn-danger" onclick="RechargeWithdraw2ExcelB()">导出</button>
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
    
</script>
    <script>
      layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;
        laydate = layui.laydate;

        table.render({
          elem           : '#list',
          toolbar        : '<div><i class="iconfont" style="color:orange;">&#xe6b6;</i> 说明：用户范围为全量出借人，包括在普惠（无特殊说明为不含智多新）、智多新、尊享三个平台产品上有过投资行为的所有出借人。列表中“新增XXX”指逾期后新增。</div>',
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
              width : 100
            },
            {
              field : 'user_name',
              title : '会员名称',
              width : 100
            },
            {
              field : 'mobile',
              title : '手机号',
              width : 110
            },
            {
              field : 'idno',
              title : '证件号',
              width : 160
            },
            {
              field : 'real_name',
              title : '用户姓名',
              width : 200
            },
            {
              field : 'user_purpose',
              title : '账户类型',
              width : 120
            },
            {
              field : 'increase',
              title : '充值总额',
              width : 190,
              align : 'right'
            },
            {
              field : 'reduce',
              title : '提现总额',
              width : 190,
              align : 'right'
            },
            {
              field : 'increase_reduce',
              title : '充提差',
              width : 190,
              align : 'right'
            },
            {
              field : 'wait_capital',
              title : '在途本金总额(含冻结)',
              width : 190,
              align : 'right'
            },
              {
                  field : 'no_frozen_wait_capital',
                  title : '在途本金总额(排除冻结)',
                  width : 190,
                  align : 'right'
              },
            {
              field : 'wait_interest',
              title : '在途利息总额',
              width : 190,
              align : 'right'
            },{
                  field : 'zx_wait_capital_total',
                  title : '尊享在途本金(含冻结)',
                  width : 190,
                  align : 'right'
              },
            {
              field : 'no_frozen_zx_wait_capital',
              title : '尊享在途本金(排除冻结)',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_wait_capital',
              title : '——尊享历史在途本金',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_new_wait_capital',
              title : '——尊享新增在途本金',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_wait_interest',
              title : '尊享在途利息',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_money',
              title : '尊享账户余额',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_lock_money',
              title : '尊享冻结金额',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_increase',
              title : '尊享充值',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_recharge',
              title : '——尊享历史充值',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_buy_debt',
              title : '——尊享债转转入',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_reduce',
              title : '尊享提现',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_withdraw',
              title : '——尊享历史提现',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_exchange',
              title : '——尊享债权兑换',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_deduct',
              title : '——尊享债权划扣',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_repay',
              title : '——尊享线下还款',
              width : 190,
              align : 'right'
            },
            {
              field : 'zx_sell_debt',
              title : '——尊享债转转出',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_wait_capital_total',
              title : '普惠在途本金(含冻结)',
              width : 190,
              align : 'right'
            },
              {
                  field : 'no_frozen_ph_wait_capital',
                  title : '普惠在途本金(排除冻结)',
                  width : 190,
                  align : 'right'
              },
            {
              field : 'ph_wait_capital',
              title : '——普惠历史在途本金',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_new_wait_capital',
              title : '——普惠新增在途本金',
              width : 190,
              align : 'right'
            },
            {
              field : 'zdx_wait_capital_total',
              title : '智多新在途本金(含冻结)',
              width : 190,
              align : 'right'
            },
              {
                  field : 'no_frozen_ph_zdx_wait_capital',
                  title : '智多新在途本金(排除冻结)',
                  width : 190,
                  align : 'right'
              },
            {
              field : 'ph_zdx_wait_capital',
              title : '——智多新历史在途本金',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_zdx_new_wait_capital',
              title : '——智多新新增在途本金',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_wait_interest',
              title : '普惠(含智多新)在途利息',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_money',
              title : '普惠(含智多新）账户余额',
              width : 190,
              align : 'right'
            }, 
            {
              field : 'ph_lock_money',
              title : '普惠(含智多新)冻结金额',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_increase',
              title : '普惠(含智多新)充值',
              width : 190,
              align : 'right',
            },
            {
              field : 'ph_recharge',
              title : '——普惠(含智多新)历史充值',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_buy_debt',
              title : '——普惠债转转入',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_zdx_buy_debt',
              title : '——智多新债转转入',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_reduce',
              title : '普惠(含智多新)提现',
              width : 190,
              align : 'right',
            },
            {
              field : 'ph_withdraw',
              title : '——普惠(含智多新)历史提现',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_exchange',
              title : '——普惠债权兑换',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_zdx_exchange',
              title : '——智多新债权兑换',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_deduct',
              title : '——普惠债权划扣',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_zdx_deduct',
              title : '——智多新债权划扣',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_repay',
              title : '——普惠线下还款',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_zdx_repay',
              title : '——智多新线下还款',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_sell_debt',
              title : '——普惠债转转出',
              width : 190,
              align : 'right'
            },
            {
              field : 'ph_zdx_sell_debt',
              title : '——智多新债转转出',
              width : 190,
              align : 'right'
            },
          ]],
          url      : '/user/Loan/RechargeWithdraw',
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
              user_id      : obj.field.user_id,
              mobile       : obj.field.mobile,
              idno         : obj.field.idno,
              user_purpose : obj.field.user_purpose,
              platform     : obj.field.platform,
              condition_id : ''
            },
            page:{curr:1}
          });
          return false;
        });

        form.on('submit(sreach_a)', function(obj){

          if (obj.field.type == 0) {
            layer.msg('请选择查询类型');
          } else if (obj.field.type == 1) {
            xadmin.open('通过上传用户ID查询','/user/Loan/addRechargeWithdrawCondition?type=1');
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
                user_id      : '',
                mobile       : '',
                idno         : '',
                user_purpose : '',
                platform     : '',
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

      function RechargeWithdraw2ExcelA()
      {
        var user_id      = $("#user_id").val();
        var mobile       = $("#mobile").val();
        var idno         = $("#idno").val();
        var user_purpose = $("#user_purpose").val();
        var platform     = $("#platform").val();
        if (user_id == '' && mobile == '' && idno == '' && user_purpose == '' && platform == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/RechargeWithdraw2Excel?user_id="+user_id+"&mobile="+mobile+"&idno="+idno+"&user_purpose="+user_purpose+"&platform="+platform , "_blank");
          });
        }
      }

      function RechargeWithdraw2ExcelB()
      {
        var condition_id = $("#condition_id").val();
        if (condition_id == '') {
          layer.msg('缺少查询条件，请先上传文件！');
        } else {
          layer.confirm('确认要根据当前上传的批量条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/RechargeWithdraw2Excel?condition_id="+condition_id , "_blank");
            reset_condition();
          });
        }
      }
    </script>
    <script type="text/html" id="operate">
    </script>
</html>