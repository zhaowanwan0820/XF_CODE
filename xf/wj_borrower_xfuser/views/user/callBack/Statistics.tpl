<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>借款人统计</title>
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
                    <a href="">借款人呼叫管理</a>
                    <a>
                        <cite>借款人统计</cite></a>
                </span>
                <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
                    <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
                </a>
            </div>

            <div  style="padding-top:15px;padding-left:15px;color: dimgray;" >
                除【在途本金区间人数统计】模块，均为实时数据
            </div>
            

            <div class="layui-fluid">
                
                
                <div class="layui-row layui-col-space10">
                  
                    <div class="layui-col-xs6 layui-col-md4">
                        <!-- 填充内容 -->
                        <div class="layui-card">
                            <div class="layui-card-header">总览</div>
                            <div class="layui-card-body" id="zonglan" ></div>
                        </div>
                    </div>

                  <div class="layui-col-xs6 layui-col-md4">
                    <div class="layui-card">
                      <div class="layui-card-header">失联人数分类统计</div>
                      <div class="layui-card-body" id="shilianfenlei"></div>
                    </div>
                  </div>

                  <div class="layui-col-xs6 layui-col-md4">
                    <div class="layui-card">
                      <div class="layui-card-header">男女比例</div>
                      <div class="layui-card-body" id="sex"></div>
                    </div>
                  </div>

                  <div class="layui-col-xs6 layui-col-md12">
                    <div class="layui-card">
                      <div class="layui-card-header">年龄分布</div>
                      <div class="layui-card-body" id='age'></div>
                    </div>
                  </div>
                  <div class="layui-col-xs6 layui-col-md12">
                    <div class="layui-card">
                      <div class="layui-card-header">问题类型分类统计</div>
                      <div class="layui-card-body" id='question'></div>
                    </div>
                  </div>
                  <div class="layui-col-xs6 layui-col-md12">
                    <div class="layui-card">
                      <div class="layui-card-header">在途本金区间人数统计(数据每日0时更新)</div>
                      <div class="layui-card-body" id='jiekuanjine'></div>
                    </div>
                  </div>
                  <div class="layui-col-xs6 layui-col-md12">
                    <div class="layui-card" >
                      <div class="layui-card-header">省级地域人数统计</div>
                      <div class="layui-card-body" >
                        <div class="layui-row layui-col-space15">
                            <div class="layui-col-sm4">
                                <table class="layui-table layuiadmin-page-table" lay-skin="line" id="list">
                                  <!-- <thead>
                                    <tr>
                                      <th>排名</th>
                                      <th>地区</th>
                                      <th>人数</th>
                                    </tr> 
                                  </thead>
                                  <tbody>
                                    <tr>
                                      <td>1</td>
                                      <td>浙江</td>
                                      <td>62310</td>
                                    </tr>
                                    <tr>
                                      <td>2</td>
                                      <td>上海</td>
                                      <td>59190</td>
                                    </tr>
                                    <tr>
                                      <td>3</td>
                                      <td>广东</td>
                                      <td>55891</td>
                                    </tr>
                                    <tr>
                                      <td>4</td>
                                      <td>北京</td>
                                      <td>51919</td>
                                    </tr>  
                                    <tr>
                                      <td>5</td>
                                      <td>山东</td>
                                      <td>39231</td>
                                    </tr>
                                    <tr>
                                      <td>6</td>
                                      <td>湖北</td>
                                      <td>37109</td>
                                    </tr>
                                  </tbody> -->
                                </table>
                            </div>
                            <div class="layui-card-body" id='diqufenbu'>

                            </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="layui-col-xs6 layui-col-md12">
                    <div class="layui-card">
                      <div class="layui-card-header">市级地域人数统计TOP20</div>
                      <div class="layui-card-body" id='shijirenshu'></div>
                    </div>
                  </div>

                  <div class="layui-col-xs6 layui-col-md12">
                    
                    <div class="layui-card">
                        <div class="layui-card-header">市级地域人数统计明细</div>

                        <div class="layui-card-body" >
                            <table class="layui-table layuiadmin-page-table" lay-skin="line" id="city_user_list">     
                            </table>
                        </div>

                    </div>
                  </div>
  
        </div>
    </div>
 <script type="text/javascript">

    layui.use(['form', 'layer', 'laydate','table','laypage'], function () {
        var $ = layui.$
        // 总览
        var myChart1 = echarts.init(document.getElementById('zonglan'));
        $.ajax({
            url: '/user/CallBack/GetZongLanData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据
                var option = {
                    title: {
                        
                        text: '总人数',
                        subtext: res.data.data1.total+'人',
                        left: 'left'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },
                    color : ['#f0cf5e','#b0e77f','#ec968b',],

                    legend: {
                        left:100,
                        data: ['失联人数','可联人数','未拨打人数']
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
                            data: res.data.data1.detail,
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

                // 失联人数分类统计
                 var myChart2 = echarts.init(document.getElementById('shilianfenlei'));

                 var option = {
                    
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },
                    color : ['#8ea5f1','#ec968b','#b0e77f', '#e886e7', '#84d3e7','#e5ba7a','#f0cf5e','#bbbbbb'],

                    legend: {
                        data: ['空号','停机','关机','无法接通','占线','挂断','无人接听','暂停服务']
                    },
                    series: [
                        {
                            name: '失联人数分类',
                            type: 'pie',
                            radius: '55%',
                            center: ['50%', '60%'],
                            label: {
                                formatter: '{b}' 
                            },
                            data: res.data.data2.detail,
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
        
    

        // sex
        var myChart7 = echarts.init(document.getElementById('sex'));
        $.ajax({
            url: '/user/CallBack/GetSexData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据
                var option = {
                    title: {
                        text: '总人数',
                        subtext: res.data.total+'人',
                        left: 'left'
                    },
                    tooltip: {
                        trigger: 'item',
                        formatter: '{a} <br/>{b} : {c} ({d}%)'
                    },
                    color : ['#e886e7','#8ea5f1'],

                    legend: {
                        data: ['男性', '女性']
                    },
                    series: [
                        {
                            name: '性别',
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
                myChart7.setOption(option);
            }
        });

        // 年龄分布
        var myChart3 = echarts.init(document.getElementById('age'));
        $.ajax({
            url: '/user/CallBack/GetAgeData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据
                var option = {
                    color: ['#8ea5f1'],

                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                            type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                        }
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: [
                        {
                            type: 'category',
                            data: res.data.name_list,
                            axisTick: {
                                alignWithLabel: true
                            }
                        }
                    ],
                    yAxis: [
                        {
                            type: 'value'
                        }
                    ],
                    series: [
                        {
                            name: '人数',
                            type: 'bar',
                            barWidth: '60%',
                            label: {
                                show: true,
                                position: 'top',
                                formatter: ' {@2012}人' 

                            },
                            data: res.data.value_list
                        }
                    ]
                };

                // 使用刚指定的配置项和数据显示图表。
                myChart3.setOption(option);
            }
        });

        // 借款金额
        var myChart4 = echarts.init(document.getElementById('jiekuanjine'));
        $.ajax({
            url: '/user/CallBack/GetJieKuanJineData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res); '#f0cf5e','#b0e77f','#ec968b'
                
                //  指定图表的配置项和数据
                var option = {
                    color:'#ec968b',
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                            type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                        }
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: [
                        {
                            type: 'category',
                            data: res.data.name_list,
                            axisTick: {
                                alignWithLabel: true
                            }
                        }
                    ],
                    yAxis: [
                        {
                            type: 'value'
                        }
                    ],
                    series: [
                        {
                            name: '人数',
                            type: 'bar',
                            barWidth: '60%',
                            label: {
                                show: true,
                                position: 'top',
                                formatter: ' {@2012}人' 

                            },
                            data: res.data.value_list
                        }
                    ]
                };

                // 使用刚指定的配置项和数据显示图表。
                myChart4.setOption(option);
            }
        });

        // 问题类型分类统计
        var myChart5 = echarts.init(document.getElementById('question'));
        $.ajax({
            url: '/user/CallBack/GetQuestionData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据 
              
                option = {
                    color:'#e5ba7a',

                    title: {
                        text: '',
                        subtext:'总人数：'+res.data.total+ '人  未归类：'+res.data.weiguilei+'人'
                    },
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'shadow'
                        }
                    },
                    legend: {
                        data: []
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'value',
                        boundaryGap: [0, 0.01]
                    },
                    yAxis: {
                        type: 'category',
                        data: res.data.name_list
                    },
                    series: [
                        {
                            name: '人数',
                            type: 'bar',
                            label: {
                                position: 'right',
                                show: true,
                                formatter: ' {@2012}人' 
                            },
                            data: res.data.value_list
                        }
                    ]
                };



                // 使用刚指定的配置项和数据显示图表。
                myChart5.setOption(option);
            }
        });

        var laydate = layui.laydate;
        var form = layui.form;
        var table = layui.table;
        var laypage = layui.laypage;
        var join = [
            {field: 'order', title: '排名',  width: 95,sort: true},
            {field: 'name', title: '地区', width: 140},
            {field: 'value', title: '人数', width: 140}

            // {field: 'code', title: '专区代码', width: 200},
            // {field: 'status_cn', title: '状态', width: 200},
            // {title: '操作', toolbar: '#operate', fixed: 'right'},

        ];

        table.render({
            elem: '#list',
            // toolbar: '#toolbar',
            defaultToolbar: [],
            totalRow:false,
            page: {layout:['prev', 'page', 'next','count']},
            jump:false,
            limit: 6,
            limits: [6,10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            cols: [join],
            url: '/user/CallBack/GetQuanGuoFenBuData?from=list',
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
        // 省级地区分布
        var myChart6 = echarts.init(document.getElementById('diqufenbu'));
        $.ajax({
            url: '/user/CallBack/GetQuanGuoFenBuData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据
                var dataList=res.data
        
                    function randomValue() {
                        return Math.round(Math.random()*1000);
                    }
                    option = {
                        title: {
                            text: '省级地域人数统计',
                        },
                        tooltip: {
                                formatter:function(params,ticket, callback){
                                    return params.seriesName+'<br />'+params.name+'：'+params.value
                                }//数据格式化
                            },
                        visualMap: {
                            min: 0,
                            max: 1500,
                            left: 'left',
                            top: 'bottom',
                            text: ['高','低'],//取值范围的文字
                            inRange: {
                                color: ['#e0ffff', '#006edd']//取值范围的颜色
                            },
                            show:true//图注
                        },
                        geo: {
                            map: 'china',
                            roam: false,//不开启缩放和平移
                            zoom:1.23,//视角缩放比例
                            label: {
                                normal: {
                                    show: true,
                                    fontSize:'10',
                                    color: 'rgba(0,0,0,0.7)'
                                }
                            },
                            itemStyle: {
                                normal:{
                                    borderColor: 'rgba(0, 0, 0, 0.2)'
                                },
                                emphasis:{
                                    areaColor: '#F3B329',//鼠标选择区域颜色
                                    shadowOffsetX: 0,
                                    shadowOffsetY: 0,
                                    shadowBlur: 20,
                                    borderWidth: 0,
                                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                                }
                            }
                        },
                        series : [
                            {
                                name: '人数',
                                type: 'map',
                                geoIndex: 0,
                                label: {
                                    show: true,
                                    formatter: '{b}人'
                                },  
                                data:dataList
                            }
                        ]
                    };
                    myChart6.setOption(option);
                    myChart6.on('click', function (params) {
                        alert(params.name);
                    });
            }
        });

        
        // 市区分布
        var myChart8 = echarts.init(document.getElementById('shijirenshu'));
        $.ajax({
            url: '/user/CallBack/GetShiJiRenShuData',
            data: {},
            type: "GET",
            success:function(res){
                //var result = JSON.parse(res);
                
                //  指定图表的配置项和数据
                var option = {
                    color: ['#8ea5f1'],
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                            type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                        }
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: [
                        {
                            type: 'category',
                            data: res.data.name_list,
                            axisLabel: { interval: 0, rotate: 30 },

                            axisTick: {
                                alignWithLabel: true
                            }
                        }
                    ],
                    yAxis: [
                        {
                            type: 'value'
                        }
                    ],
                    series: [
                        {
                            name: '人数',
                            type: 'bar',
                            barWidth: '60%',
                            label: {
                                show: true,
                                position: 'top',
                                formatter: ' {@2012}人' 

                            },
                            data: res.data.value_list
                        }
                    ]
                };

                // 使用刚指定的配置项和数据显示图表。
                myChart8.setOption(option);
            }
        });

        var city = [
            {field: 'order', title: '排名',  width: 400,sort: true},
            {field: 'city', title: '地区', width: 400},
            {field: 'count', title: '人数', width: 400}

            // {field: 'code', title: '专区代码', width: 200},
            // {field: 'status_cn', title: '状态', width: 200},
            // {title: '操作', toolbar: '#operate', fixed: 'right'},

        ];

        table.render({
            elem: '#city_user_list',
            // toolbar: '#toolbar',
            defaultToolbar: [],
            totalRow:false,
            page: true,
            jump:false,
            limit: 8,
            limits: [8,10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            cols: [city],
            url: '/user/CallBack/GetShiJiRenShuData?from=list',
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