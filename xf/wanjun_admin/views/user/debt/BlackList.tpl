<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>债转黑名单</title>
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
                    <cite>债转黑名单</cite></a>
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
                                            <select name="type" id="type" lay-search="">
                                              <option value="1" <{if $type eq '1'}> selected <{/if}> >尊享</option>
                                              <option value="2" <{if $type eq '2'}> selected <{/if}> >普惠</option>
                                              <option value="3" <{if $type eq '3'}> selected <{/if}> >工场微金</option>
                                              <option value="4" <{if $type eq '4'}> selected <{/if}> >智多新</option>
                                              <option value="5" <{if $type eq '5'}> selected <{/if}> >交易所</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label for="deal_id" class="layui-form-label">借款ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_id" id="deal_id" placeholder="请输入借款ID" autocomplete="off" class="layui-input" value="<{$_GET['deal_id']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label for="deal_name" class="layui-form-label">借款标题</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="deal_name" id="deal_name" placeholder="请输入借款标题" autocomplete="off" class="layui-input" value="<{$_GET['deal_name']}>">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1" <{if $_GET['status'] eq '1'}> selected <{/if}>>开启</option>
                                              <option value="2" <{if $_GET['status'] eq '2'}> selected <{/if}>>关闭</option>
                                            </select>
                                          </div>
                                        </div>
                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="demo1">立即搜索</button>
                                          <button type="button" class="layui-btn layui-btn-primary" onclick="reset_form()">重置</button>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                        </div>
                        <div class="layui-card-header">
                            <button class="layui-btn" onclick="xadmin.open('添加债转黑名单','/user/Debt/AddBlackList',800,600)">
                                <i class="layui-icon"></i>添加</button>
                        </div>
                        <div class="layui-card-body ">
                            <table class="layui-table layui-form">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>所属平台</th>
                                        <th>借款ID</th>
                                        <th>借款标题</th>
                                        <th>状态</th>
                                        <th>录入时间</th>
                                        <th>操作人ID</th>
                                        <th>操作IP</th>
                                        <th>更新时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  <{foreach $listInfo as $k => $v}>
                                    <tr>
                                        <td><{$v['id']}></td>
                                        <td><{$v['type']}></td>
                                        <td><{$v['deal_id']}></td>
                                        <td><{$v['deal_name']}></td>
                                        <td><{$status[$v['status']]}></td>
                                        <td><{$v['addtime']}></td>
                                        <td><{$v['op_user_id']}></td>
                                        <td><{$v['addip']}></td>
                                        <td><{$v['updatetime']}></td>
                                        <td class="td-manage">
                                          <{if $v['status'] eq '1' }>
                                          <a title="关闭" onclick="start(1,<{$v['id']}>)" href="javascript:;">
                                            <button class="layui-btn layui-btn-danger">关闭</button>
                                          </a>
                                          <{else if $v['status'] eq '2'}>
                                          <a title="开启" onclick="start(2,<{$v['id']}>)" href="javascript:;">
                                            <button class="layui-btn">开启</button>
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
      layui.use(['laydate', 'form'] , function(){
        form = layui.form;
      });

      function start(val , id) {
        if (val == 1) {
          var str = '确认要关闭吗？';
        } else if (val == 2) {
          var str = '确认要开启吗？';
        }
        layer.confirm(str,
        function(index) {
          $.ajax({
            url:'/user/Debt/CloseBlackList',
            type:'post',
            data:{
              'id':id
            },
            dataType:'json',
            success:function(res) {
              if (res['code'] === 0) {
                layer.msg(res['info'] , {time:1000,icon:1} , function(){
                  location.reload();
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

      function reset_form()
      {
        $("#type").val(1);
        $("#deal_id").val('');
        $("#deal_name").val('');
        $("#status").val('');
        form.render();
      }
    </script>
</html>