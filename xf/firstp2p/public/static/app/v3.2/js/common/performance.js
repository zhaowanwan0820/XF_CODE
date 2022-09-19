
    (function($) {
        $(function() {
            (function() {


                //转换时间

                function formatDate(date, format) {
                    var paddNum = function(num) {
                        num += "";
                        return num.replace(/^(\d)$/, "0$1");
                    }
                    //指定格式字符
                    var cfg = {
                        yyyy: date.getFullYear() //年 : 4位
                        ,
                        yy: date.getFullYear().toString().substring(2)//年 : 2位
                        ,
                        M: date.getMonth() + 1  //月 : 如果1位的时候不补0
                        ,
                        MM: paddNum(date.getMonth() + 1) //月 : 如果1位的时候补0
                        ,
                        d: date.getDate()   //日 : 如果1位的时候不补0
                        ,
                        dd: paddNum(date.getDate())//日 : 如果1位的时候补0
                        ,
                        hh: date.getHours()  //时
                        ,
                        mm: date.getMinutes() //分
                        ,
                        ss: date.getSeconds() //秒
                    }
                    format || (format = "yyyy-MM-dd hh:mm:ss");
                    return format.replace(/([a-z])(\1)*/ig, function(m) { return cfg[m]; });
                }

                //切换选项卡
                var $navli = $("#menu li");
                $navli.bind("click", function() {
                    $(this).addClass("select").siblings().removeClass("select");
                    var index = $navli.index(this);
                    $(".invf_txt>div").eq(index).show()
                        .siblings().hide();
                });

                //图表配置
                Highcharts.theme = {
                    chart: {
                        style: {
                            fontFamily: "Dosis, sans-serif"
                        }
                    },
                    title: {
                        style: {
                            fontSize: '16px',
                            fontWeight: 'bold',
                            textTransform: 'uppercase'
                        }
                    },
                    tooltip: {
                        borderWidth: 0,
                        backgroundColor: '#f94272',
                        shadow: false,
                        style: {
                            color: '#fff',
                            fontWeight: 'bold'
                        },
                        borderRadius: 5
                    },
                    xAxis: {
                        gridLineColor: '#d1d1d1',
                        gridLineDashStyle: 'Dot',
                        gridLineWidth: 1,
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        }
                    },
                    yAxis: {
                        gridLineColor: '#d1d1d1',
                        gridLineDashStyle: 'Dot',
                        gridLineWidth: 1,
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        }
                    },
                    plotOptions: {
                        candlestick: {
                            lineColor: '#d1d1d1'
                        },
                        area: {
                            fillOpacity: 0.2,
                            marker: {
                                fillColor: '#ee4634',
                                lineColor: '#ee4634',
                                lineWidth: '4',
                                states: {
                                    hover: {
                                        enabled: true,
                                        fillColor: '#ee4634',
                                        lineColor: '#ee4634',
                                        lineWidth: '4',
                                    },
                                    select: {
                                        enabled: true,
                                        fillColor: '#ee4634',
                                        lineColor: '#ee4634',
                                        lineWidth: '4',
                                    }
                                }
                            },                            
                        }
                    }
                };

                Highcharts.Chart.prototype.callbacks.push(function(chart) {
                    var hasTouch = hasTouch = document.documentElement.ontouchstart !== undefined,
                        mouseTracker = chart.tracker,
                        container = chart.container,
                        mouseMove;

                    mouseMove = function(e) {
                        // let the system handle multitouch operations like two finger scroll
                        // and pinching
                        if (e && e.touches && e.touches.length > 1) {
                            return;
                        }

                        // normalize
                        e = mouseTracker.normalizeMouseEvent(e);
                        if (!hasTouch) { // not for touch devices
                            e.returnValue = false;
                        }

                        var chartX = e.chartX,
                            chartY = e.chartY,
                            isOutsidePlot = !chart.isInsidePlot(chartX - chart.plotLeft, chartY - chart.plotTop);

                        // cancel on mouse outside
                        if (isOutsidePlot) {

                            /*if (!lastWasOutsidePlot) {
            // reset the tracker
            resetTracker();
            }*/

                            // drop the selection if any and reset mouseIsDown and hasDragged
                            //drop();
                            if (chartX < chart.plotLeft) {
                                chartX = chart.plotLeft;
                            } else if (chartX > chart.plotLeft + chart.plotWidth) {
                                chartX = chart.plotLeft + chart.plotWidth;
                            }

                            if (chartY < chart.plotTop) {
                                chartY = chart.plotTop;
                            } else if (chartY > chart.plotTop + chart.plotHeight) {
                                chartY = chart.plotTop + chart.plotHeight;
                            }
                        }

                        if (chart.mouseIsDown && e.type !== 'touchstart') { // make selection

                            // determine if the mouse has moved more than 10px
                            hasDragged = Math.sqrt(
                                Math.pow(mouseTracker.mouseDownX - chartX, 2) +
                                    Math.pow(mouseTracker.mouseDownY - chartY, 2)
                            );
                            if (hasDragged > 10) {
                                var clickedInside = chart.isInsidePlot(mouseTracker.mouseDownX - chart.plotLeft, mouseTracker.mouseDownY - chart.plotTop);

                                // make a selection
                                if (chart.hasCartesianSeries && (mouseTracker.zoomX || mouseTracker.zoomY) && clickedInside) {
                                    if (!mouseTracker.selectionMarker) {
                                        mouseTracker.selectionMarker = chart.renderer.rect(
                                            chart.plotLeft,
                                            chart.plotTop,
                                            zoomHor ? 1 : chart.plotWidth,
                                            zoomVert ? 1 : chart.plotHeight,
                                            0
                                        )
                                            .attr({
                                                fill: mouseTracker.options.chart.selectionMarkerFill || 'rgba(69,114,167,0.25)',
                                                zIndex: 7
                                            })
                                            .add();
                                    }
                                }

                                // adjust the width of the selection marker
                                if (mouseTracker.selectionMarker && zoomHor) {
                                    var xSize = chartX - mouseTracker.mouseDownX;
                                    mouseTracker.selectionMarker.attr({
                                        width: mathAbs(xSize),
                                        x: (xSize > 0 ? 0 : xSize) + mouseTracker.mouseDownX
                                    });
                                }
                                // adjust the height of the selection marker
                                if (mouseTracker.selectionMarker && zoomVert) {
                                    var ySize = chartY - mouseTracker.mouseDownY;
                                    mouseTracker.selectionMarker.attr({
                                        height: mathAbs(ySize),
                                        y: (ySize > 0 ? 0 : ySize) + mouseTracker.mouseDownY
                                    });
                                }

                                // panning
                                if (clickedInside && !mouseTracker.selectionMarker && mouseTracker.options.chart.panning) {
                                    chart.pan(chartX);
                                }
                            }

                        } else if (!isOutsidePlot) {
                            // show the tooltip
                            mouseTracker.onmousemove(e);
                        }

                        lastWasOutsidePlot = isOutsidePlot;
                    }

                    container.onmousemove = container.ontouchstart = container.ontouchmove = mouseMove;
                });

                Highcharts.setOptions(Highcharts.theme);


                $('.container').highcharts({
                    chart: {
                        plotShadow: true,
                        type: 'area'
                    },
                    title: {
                        text: '',
                    },
                    subtitle: {
                        text: '',
                    },
                    xAxis: {
                        labels: {
                            enabled: false
                        },
                        type: 'datetime',
                        tickmarkPlacement: 'on'
                    },
                    yAxis: {
//                        tickInterval: 2,
//                        min: -0.5,
                        title: {
                            text: ''
                        }
                    },
                    plotOptions: {
                        series: {
                            animation: false,
                            marker: {
                                enabled: false,
                                symbol: 'circle',
                                radius: 0,
                                states: {
                                    hover: {
                                        enabled: true
                                    },
                                    select: {
                                        enabled: true
                                    }
                                }
                            },
                            states: {
                                hover: {
                                    enabled: true,//鼠标放上去线的状态控制  
                                    lineWidth: 2,
                                    halo: {
                                        size: 0
                                    }
                                }
                            },
                            point: {
                                events: {
                                    mouseOver: function() {
                                        $(".char-maney").html(this.y + '元');
                                        $(".char-time").html(formatDate((new Date(this.category)), "yyyy-MM-dd"));
                                    }
                                }
                            }
                        }
                   },
                    tooltip: {
                        animation: false,
                        crosshairs: [{
                            width: 2,
                            color: '#ee4634',
                            dashStyle: 'ShortDashDotDot'
                        }],
                        followTouchMove: true,
                        formatter: function() {
                            return this.y + '元';
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    series: [{
                        name: '金额',
                        color: '#ee4634',
                    }],
                    credits: {
                        enabled: false // 禁用版权信息
                    }
                });

                var chart = $('.container').highcharts();

                //获取数据
                var url = "/fund/recentDaysDailyProfit?fundCode=3096&recentDays=7",
                    newdata;
                newdata = [[1414598400000, 1.12], [1414684800000, 1.19], [1414857600000, 2.28], [1414944000000, 1.25], [1415030400000, 1.22]];
                //初始时间
                var datanow = new Date(),
                    $hcharbegin = $(".hchar1_begin");
                $(".hchar1_end").html(formatDate((new Date(datanow)), "yyyy-MM-dd"));
                $hcharbegin.html(formatDate((new Date(datanow.setDate(datanow.getDate() - 7))), "yyyy-MM-dd"));
                //切换按钮
                var $btnli = $(".highchar1 li");
                $btnli.bind("click", function() {
                    datanow = new Date();
                    $(this).addClass("sel").siblings().removeClass("sel");
                    var index = $btnli.index(this);
                    switch (index) {
                    case 0:
                        $hcharbegin.html(formatDate((new Date(datanow.setDate(datanow.getDate() - 7))), "yyyy-MM-dd"));
                        url = "/fund/recentDaysDailyProfit?fundCode=3096&recentDays=7";
                        newdata = [[1414598400000, 1.12], [1414684800000, 1.19], [1414857600000, 2.28], [1414944000000, 1.25], [1415030400000, 1.22]];
               
                        break;
                    case 1:
                        $hcharbegin.html(formatDate((new Date(datanow.setDate(datanow.getDate() - 30))), "yyyy-MM-dd"));
                        url = "/fund/recentDaysDailyProfit?fundCode=3096&recentDays=30";
                        newdata = [[1412611200000, 7.94], [1412697600000, 1.63], [1412784000000, 1.35], [1412870400000, 1.29], [1413043200000, 2.27], [1413129600000, 1.42], [1413216000000, 1.4], [1413302400000, 1.15], [1413388800000, 1.29], [1413475200000, 1.15], [1413648000000, 2.3], [1413734400000, 1.19], [1413820800000, 1.36], [1413907200000, 1.16], [1413993600000, 1.16], [1414080000000, 1.26], [1414252800000, 2.31], [1414339200000, 1.47], [1414425600000, 1.26], [1414512000000, 1.24], [1414598400000, 1.12], [1414684800000, 1.19], [1414857600000, 2.28], [1414944000000, 1.25], [1415030400000, 1.22]];
                       break;
                    case 2:
                        $hcharbegin.html(formatDate((new Date(datanow.setDate(datanow.getDate() - 90))), "yyyy-MM-dd"));
                        url = "/fund/recentDaysDailyProfit?fundCode=3096&recentDays=90";
                        newdata = [[1407427200000, 1.17], [1407600000000, 2.35], [1407686400000, 1.17], [1407772800000, 1.17], [1407859200000, 1.22], [1407945600000, 1.24], [1408032000000, 1.16], [1408204800000, 2.33], [1408291200000, 1.16], [1408377600000, 1.28], [1408464000000, 1.16], [1408550400000, 1.19], [1408636800000, 1.54], [1408809600000, 2.32], [1408896000000, 1.29], [1408982400000, 1.26], [1409068800000, 1.16], [1409155200000, 1.2], [1409241600000, 1.16], [1409414400000, 2.38], [1409500800000, 1.38], [1409587200000, 1.19], [1409673600000, 1.15], [1409760000000, 1.39], [1409846400000, 1.16], [1410105600000, 3.46], [1410192000000, 1.18], [1410278400000, 1.2], [1410364800000, 1.28], [1410451200000, 1.15], [1410624000000, 2.31], [1410710400000, 1.13], [1410796800000, 1.34], [1410883200000, 1.15], [1410969600000, 1.34], [1411056000000, 1.16], [1411228800000, 2.31], [1411315200000, 1.16], [1411401600000, 1.27], [1411488000000, 1.24], [1411574400000, 1.15], [1411660800000, 1.55], [1411833600000, 2.28], [1411920000000, 3.05], [1412006400000, 1.75], [1412611200000, 7.94], [1412697600000, 1.63], [1412784000000, 1.35], [1412870400000, 1.29], [1413043200000, 2.27], [1413129600000, 1.42], [1413216000000, 1.4], [1413302400000, 1.15], [1413388800000, 1.29], [1413475200000, 1.15], [1413648000000, 2.3], [1413734400000, 1.19], [1413820800000, 1.36], [1413907200000, 1.16], [1413993600000, 1.16], [1414080000000, 1.26], [1414252800000, 2.31], [1414339200000, 1.47], [1414425600000, 1.26], [1414512000000, 1.24], [1414598400000, 1.12], [1414684800000, 1.19], [1414857600000, 2.28], [1414944000000, 1.25], [1415030400000, 1.22]];
                         break;
                    default:
                        break;
                    }

//                    $.getJSON(url, function(data) {
//                        newdata = data.data;
//                        console.log(data.data);
//                    });
//                    $.ajax({
//                        url: url,
//                        type: 'POST',
//                        dataType: 'json',
//                        async:false,
//                        success: function(data) {
//                            newdata = data.data;
//                        }
//                    });
                    //初始的赋值
                    if (newdata.length > 0) {
                        $(".hchar1_maney").html(newdata[newdata.length - 1][1]+'元');
                        $(".hchar1_time").html(formatDate((new Date(newdata[newdata.length - 1][0])), "yyyy-MM-dd"));
                    }

                    chart.series[0].setData(newdata);

                });

//                    $.ajax({
//                        url: url,
//                        type: 'POST',
//                        dataType: 'json',
//                        async:false,
//                        success: function(data) {
//                            newdata = data.data;
//                        }
//                    });
                //初始的赋值
                if (newdata.length > 0) {
                    $(".hchar1_maney").html(newdata[newdata.length - 1][1]+'元');
                    $(".hchar1_time").html(formatDate((new Date(newdata[newdata.length - 1][0])), "yyyy-MM-dd"));
                }

                chart.series[0].setData(newdata);

                
                $('.container2').highcharts({
                    chart: {
                        plotShadow: true,
                        type: 'area'
                    },
                    title: {
                        text: '',
                    },
                    subtitle: {
                        text: '',
                    },
                    xAxis: {
                        labels: {
                            enabled: false
                        },
                        type: 'datetime',
                        tickmarkPlacement: 'on'
                    },
                    yAxis: {
//                        tickInterval: 2,
//                        min: -0.5,
                        title: {
                            text: ''
                        }
                    },
                    plotOptions: {
                        series: {
                            animation: false,
                            marker: {
                                enabled: false,
                                symbol: 'circle',
                                radius: 0,
                                states: {
                                    hover: {
                                        enabled: true
                                    },
                                    select: {
                                        enabled: true
                                    }
                                }
                            },
                            states: {
                                hover: {
                                    enabled: true,//鼠标放上去线的状态控制  
                                    lineWidth: 2,
                                    halo: {
                                        size: 0
                                    }
                                }
                            },
                            point: {
                                events: {
                                    mouseOver: function() {
                                        $(".hchar2_maney").html(this.y + '%');
                                        $(".hchar2_time").html(formatDate((new Date(this.category)), "yyyy-MM-dd"));
                                    },
                                    click: function() {
                                        $(".hchar2_maney").html(this.y + '%');
                                        $(".hchar2_time").html(formatDate((new Date(this.category)), "yyyy-MM-dd"));
                                    }
                                }
                            }
                        }
                    },
                    tooltip: {
                        animation: false,
                        crosshairs: [{
                            width: 2,
                            color: '#ee4634',
                            dashStyle: 'ShortDashDotDot'
                        }],
                        followTouchMove: true,
                        formatter: function() {
                            return this.y + '%';
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    series: [{
                        name: '年化利率',
                        color: '#ee4634',
                    }],
                    credits: {
                        enabled: false // 禁用版权信息
                    }
                });
                
                var chart2 = $('.container2').highcharts();

                //获取数据
                var url2 = "/fund/recentDaysLatestWeeklyYield?fundCode=3096&recentDays=7",
                    newdata2;

                newdata2 = [[1414598400000, 4.62], [1414684800000, 4.58], [1414857600000, 4.56], [1414944000000, 4.45], [1415030400000, 4.43]];
                //初始时间
                var datanow2 = new Date(),
                    $hcharbegin2 = $(".hchar2_begin");
                $(".hchar2_end").html(formatDate((new Date(datanow2)), "yyyy-MM-dd"));
                $hcharbegin2.html(formatDate((new Date(datanow2.setDate(datanow2.getDate() - 7))), "yyyy-MM-dd"));
                //切换按钮
                var $btnli2 = $(".highchar2 li");
                $btnli2.bind("click", function() {
                    datanow2 = new Date();
                    $(this).addClass("sel").siblings().removeClass("sel");
                    var index = $btnli2.index(this);
                    switch (index) {
                    case 0:
                        $hcharbegin2.html(formatDate((new Date(datanow2.setDate(datanow2.getDate() - 7))), "yyyy-MM-dd"));
                        url2 = "/fund/recentDaysLatestWeeklyYield?fundCode=3096&recentDays=7";
                         newdata2 = [[1414598400000, 4.62], [1414684800000, 4.58], [1414857600000, 4.56], [1414944000000, 4.45], [1415030400000, 4.43]];
                
                       break;
                    case 1:
                        $hcharbegin2.html(formatDate((new Date(datanow2.setDate(datanow2.getDate() - 30))), "yyyy-MM-dd"));
                        url2 = "/fund/recentDaysLatestWeeklyYield?fundCode=3096&recentDays=30";
                         newdata2 = [[1412611200000,4.23],[1412697600000,4.5],[1412784000000,4.61],[1412870400000,4.7],[1413043200000,4.69],[1413129600000,4.85],[1413216000000,5],[1413302400000,4.74],[1413388800000,4.71],[1413475200000,4.63],[1413648000000,4.65],[1413734400000,4.53],[1413820800000,4.51],[1413907200000,4.51],[1413993600000,4.44],[1414080000000,4.5],[1414252800000,4.5],[1414339200000,4.66],[1414425600000,4.6],[1414512000000,4.64],[1414598400000,4.62],[1414684800000,4.58],[1414857600000,4.56],[1414944000000,4.45],[1415030400000,4.43]];
                
                         break;
                    case 2:
                        $hcharbegin2.html(formatDate((new Date(datanow2.setDate(datanow2.getDate() - 90))), "yyyy-MM-dd"));
                        url2 = "/fund/recentDaysLatestWeeklyYield?fundCode=3096&recentDays=90";
                        newdata2 =[[1407427200000,4.37],[1407600000000,4.37],[1407686400000,4.37],[1407772800000,4.37],[1407859200000,4.4],[1407945600000,4.43],[1408032000000,4.43],[1408204800000,4.42],[1408291200000,4.42],[1408377600000,4.48],[1408464000000,4.44],[1408550400000,4.41],[1408636800000,4.62],[1408809600000,4.62],[1408896000000,4.68],[1408982400000,4.67],[1409068800000,4.67],[1409155200000,4.68],[1409241600000,4.47],[1409414400000,4.5],[1409500800000,4.55],[1409587200000,4.52],[1409673600000,4.51],[1409760000000,4.61],[1409846400000,4.61],[1410105600000,4.45],[1410192000000,4.44],[1410278400000,4.47],[1410364800000,4.41],[1410451200000,4.41],[1410624000000,4.41],[1410710400000,4.4],[1410796800000,4.49],[1410883200000,4.46],[1410969600000,4.49],[1411056000000,4.49],[1411228800000,4.49],[1411315200000,4.5],[1411401600000,4.47],[1411488000000,4.52],[1411574400000,4.41],[1411660800000,4.63],[1411833600000,4.61],[1411920000000,5.65],[1412006400000,5.91],[1412611200000,4.23],[1412697600000,4.5],[1412784000000,4.61],[1412870400000,4.7],[1413043200000,4.69],[1413129600000,4.85],[1413216000000,5],[1413302400000,4.74],[1413388800000,4.71],[1413475200000,4.63],[1413648000000,4.65],[1413734400000,4.53],[1413820800000,4.51],[1413907200000,4.51],[1413993600000,4.44],[1414080000000,4.5],[1414252800000,4.5],[1414339200000,4.66],[1414425600000,4.6],[1414512000000,4.64],[1414598400000,4.62],[1414684800000,4.58],[1414857600000,4.56],[1414944000000,4.45],[1415030400000,4.43]] ;
                           break;
                    default:
                        break;
                    }


//                    $.ajax({
//                        url: url2,
//                        type: 'POST',
//                        dataType: 'json',
//                        async:false,
//                        success: function(data2) {
//                            newdata2 = data2.data;
//                        }
//                    });
                    //初始的赋值
                    if (newdata2.length > 0) {
                        $(".hchar2_maney").html(newdata2[newdata2.length - 1][1] + '%');
                        $(".hchar2_time").html(formatDate((new Date(newdata2[newdata2.length - 1][0])), "yyyy-MM-dd"));
                    }

                    chart2.series[0].setData(newdata2);
                });


//                    $.ajax({
//                        url: url2,
//                        type: 'POST',
//                        dataType: 'json',
//                        async:false,
//                        success: function(data2) {
//                            newdata2 = data2.data;
//                        }
//                    });
                //初始的赋值
                if (newdata2.length > 0) {
                    $(".hchar2_maney").html(newdata2[newdata2.length - 1][1]+'%');
                    $(".hchar2_time").html(formatDate((new Date(newdata2[newdata2.length - 1][0])), "yyyy-MM-dd"));
                }

                chart2.series[0].setData(newdata2);
            })();
        });

    })(jQuery);
