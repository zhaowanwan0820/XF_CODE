<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>统计</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8" />
        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
        <!-- <script src="https://cdn.staticfile.org/echarts/4.3.0/echarts.min.js"></script> -->
        <script type="text/javascript" src="<{$CONST.layuiPath}>/extend/echarts.min.js"></script>
        <script type="text/javascript" src="<{$CONST.layuiPath}>/extend/china.js"></script>


        <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
        <!--[if lt IE 9]>
            <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
            <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    
        <style>
            
        .layui-card-body{display: flex; justify-content: center; flex-direction: column; text-align: center; height: 400px;}
        </style>
        <body>
            <div class="x-nav">
                <span class="layui-breadcrumb">
                    <a href="">定向收购</a>
                    <a>
                        <cite>数据统计</cite></a>
                </span>
                <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
                    <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
                </a>
            </div>

         

            <div class="layui-fluid">
                
                
                <div class="layui-row layui-col-space10">
                  
                    <div class="layui-col-xs6 layui-col-md4">
                        <!-- 填充内容 -->
                        <div class="layui-card">
                            <div class="layui-card-header">实时资金额度统计</div>
                            <div class="layui-card-body" id="amount" ></div>
                        </div>
                    </div>

                  <div class="layui-col-xs6 layui-col-md4">
                    <div class="layui-card">
                      <div class="layui-card-header">实时债权统计</div>
                      <div class="layui-card-body" id="debt"></div>
                    </div>
                  </div>

                  <div class="layui-col-xs6 layui-col-md4">
                    <div class="layui-card">
                      <div class="layui-card-header">实时人数统计</div>
                      <div class="layui-card-body" id="people"></div>
                    </div>
                  </div>


                <div class="layui-col-xs6 layui-col-md12">
                
                    <div class="layui-card">
                        <div class="layui-card-header">每日数据记录（每日零点更新）</div>
                        <table id="daily_list">     
                        </table>
                    
                    </div>
                </div>
  
        </div>
    </div>
 <script type="text/javascript">

    layui.use(['form', 'layer', 'laydate','table','laypage'], function () {
        var $ = layui.$
        // 总览
        var myChart1 = echarts.init(document.getElementById('amount'));
        $.ajax({
            url: '/debtMarket/ExclusivePurchase/getAmountData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据
                var option = {
                    title: {
                        
                        text: '总资金额度',
                        subtext: res.data.total+'（元）',
                        left: 'left'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },
                    color : ['#f0cf5e','#ec968b','#b0e77f',],
                    legend: {
                        left:100,
                        data: ['冻结中','剩余额度','交易完成']
                    },
                    series: [
                        {
                            name: '总览',
                            type: 'pie',
                            
                            radius: '55%',
                            center: ['55%', '60%'],
                            label: {
                                formatter: '{b}' 
                            },
                            data: res.data.detail,
                            emphasis: {
                                itemStyle: {
                                    shadowBlur: 10,
                                    shadowOffsetX: 0,
                                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                                }
                            }
                        }
                    ]
                };    
               // 使用刚指定的配置项和数据显示图表。
              myChart1.setOption(option);
            }
              
        });
        
        // 总览
        var myChart2 = echarts.init(document.getElementById('debt'));
        $.ajax({
            url: '/debtMarket/ExclusivePurchase/getDebtData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据
                var option = {
                    title: {
                        
                        text: '总债权金额',
                        subtext: res.data.total+'（元）',
                        left: 'left'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },
                    // color : ['#f0cf5e','#ec968b','#b0e77f',],
                    legend: {
                        left:100,
                        data: ['待签约','待付款','交易完成']
                    },
                    series: [
                        {
                            name: '总债权金额',
                            type: 'pie',
                            
                            radius: '55%',
                            center: ['55%', '60%'],
                            label: {
                                formatter: '{b}' 
                            },
                            data: res.data.detail,
                            emphasis: {
                                itemStyle: {
                                    shadowBlur: 10,
                                    shadowOffsetX: 0,
                                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                                }
                            }
                        }
                    ]
                };    
               // 使用刚指定的配置项和数据显示图表。
              myChart2.setOption(option);
            }
        });
    

        // people
        var myChart3 = echarts.init(document.getElementById('people'));
        $.ajax({
            url: '/debtMarket/ExclusivePurchase/GetPeopleData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据
                var option = {
                    
                    title: {
                        text: '总出借人数',
                        subtext: res.data.total+'人',
                        left: 'left'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },
                    color : ['#bbbbbb','#e886e7','#8ea5f1','#c1a5f1'],

                    legend: {
                        left:100,

                        data: ['待签约','待付款','已出清','失败']
                    },
                    series: [
                        {
                            name: '总出借人数',
                            type: 'pie',
                            radius: '55%',
                            center: ['50%', '60%'],
                            label: {
                                formatter: '{b}' 
                            },
                            data: res.data.detail,
                            emphasis: {
                                itemStyle: {
                                    shadowBlur: 10,
                                    shadowOffsetX: 0,
                                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                                }
                            }
                        }
                    ]
                };    
                // 使用刚指定的配置项和数据显示图表。
                myChart3.setOption(option);
            }
        });
        var laydate = layui.laydate;
        var form = layui.form;
        var table = layui.table;
        var laypage = layui.laypage;

        var data = [
            {field: 'handle_time', title: '日期',width: 120},
            {field: 'total_quotas', title: '总额度（元）',width: 140},
            {field: 'frozen_quotas', title: '冻结中额度（元）',width: 140},
            {field: 'surplus_quotas', title: '剩余额度（元）',width: 140},
            {field: 'finish_quotas', title: '交易完成额度（元）',width: 140},
            {field: 'total_debt', title: '总债权金额（元）',width: 140},
            {field: 'be_sign_debt', title: '待签约债权（元）',width: 140},
            {field: 'be_paid_debt', title: '待付款债权（元）',width: 140},
            {field: 'finish_debt', title: '已交易完成债权（元）',width: 160},
            {field: 'total_user', title: '总出借人数',width: 120},
            {field: 'be_sign_user', title: '待签约人数',width: 120},
            {field: 'be_paid_user', title: '待付款人数',width: 120},
            {field: 'finish_user', title: '已出清人数',width: 120},
            {field: 'fail_user', title: '交易失败人数',width: 120},
        ];

        table.render({
            elem: '#daily_list',
            // toolbar: '#toolbar',
            // defaultToolbar: [],
            totalRow:false,
            page: true,
            jump:false,
            limit: 8,
            limits: [8,10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            cols: [data],
            url: '/debtMarket/ExclusivePurchase/GetStatisticsList',
            method: 'post',
            response:
                {
                    statusName: 'code',
                    statusCode: 0,
                    msgName: 'info',
                    countName: 'countNum',
                    dataName: 'list'
                }
        });

    });
            
    </script>
        
</body>
  

</html>