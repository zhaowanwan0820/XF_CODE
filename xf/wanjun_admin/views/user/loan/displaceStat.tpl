<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>数据看板</title>
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
                <a href="">数据统计</a>
                <a>
                    <cite>数据看板</cite></a>
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
                                <h2 class="layui-colla-title">出借人数据<i class="layui-icon layui-colla-icon"></i></h2>
                                    <div class="layui-colla-content layui-show" style="height:80px;">
                                        <div class="clear_div">
                                            <table  id="clear_table">
                                                <tr>
                                                    <td>持有在途债权总人数：<{$total_user}>人</td>
                                                    <td>法大大签约置换人数：<{$fdd_sign_num}>人</td>
                                                    <td>用户点击确认签约人数：<{$sub_sign_num}>人</td>
                                                </tr>
                                                <tr>
                                                    <td>用户其他方式签约人数：<{$other_sign_num}>人</td>
                                                    <td>系统批量操作人数：<{$system_sign_num}>人</td>
                                                    <td></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                              </div>
                            </div>
                            <div class="layui-collapse" lay-filter="test" style="margin-top: 20px;">
                                <div class="layui-colla-item">
                                    <h2 class="layui-colla-title">相关金额数据<i class="layui-icon layui-colla-icon"></i></h2>
                                    <div class="layui-colla-content layui-show" style="height:180px;">
                                        <div class="clear_div">
                                            <table  id="clear_table">
                                                <tr>
                                                    <td>在途合计金额：<{$capital_total}>元</td>
                                                    <td> </td>
                                                </tr>
                                                <tr>
                                                    <td>在途金额（排除万峻）：<{$no_wj_capital}>元</td>
                                                    <td> </td>
                                                </tr>
                                                <tr>
                                                    <td>万峻在途合计：<{$wj_capital}>元</td>
                                                    <td> </td>
                                                </tr>
                                                <tr>
                                                    <td>法大大签约置换在途金额：<{$fdd_displace_capital}>元</td>
                                                    <td> </td>
                                                </tr>
                                                <tr>
                                                    <td>非法大大签约置换在途金额：<{$no_fdd_displace_capital}>元</td>
                                                    <td> </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script>
    layui.use(['laydate', 'form']);
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
        padding: 5px;
        width: 310px;
    }
</style>
</html>