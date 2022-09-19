<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>款项查询</title>
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
                <a href="">债转市场管理</a>
                <a>
                    <cite>款项查询</cite></a>
            </span>
            <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
                <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
            </a>
        </div>
        <div class="layui-fluid">
            <div class="layui-row layui-col-space15">

                <div class="layui-col-md12" style="overflow-x:auto;">

                    <div class="layui-card" style="height:700px">
                        <div class="layui-card-body">
                            <div class="layui-collapse" lay-filter="test">
                                <div class="layui-colla-item">
                                <h2 class="layui-colla-title">定向收购-受让人款项查询<i class="layui-icon layui-colla-icon"></i></h2>
                                <div class="layui-colla-content layui-show">
                                  <form class="layui-form">

                                      <div class="layui-form-item layui-block">
                                        <div class="layui-inline">
                                          <label class="layui-form-label" style="width: 250px">请选择受让方：</label>
                                          <div class="layui-input-inline" style="width: 300px">
                                              <select name="purchase_user_id" id="purchase_user_id" lay-verify="required" lay-search="">
                                                  <{foreach $user_arr as $k => $v}>
                                              <option value="<{$v['id']}>" <{if $user eq $v['id'] }>selected<{/if}>><{$v['name']}></option>
                                                  <{/foreach}>
                                              </select>
                                          </div>
                                        </div>
                                      </div>

                                      <div class="layui-form-item layui-block">
                                          <div class="layui-inline">
                                              <label class="layui-form-label" style="width: 250px">代付代发-可用余额(可付款余额)：</label>
                                              <div id="can_pay" class="layui-input-inline" style="width: 300px;margin-top:10px;">
                                                  0.00
                                              </div>
                                          </div>
                                      </div>

                                      <div class="layui-form-item layui-block">
                                          <div class="layui-inline">
                                              <label class="layui-form-label" style="width: 250px">可提现余额(商户账户余额)：</label>
                                              <div id="use_money"  class="layui-input-inline" style="width: 300px;margin-top:10px;">
0.00
                                              </div>
                                          </div>
                                      </div>
                                      
                                      <div class="layui-form-item" style="margin-left: 200px;">
                                        <div class="layui-input-block"  >
                                          <button type="button" class="layui-btn" lay-filter="demo1" onclick="change_sql()">一键查询</button>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                        </div>
                        <div class="layui-card-body" id="body">
                            <table class="layui-table">
                                <thead>
                                    <tr id="title">
                                    </tr>
                                </thead>
                                <tbody id="data">
                                </tbody>
                            </table>
                        </div>
                        <div class="layui-card-body ">
                            <div class="page">
                                <div>
                                    <{$pages}></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script>
    layui.use(['laydate', 'form']);

    function change_sql(sql = '') {

      var purchase_user_id = $("#purchase_user_id").val();
      if (purchase_user_id == '') {
        layer.alert('请正确选择受让方');
      } else {
        $("#can_pay").html('');
        $("#use_money").html('');
        $.ajax({
          url:"/user/XFDebt/FundInquiry",
          type:"post",
          data:{'purchase_user_id':purchase_user_id },
          dataType:'json',
          success:function(res){
            if (res['code'] == 0) {
                var data = res['data'];
                $("#can_pay").html(data['can_pay']);
                $("#use_money").html(data['use_money']);
            } else {
                $("#can_pay").html('0.00');
                $("#use_money").html('0.00');
                layer.alert(res['info']);
            }
            layer.close(loading);
          }
        });
      }
    }
    </script>
</html>