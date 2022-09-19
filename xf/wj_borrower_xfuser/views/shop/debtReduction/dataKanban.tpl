<!DOCTYPE html>
<html class="x-admin-sm">

    <head>
        <meta charset="UTF-8">
        <title>实时数据看版</title>
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
            table {
                width: 100%;
                margin-left: 50px;
            }
            table td, table th
            {
                width: 25%;
                margin-top: 20px;
                text-align: left;
            }

            .layui-card-body{display: flex; justify-content: center; flex-direction: column; text-align: center; height: 400px;}


        </style>
        <body>
            <div class="x-nav">
                <span class="layui-breadcrumb">
                    <a href="">商城化债管理</a>
                    <a><cite>实时数据看版</cite></a>
                </span>
                <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8" onclick="location.reload()" title="刷新">
                    <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
                </a>
            </div>
            <div class="layui-fluid">
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md4">
                        <div class="layui-row layui-col-space15">
                          
                          <div class="layui-col-md12" >
    
                            <div class="layui-card" >
                                <div class="layui-col-md12">
                                    <div class="layui-card">
                                        <div class="layui-card-header">数据总览</div>
                                        <div class="layui-card-body " style="height: 480px;padding-bottom: 10px;">
                                            <div carousel-item="">
                                                <ul class="main_data layui-row layui-col-space10 layui-this x-admin-carousel x-admin-backlog">
                                                    <li class="layui-col-md6 layui-col-xs18" >
                                                        <a href="javascript:;" class="x-admin-backlog-body" style="height: 130px;padding-top: 80px;">
                                                           
                                                            <h3 style="font-size: 20px;color: #666666;">债转人数</h3>
                                                            <p >
                                                                <cite style="font-size: 25px; color: #259ed8;font-weight:bold"><{$platform_data['debt_number_total']|number_format:0}></cite></p>
                                                        </a>
                                                    </li>
                                                    <li class="layui-col-md6 layui-col-xs18">
                                                        <a href="javascript:;" class="x-admin-backlog-body" style="height: 130px;padding-top: 80px;">
                                                            <h3 style="font-size: 20px;color: #666666;">债转金额</h3>
                                                            <p>
                                                                <cite style="font-size: 25px; color: #259ed8;font-weight:bold"><{$platform_data['debt_total']|number_format:0}></cite>
                                                            </p>
                                                        </a>
                                                    </li>
                                                    <li class="layui-col-md6 layui-col-xs18">
                                                        <a href="javascript:;" class="x-admin-backlog-body" style="height: 130px;padding-top: 80px;">
                                                            <h3 style="font-size: 20px;color: #666666;">积分消耗</h3>
                                                            <p>
                                                                <cite style="font-size: 25px; color: #259ed8;font-weight:bold"><{$platform_data['order_total']|number_format:0}></cite></p>
                                                        </a>
                                                    </li>
                                                    <li class="layui-col-md6 layui-col-xs18">
                                                        <a href="javascript:;" class="x-admin-backlog-body" style="height: 130px;padding-top: 80px;">
                                                            <h3 style="font-size: 20px;color: #666666;">积分剩余</h3>
                                                            <p>
                                                                <cite style="font-size: 25px; color: #259ed8;font-weight:bold"><{$platform_data['residual_integral']|number_format:0}></cite></p>
                                                        </a>
                                                    </li>
                                                    
                                                </ul>
                                            </div>
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                           
                          </div>
                         
                        </div>
                    </div>
                    <div class="layui-col-md8">
                        <div class="layui-row layui-col-space15">
                            <{foreach $shop_data as $k => $v}>
                            <div class="layui-col-md6" >
                                <div class="layui-card" >
                                    <div class="layui-col-md12">
                                        <div class="layui-card">
                                            <div class="layui-card-header" style="color: #333333;"><{$v['shop_name']}></div>
                                            <div class="layui-card-body " style="height: 200px;">
                                                <div carousel-item="">
                                                    <ul class="layui-row layui-col-space10 layui-this x-admin-carousel x-admin-backlog">
                                                        <li class="layui-col-md6 layui-col-xs18">
                                                            <a href="javascript:;" class="x-admin-backlog-body">
                                                                <h3 style="color: #666666;">债转人数</h3>
                                                                <p>
                                                                    <cite style="color: #666666;font-weight:bold"><{$v['debt_number_total']|number_format:0}></cite></p>
                                                            </a>
                                                        </li>
                                                        <li class="layui-col-md6 layui-col-xs18">
                                                            <a href="javascript:;" class="x-admin-backlog-body">
                                                                <h3 style="color: #666666;">债转金额</h3>
                                                                <p>
                                                                    <cite style="color: #666666;font-weight:bold"><{$v['debt_total']|number_format:0}></cite>
                                                                </p>
                                                            </a>
                                                        </li>
                                                        <li class="layui-col-md6 layui-col-xs18">
                                                            <a href="javascript:;" class="x-admin-backlog-body">
                                                                <h3 style="color: #666666;">积分消耗</h3>
                                                                <p>
                                                                    <cite style="color: #666666;font-weight:bold"><{$v['order_total']|number_format:0}></cite></p>
                                                            </a>
                                                        </li>
                                                        <li class="layui-col-md6 layui-col-xs18">
                                                            <a href="javascript:;" class="x-admin-backlog-body">
                                                                <h3 style="color: #666666;">积分剩余</h3>
                                                                <p>
                                                                    <cite style="color: #666666;font-weight:bold"><{$v['residual_integral']|number_format:0}></cite></p>
                                                            </a>
                                                        </li>
                                                        
                                                    </ul>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                               
                              </div>
                            <{/foreach}>
                        </div>
                    </div>
                   
    
                
                </div>
                
    </div>
</body>

<style type="text/css">

</style>

</html>