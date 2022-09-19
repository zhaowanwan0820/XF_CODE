<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>受让方信息记录</title>
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
                    <cite>受让方信息记录</cite></a>
            </span>
            <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right" onclick="location.reload()" title="刷新">
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
                                  <form class="layui-form" action="" id="my_form">

                                      <div class="layui-form-item">

                                        <div class="layui-inline">
                                          <label class="layui-form-label">所属平台</label>
                                          <div class="layui-input-inline">
                                            <input lay-filter="type" type="radio" name="type" value="1" title="尊享" <{if $type eq '1'}> checked <{/if}> >
                                            <input lay-filter="type" type="radio" name="type" value="2" title="普惠" <{if $type eq '2'}> checked <{/if}> >
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">受让人</label>
                                          <div class="layui-input-inline">
                                            <select name="user" id="user" lay-verify="required" lay-search="">
                                              <option value="">请输入受让人</option>
                                              <option value="ALL" <{if $user eq 'ALL' }>selected<{/if}>>全部受让人</option>
                                              <{foreach $user_arr as $k => $v}>
                                              <option value="<{$v['id']}>" <{if $user eq $v['id'] }>selected<{/if}>><{$v['name']}></option>
                                              <{/foreach}>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">承接债权途径</label>
                                          <div class="layui-input-inline">
                                            <select name="debt_src" id="debt_src">
                                              <option value="">全部</option>
                                              <option value="1" <{if $_GET['debt_src'] eq 1 }>selected<{/if}>>权益兑换</option>
                                              <option value="4" <{if $_GET['debt_src'] eq 4 }>selected<{/if}>>一键下车</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款标题</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_name" placeholder="请输入借款标题" autocomplete="off" class="layui-input" value="<{$_GET['deal_name']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">项目名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="project_name" placeholder="请输入项目名称" autocomplete="off" class="layui-input" value="<{$_GET['project_name']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">交易所备案产品号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="number" placeholder="请输入交易所备案产品号" autocomplete="off" class="layui-input" value="<{$_GET['number']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款企业</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="company" placeholder="请输入借款企业" autocomplete="off" class="layui-input" value="<{$_GET['company']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">融资经办机构</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="agency_name" placeholder="请输入融资经办机构" autocomplete="off" class="layui-input" value="<{$_GET['agency_name']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">用款方</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="ums_name" placeholder="请输入用款方" autocomplete="off" class="layui-input" value="<{$_GET['ums_name']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">查询日期</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="请选择开始日期" name="start" id="start" readonly value="<{$_GET['start']}>">
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="请选择截止日期" name="end" id="end" readonly  value="<{$_GET['end']}>">
                                          </div>
                                        </div>

                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="demo1">立即搜索</button>
                                          <button type="button" onclick="chongzhi()" class="layui-btn layui-btn-primary">重置</button>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                        </div>
                        <div class="layui-card-header">
                            <{if $daochu_status eq 1 }>
                            <button class="layui-btn" onclick="start()">导出</button>
                            <{/if}>
                        </div>
                        <div class="layui-card-body ">
                            <table class="layui-table layui-form">
                                <thead>
                                    <tr>
                                        <th colspan="9">受让债权金额合计：<{$total}></th>
                                    </tr>
                                    <tr>
                                        <th>序号</th>
                                        <th>受让债权金额</th>
                                        <th>人数</th>
                                        <th>借款标题</th>
                                        <th>项目名称</th>
                                        <th>交易所备案产品号</th>
                                        <th>借款企业</th>
                                        <th>融资经办机构</th>
                                        <th>用款方</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  <{foreach $listInfo as $k => $v}>
                                    <tr>
                                        <td><{$k+1}></td>
                                        <td><{$v['debt_account']}></td>
                                        <td><{$v['count']}></td>
                                        <td><{$v['deal_name']}></td>
                                        <td><{$v['project_name']}></td>
                                        <td><{$v['jys_record_number']}></td>
                                        <td><{$v['company_name']}></td>
                                        <td><{$v['agency_name']}></td>
                                        <td><{$v['ums_name']}></td>
                                    </tr>
                                  <{/foreach}>
                                </tbody>
                            </table>
                        </div>
                        <div class="layui-card-body ">
                            <div class="page">
                                <div class="in-ul">
                                    <{$pages}>
                                    <{if $listInfo}>
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
      layui.use(['laydate', 'form'],
        function() {
          var laydate = layui.laydate;

          //执行一个laydate实例
          laydate.render({
              elem: '#start' //指定元素
          });

          //执行一个laydate实例
          laydate.render({
              elem: '#end' //指定元素
          });

        });

      function start() {
        layer.confirm('确认要根据当前筛选条件导出吗？',
        function(index) {
          layer.close(index);
          window.open("/user/Debt/AssigneeDataExcel?user=<{$user}>&type=<{$type}>&start=<{$_GET['start']}>&end=<{$_GET['end']}>&deal_name=<{$_GET['deal_name']}>&project_name=<{$_GET['project_name']}>&company=<{$_GET['company']}>&agency_name=<{$_GET['agency_name']}>&ums_name=<{$_GET['ums_name']}>&number=<{$_GET['number']}>&debt_src=<{$_GET['debt_src']}>","_blank");
        });
      }

      function chongzhi() {
        location.href = '/user/Debt/AssigneeData';
      }
    </script>
</html>