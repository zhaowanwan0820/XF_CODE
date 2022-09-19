<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>用款方信息</title>
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
                <a href="">首页</a>
                <a href="">受让方信息看板</a>
                <a>
                    <cite>用款方信息</cite></a>
            </span>
            <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8" onclick="location.reload()" title="刷新">
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
                                <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                                <div class="layui-colla-content layui-show">
                                  <form class="layui-form" action="">

                                      <div class="layui-form-item">
                                        <div class="layui-inline">
                                          <label for="name" class="layui-form-label">用款方</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="name" id="name" placeholder="请输入用款方" autocomplete="off" class="layui-input" value="<{$_GET['name']}>">
                                          </div>
                                        </div>
                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="demo1">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                        </div>
                        <div class="layui-card-header">
                            <button class="layui-btn" onclick="xadmin.open('添加','/user/Debt/AddUsingMoneySide',800,600)">
                                <i class="layui-icon"></i>添加用款方</button>
                        </div>
                        <div class="layui-card-body ">
                            <table class="layui-table layui-form">
                                <thead>
                                    <tr>
                                        <th>序号</th>
                                        <th>用款方</th>
                                        <th>借款企业</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  <{foreach $list as $k => $v}>
                                    <tr>
                                        <td><{$v['id']}></td>
                                        <td><{$v['name']}></td>
                                        <td class="td-manage">
                                          <a title="查看" onclick="xadmin.open('查看借款企业','/user/Debt/UsingMoneySideInfo?id=<{$v['id']}>')" href="javascript:;">
                                            <button class="layui-btn layui-btn-primary">查看</button>
                                          </a>
                                          <a title="编辑" onclick="xadmin.open('编辑借款企业','/user/Debt/EditUsingMoneySide?id=<{$v['id']}>')" href="javascript:;">
                                            <button class="layui-btn layui-btn-normal">编辑</button>
                                          </a>
                                        </td>
                                    </tr>
                                  <{/foreach}>
                                </tbody>
                            </table>
                        </div>
                        <div class="layui-card-body ">
                            <div class="page">
                                <div class="in-ul">
                                    <{$pages}>
                                    <{if $list}>
                                      <div class="layui-inline">
                                        <label class="layui-form-label">总数据量:</label>
                                        <span><{$count}></span>
                                      </div>
                                    <{/if}>
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
</html>