<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>出清统计</title>
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
                <a href="">出清管理</a>
                <a>
                    <cite>出清统计</cite></a>
            </span>
            <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
                <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
            </a>
        </div>
        <div class="layui-fluid">
            <div class="layui-row layui-col-space15">

                <div class="layui-col-md12" style="overflow-x:auto;">

                    <div class="layui-card">
                        <div class="layui-card-body">
                            <div class="layui-collapse" lay-filter="test">
                                <div class="layui-colla-item">
                                <h2 class="layui-colla-title">数据总览<i class="layui-icon layui-colla-icon"></i></h2>
                                    <div class="layui-colla-content layui-show" style="height:90px;">
                                        <h3><b>借款人情况总览</b></h3> 
                                        <div class="clear_div">
                                            <table  id="clear_table">
                                                <tr>
                                                    <td>总借款人数：<{$total}>人</td>
                                                    <td>已出清人数：<{$yes_clear}>人</td>
                                                    <td>未出清人数：<{$no_clear}>人</td>
                                                    <td>未分配人数：<{$no_distribution}>人</td>
                                                </tr>
                                            </table>
                                            <ul class="ffqk">
                                                <{foreach $distribution_detail as $key => $val}>
                                                <li><{$val['name']}>分配人数：<{$val['value']}>人</li>
                                                <{/foreach}>
                                            </ul>
                                        </div>
                                    </div>
                                    <!--div class="layui-colla-content layui-show" style="height:70px;">
                                        <h3><b>借款人完全出清情况总览</b></h3>
                                        <div class="clear_div">
                                            <table  id="clear_table">
                                                <tr>
                                                    <td>总借款人数：<{$total}>人</td>
                                                    <td>未出清人数：<{$user_clear['no_clear']}>人</td>
                                                    <td>已出清人数：<{$total-$user_clear['no_clear']}>人</td>
                                                </tr>
                                                <{foreach $user_clear['cs_clear'] as $key => $val}>
                                                <!tr>
                                                    <td><{$key}>-线下还款出清：<{$val['rp']}>人</td>
                                                    <td><{$key}>-补录凭证出清：<{$val['pz']}>人</td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <{/foreach}>
                                            </table>
                                        </div>

                                    </div-->

                                    <div class="layui-colla-content layui-show" style="height:auto;">
                                        <h3><b>债权数量总览</b></h3>
                                        <div class="clear_div">
                                            <table  id="clear_table">
                                                <tr>
                                                    <td>待还债权：<{number_format($debt_clear['no_clear'], 2 , '.' , ',')}>元</td>
                                                    <td>系统划扣累计回收债权：<{number_format($debt_clear['yop_clear'], 2 , '.' , ',')}>元</td>
                                                    <!--td>管理人员-线下还款出清：<{$debt_clear['Repay_clear']}>元</td-->
                                                    <!--td>管理人员-补录凭证出清：<{$debt_clear['RepaySlip_clear']}>元</td-->
                                                </tr>
                                                <{foreach $debt_clear['cs_clear'] as $key => $val}>
                                                <tr>
                                                    <td><{$key}>-线下还款回收债权：<{number_format($val['rp_debt'], 2 , '.' , ',')}>元</td>
                                                    <td><{$key}>-补录凭证回收债权：<{number_format($val['pz_debt'], 2 , '.' , ',')}>元</td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <{/foreach}>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="layui-colla-content layui-show" style="height:115px;">
                                        <h3><b>借款人回款情况总览</b></h3>
                                        <div class="clear_div">
                                            <div>系统划扣回款总金额：<{number_format($debt_clear['yop_clear'], 2 , '.' , ',')}>元</div>
                                            <div>线下还款总金额：<{number_format($debt_clear['repay_total'], 2 , '.' , ',')}>元</div>
                                            <div>
                                                <ul class="ffqk">
                                                    <{foreach $debt_clear['cs_clear'] as $key => $val}>
                                                    <li><{$key}>-线下还款总金额：<{number_format($val['rp_amount'], 2 , '.' , ',')}>元</li>
                                                    <{/foreach}>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                              </div>
                            </div>

                            <{foreach $debt_clear['cs_clear'] as $key => $val}>
                            <div class="layui-collapse" lay-filter="test" style="margin-top: 20px;">
                                <div class="layui-colla-item">
                                    <h2 class="layui-colla-title"><{$key}><i class="layui-icon layui-colla-icon"></i></h2>
                                    <div class="layui-colla-content layui-show" style="height:70px;">
                                        <h3><b>已分配借款人情况</b></h3>
                                        <div class="clear_div">
                                            <ul class="ffqk">
                                                <li>未线下还款人数：<{$val['no_paid_user_num']}>人</li>
                                                <li>线下还款人数：<{$val['paid_user_num']}>人</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="layui-colla-content layui-show" style="height:70px;">
                                        <h3><b>出清债权情况</b></h3>
                                        <div class="clear_div">
                                            <ul class="ffqk">
                                                <li>待还债权：<{number_format($val['wait_capital'], 2 , '.' , ',')}>元</li>
                                                <li>线下还款回收债权：<{number_format($val['rp_debt'], 2 , '.' , ',')}>元</li>
                                                <li>补录凭证回收债权：<{number_format($val['pz_debt'], 2 , '.' , ',')}>元</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="layui-colla-content layui-show" style="height:70px;">
                                        <h3><b>金额情况</b></h3>
                                        <div class="clear_div">
                                            <ul class="ffqk">
                                                <li>线下还款金额：<{number_format($val['rp_amount'], 2 , '.' , ',')}>元</li>
                                                <li>补录凭证金额：<{number_format($val['pz_amount'], 2 , '.' , ',')}>元</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <{/foreach}>
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
      if (sql == '') {
        sql = $("#sql").val();
      }
      var db = $(".db:checked").val();
      if (db != 1 && db != 2 && db != 3 && db != 4) {
        layer.alert('请正确选择数据库');
      } else {
        db = parseInt(db)-1;
        var loading = layer.load(2, {
            shade: [0.3],
            time: 3600000
        });
        $("#body").prop('style','width: 0px;');
        $("#title").html('');
        $("#data").html('');
        $.ajax({
          url:"/user/SqlQuery/Index",
          type:"post",
          data:{'db':db,'sql':sql},
          dataType:'json',
          success:function(res){
            if (res['code'] == 0) {
              var width = 0;
              var title = '';
              for (var t in res['data'][0]) {
                width = width + 150;
                title += '<th>'+t+'</th>';
              }
              $("#body").prop('style','width:'+width+'px;');
              $("#title").html(title);
              var data = res['data'];
              var str  = '';
              for (var k in data) {
                str += '<tr>';
                for (var v in data[k]) {
                  str += '<td>'+data[k][v]+'</td>';
                }
                str += '</tr>';
              }
              $("#data").html(str);
            } else {
              $("#body").prop('style','width: 0px;');
              $("#title").html('');
              $("#data").html('');
              layer.alert(res['info']);
            }
            layer.close(loading);
          }
        });
      }
    }
    </script>
<style>
    .ffqk{
        width: 100%;
    }
    .ffqk li{
        float: left;width: 310px;
    }
    .clear_div{
        padding-top: 10px;
    }
    #clear_table td{
        padding: 0px;
        width: 310px;
    }
</style>
</html>