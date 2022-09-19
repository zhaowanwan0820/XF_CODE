<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>出借人统计</title>
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
                    <a href="">出清管理</a>
                    <a>
                        <cite>出清统计</cite></a>
                </span>
                <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
                    <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
                </a>
            </div>

         

            <div class="layui-fluid">
                
                
                <div class="layui-row layui-col-space10">
                  
                    <div class="layui-col-xs6 layui-col-md6">
                        <!-- 填充内容 -->
                        <div class="layui-card">
                            <div class="layui-card-header">借款人人数</div>
                            <div class="layui-card-body" id="data_borrower" ></div>
                        </div>
                    </div>

                  <div class="layui-col-xs6 layui-col-md6">
                    <div class="layui-card">
                      <div class="layui-card-header">已回款人数</div>
                      <div class="layui-card-body" id="data_repay"></div>
                    </div>
                  </div>

                  <div class="layui-col-xs6 layui-col-md6">
                    <div class="layui-card">
                      <div class="layui-card-header">债权金额</div>
                      <div class="layui-card-body" id="data_debt"></div>
                    </div>
                  </div>

                  <div class="layui-col-xs6 layui-col-md6">
                    <div class="layui-card">
                      <div class="layui-card-header">已回款金额</div>
                      <div class="layui-card-body" id="data_repay_amount"></div>
                    </div>
                  </div>


                  <{foreach $csCompanyList as $key => $val}>
                  <div class="layui-col-xs12 layui-col-md12"> 
                    <div class="x-nav" style="font-size: 18px;" ><{$val['name']}></div>

            
                    <div class="layui-col-xs6 layui-col-md6">
                      <div class="layui-card">
                        <div class="layui-card-header">人数统计</div>
                        <div class="layui-card-body" id="<{$val['user_statistics']}>"></div>
                      </div>
                    </div>
  
                   
                    <div class="layui-col-xs6 layui-col-md6">
                      <div class="layui-card">
                        <div class="layui-card-header">债权金额统计</div>
                        <div class="layui-card-body" id="<{$val['debt_statistics']}>"></div>
                      </div>
                    </div>
  
  
                </div>
                  
                  <{/foreach}>

  
        </div>
    </div>
 <script type="text/javascript">

    layui.use(['form', 'layer', 'laydate','table','laypage'], function () {
        var $ = layui.$
        // 总览
       
        $.ajax({
            url: '/borrower/dealOrder/GetClearData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                //  指定图表的配置项和数据
                var myChartDataBorrower = echarts.init(document.getElementById('data_borrower'));
                var option = {
                    title: {
                        
                        text: '总人数',
                        subtext: res.data.borrower.total+'人',
                        left: 'left'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },
                    legend: {
                        
                        top: 'bottom',
                        data: res.data.borrower.name_list
                    },
                    series: [
                        {
                            name: '借款人人数',
                            type: 'pie',
                            
                            radius: '55%',
                            center: ['55%', '60%'],
                            label: {
                                formatter: '{b}' 
                            },
                            data: res.data.borrower.detail,
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
                myChartDataBorrower.setOption(option);

                // 已回款
                 var myChartDataRepay = echarts.init(document.getElementById('data_repay'));

                 var option = {
                    title: {
                        
                        text: '总人数',
                        subtext: res.data.repay.total+'人',
                        left: 'left'
                        
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },

                    legend: {
                        top: 'bottom',
                        data: res.data.repay.name_list                    },
                    series: [
                        {
                            name: '已回款人数',
                            type: 'pie',
                            radius: '55%',
                            center: ['50%', '60%'],
                            label: {
                                formatter: '{b}' 
                            },
                            data: res.data.repay.detail,
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
                myChartDataRepay.setOption(option);


                // 债权金额
                var myChartDataDebt = echarts.init(document.getElementById('data_debt'));

                var option = {
                    title: {
                        
                        text: '总金额',
                        subtext: res.data.debt.total+'元',
                        left: 'left'
                        
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },

                    legend: {
                        top: 'bottom',
                        data: res.data.debt.name_list                    },
                    series: [
                        {
                            name: '债权金额',
                            type: 'pie',
                            radius: '55%',
                            center: ['50%', '60%'],
                            label: {
                                formatter: '{b}' 
                            },
                            data: res.data.debt.detail,
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
                myChartDataDebt.setOption(option);

                // 已回款金额
                var myChartRepayAmount = echarts.init(document.getElementById('data_repay_amount'));

                var option = {
                    title: {
                        
                        text: '总金额',
                        subtext: res.data.repay_amount.total+'元',
                        left: 'left'
                        
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },

                    legend: {
                        top: 'bottom',
                        data: res.data.repay_amount.name_list                    },
                    series: [
                        {
                            name: '已回款金额',
                            type: 'pie',
                            radius: '55%',
                            center: ['50%', '60%'],
                            label: {
                                formatter: '{b}' 
                            },
                            data: res.data.repay_amount.detail,
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
                myChartRepayAmount.setOption(option);

                res.data.csCompanyList.forEach(function (params) {
               
                    // 债权金额
                    var myChartDataDebt = echarts.init(document.getElementById(params.user_statistics));

                    var option = {
                        title: {
                            
                            text: '总人数',
                            subtext: params.total_user+'人',
                            left: 'left'
                            
                        },
                        tooltip: {
                            trigger: 'item',
                            formatter: '{a} <br/>{b} : {c} ({d}%)'
                        },

                        legend: {
                            top: 'bottom',
                            data: ['已出清','回款未出清','未还款']                    
                        },
                        series: [
                            {
                                name: '人数统计',
                                type: 'pie',
                                radius: '55%',
                                center: ['50%', '60%'],
                                label: {
                                    formatter: '{b}' 
                                },
                                data: [
                                    {name:'已出清',value:params.clear_user},
                                    {name:'回款未出清',value:params.no_clear},
                                    {name:'未还款',value:params.no_repay}
                                ],
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
                    myChartDataDebt.setOption(option);

                    // 已回款金额
                    var myChartRepayAmount = echarts.init(document.getElementById(params.debt_statistics));

                    var option = {
                        title: {
                            
                            text: '总金额',
                            subtext: params.total_amount+'元',
                            left: 'left'
                            
                        },
                        tooltip: {
                            trigger: 'item',
                            formatter: '{a} <br/>{b} : {c} ({d}%)'
                        },

                        legend: {
                            top: 'bottom',
                            data: ['已回款','未回款']                  
                        },
                        series: [
                            {
                                name: '已回款金额',
                                type: 'pie',
                                radius: '55%',
                                center: ['50%', '60%'],
                                label: {
                                    formatter: '{b}' 
                                },
                                data: [
                                    {name:'已回款',value:params.repay_debt},
                                    {name:'未回款',value:params.no_repay_debt}
                                ],
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
                    myChartRepayAmount.setOption(option);

                })
                // for(i=1;i<res.data.csCompanyList.length;i++){


                // }
               
            }
        });
        

        
    


    });
            
    </script>
        
</body>
  

</html>