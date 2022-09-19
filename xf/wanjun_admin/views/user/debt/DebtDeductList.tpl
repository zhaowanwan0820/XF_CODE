<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>债权扣除记录</title>
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
                <a href="">化债管理</a>
                <a>
                    <cite>债权扣除记录</cite></a>
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
                                          <label class="layui-form-label">所属平台</label>
                                          <div class="layui-input-inline">
                                            <select name="deal_type" lay-search="">
                                              <option value="1" <{if $deal_type eq '1'}> selected <{/if}>>尊享</option>
                                              <option value="2" <{if $deal_type eq '2'}> selected <{/if}>>普惠</option>
                                              <option value="3" <{if $deal_type eq '3'}> selected <{/if}>>工场微金</option>
                                              <option value="4" <{if $deal_type eq '4'}> selected <{/if}>>智多新</option>
                                              <option value="5" <{if $deal_type eq '5'}> selected <{/if}>>交易所</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1" <{if $_GET['status'] eq '1'}> selected <{/if}>>待处理</option>
                                              <option value="2" <{if $_GET['status'] eq '2'}> selected <{/if}>>已启动</option>
                                              <option value="3" <{if $_GET['status'] eq '3'}> selected <{/if}>>交易完成</option>
                                              <option value="4" <{if $_GET['status'] eq '4'}> selected <{/if}>>交易失败</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">用户ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="user_id" placeholder="请输入用户ID" autocomplete="off" class="layui-input" value="<{$_GET['user_id']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">用户手机号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" placeholder="请输入用户手机号" autocomplete="off" class="layui-input" value="<{$_GET['mobile']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">投资记录ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_load_id" placeholder="请输入投资记录ID" autocomplete="off" class="layui-input" value="<{$_GET['deal_load_id']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款标题</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_name" placeholder="请输入借款标题" autocomplete="off" class="layui-input" value="<{$_GET['deal_name']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">借款ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_id" placeholder="请输入借款ID" autocomplete="off" class="layui-input" value="<{$_GET['deal_id']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">回购用户ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="buyback_user_id" placeholder="请输入回购用户ID" autocomplete="off" class="layui-input" value="<{$_GET['buyback_user_id']}>">
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
                            <button class="layui-btn" onclick="xadmin.open('添加','/user/Debt/AddDebtDeduct')">
                                <i class="layui-icon"></i>添加</button>
                        </div>
                        <div class="layui-card-body ">
                            <table class="layui-table layui-form">
                                <thead>
                                    <tr>
                                        <th>记录ID</th>
                                        <th>用户ID</th>
                                        <th>用户手机号</th>
                                        <th>投资记录ID</th>
                                        <th>借款标题</th>
                                        <th>借款ID</th>
                                        <th>回购用户ID</th>
                                        <th>债权划扣金额</th>
                                        <th>操作人用户ID</th>
                                        <th>录入时间</th>
                                        <th>操作IP</th>
                                        <th>启动时间</th>
                                        <th>状态</th>
                                        <th>债权交易完成时间</th>
                                        <th>凭证</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  <{foreach $listInfo as $k => $v}>
                                    <tr>
                                        <td><{$v['id']}></td>
                                        <td><{$v['user_id']}></td>
                                        <td><{$v['mobile']}></td>
                                        <td><{$v['tender_id']}></td>
                                        <td><{$v['deal_name']}></td>
                                        <td><{$v['deal_id']}></td>
                                        <td><{$v['buyback_user_id']}></td>
                                        <td><{$v['debt_account']}></td>
                                        <td><{$v['op_user_id']}></td>
                                        <td><{$v['addtime']}></td>
                                        <td><{$v['addip']}></td>
                                        <td><{$v['start_time']}></td>
                                        <td><{$status[$v['status']]}></td>
                                        <td><{$v['successtime']}></td>
                                        <td>
                                          <a href="/<{$v['agreement_pic']}>" download><button class="layui-btn layui-btn-primary">下载</button></a>
                                        </td>
                                        <td class="td-manage">
                                          <{if $v['status'] eq '0' }>
                                          <a title="编辑" onclick="xadmin.open('编辑','/user/Debt/EditDebtDeduct?id=<{$v['id']}>')" href="javascript:;">
                                            <button class="layui-btn layui-btn-normal">编辑</button>
                                          </a>
                                          <a title="启动" onclick="start(<{$v['id']}>)" href="javascript:;">
                                            <button class="layui-btn">启动</button>
                                          </a>
                                          <{else}>
                                          <a title="编辑" href="javascript:;">
                                            <button class="layui-btn layui-btn-disabled">编辑</button>
                                          </a>
                                          <a title="启动" href="javascript:;">
                                            <button class="layui-btn layui-btn-disabled">启动</button>
                                          </a>
                                          <{/if}>
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
      layui.use(['laydate', 'form']);

      function start(id) {
        layer.confirm('确认要启动吗？',
        function(index) {
          $.ajax({
            url:'/user/Debt/StartDebtDeduct',
            type:'post',
            data:{
              'id':id
            },
            dataType:'json',
            success:function(res) {
              if (res['code'] === 0) {
                layer.msg(res['info'] , {time:1000,icon:1} , function(){
                  parent.location.reload();
                  var index = parent.layer.getFrameIndex(window.name);
                  parent.layer.close(index);
                });
              } else {
                layer.alert(res['info']);
              }
            }
          });
        });
      }
    </script>
</html>