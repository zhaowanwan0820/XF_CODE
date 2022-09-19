<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>全量用户信息查询</title>
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
                <a href="">工场微金业务数据</a>
                <a>
                    <cite>全量用户信息查询</cite></a>
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
                                          <label class="layui-form-label">用户ID</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="id" placeholder="请输入用户ID" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">会员编号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="member_id" placeholder="请输入会员编号" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">会员名称</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="user_name" placeholder="请输入会员名称" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">用户姓名</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="real_name" placeholder="请输入用户姓名" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">手机号码</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="mobile" placeholder="请输入手机号码" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">证件号码</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="idno" placeholder="请输入证件号码" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">银行卡号</label>
                                          <div class="layui-input-inline">
                                            <input type="text" name="bankcard" placeholder="请输入银行卡号" autocomplete="off" class="layui-input" value="">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">账户类型</label>
                                          <div class="layui-input-inline">
                                            <select name="user_purpose" lay-search="">
                                              <option value="">全部</option>
                                              <option value="0">借贷混合用户</option>
                                              <option value="1">投资户</option>
                                              <option value="2">融资户</option>
                                              <option value="3">咨询户</option>
                                              <option value="4">担保/代偿I户</option>
                                              <option value="5">渠道户</option>
                                              <option value="6">渠道虚拟户</option>
                                              <option value="7">资产收购户</option>
                                              <option value="8">担保/代偿II-b户</option>
                                              <option value="9">受托资产管理户</option>
                                              <option value="10">交易中心（所）</option>
                                              <option value="11">平台户</option>
                                              <option value="12">保证金户</option>
                                              <option value="13">支付户</option>
                                              <option value="14">投资券户</option>
                                              <option value="15">红包户</option>
                                              <option value="16">担保/代偿II-a户</option>
                                              <option value="17">放贷户</option>
                                              <option value="18">垫资户</option>
                                              <option value="19">管理户</option>
                                              <option value="20">商户账户</option>
                                              <option value="21">营销补贴户</option>
                                            </select>
                                          </div>
                                        </div>

                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                          <{if $user_export_auth == 1 }>
                                          <button type="button" class="layui-btn layui-btn-danger" onclick="user_list_excel()">导出</button>
                                          <{/if}>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                        </div>

                        <div class="layui-card-body">
                            <div class="layui-collapse" lay-filter="test">
                                <div class="layui-colla-item">
                                    <h2 class="layui-colla-title">批量条件上传<i class="layui-icon layui-colla-icon"></i></h2>
                                    <div class="layui-colla-content">
                                        <form class="layui-form" action="">
                                            <div class="layui-form-item">

                                              <div class="layui-inline">
                                                <label class="layui-form-label">查询类型</label>
                                                <div class="layui-input-inline">
                                                  <select name="type" lay-search="">
                                                    <option value="1">上传用户ID</option>
                                                  </select>
                                                </div>
                                              </div>

                                              <div class="layui-inline">
                                                <label class="layui-form-label">当前查询条件</label>
                                                <div class="layui-input-inline">
                                                  <input type="text" class="layui-input" id="condition_name" value="" readonly style="width: 400px">
                                                </div>
                                                <input type="hidden" name="condition_id" id="condition_id" value="">
                                              </div>
                                            </div>

                                            <div class="layui-form-item">
                                              <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit="" lay-filter="user_condition_upload">上传文件</button>
                                                <button class="layui-btn" lay-submit="" lay-filter="user_search">立即搜索</button>
                                                <button type="button" class="layui-btn layui-btn-primary" onclick="reset_condition()">重置</button>
                                                <{if $user_export_auth == 1 }>
                                                <button type="button" class="layui-btn layui-btn-danger" onclick="batch_user_list_excel()">导出</button>
                                                <{/if}>
                                              </div>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="layui-card-body">
                          <table class="layui-table layui-form" lay-filter="list" id="list">
                          </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script>
      layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;

        table.render({
          elem           : '#list',
          toolbar        : '#toolbar',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'user_id',
              title : '用户ID',
              fixed : 'left',
            },
            {
              field : 'member_id',
              title : '会员编号',
            },
            {
              field : 'user_name',
              title : '会员名称',
            },
            {
              field : 'real_name',
              title : '用户姓名',
            },
            {
              field : 'mobile',
              title : '手机号',
              templet: function (d) {
                  // 手机号脱敏规则: 1500****657
                  if (d.mobile != "" && d.mobile != undefined && d.mobile != "null") {
                      return d.mobile.substr(0, 4) + "****" + d.mobile.substr(8, 10);
                  }
                  return "";
              }
            },
            {
              field : 'create_time',
              title : '注册时间',
            },
            {
              field : 'user_purpose',
              title : '账户类型',
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
            },
          ]],
          url      : '/user/XFDebt/UserList',
          method   : 'post',
          response :
          {
            statusName : 'code',
            statusCode : 0,
            msgName    : 'info',
            countName  : 'count',
            dataName   : 'data'
          }
        });

        form.on('submit(sreach)', function(obj){
          table.reload('list', {
            page     : {
                curr          : 1
            },
            where    :
            {
              condition_id    : '',
              id              : obj.field.id.trim(),
              bankcard        : obj.field.bankcard.trim(),
              user_name       : obj.field.user_name.trim(),
              real_name       : obj.field.real_name.trim(),
              idno            : obj.field.idno.trim(),
              mobile          : obj.field.mobile.trim(),
              user_purpose    : obj.field.user_purpose,
              member_id       : obj.field.member_id.trim()
            }
          });
          return false;
        });

        /**
        * 触发上传文件功能
        */  
        form.on('submit(user_condition_upload)', function(obj){
          if (obj.field.type != 1) {
            layer.msg('请选择查询类型');
          }
          if (obj.field.type == 1) {
            xadmin.open('通过上传用户ID查询','/user/XFDebt/AddUserConditionUpload?type=1');
          }
          return false;
        });

        /**
        * 批量上传文件搜索区域, 立即搜索功能
        */  
        form.on('submit(user_search)', function(obj){
          if (obj.field.condition_id == '' || obj.field.condition_id == undefined || obj.field.condition_id == 'null') {
            layer.msg('缺少查询条件，请先上传文件!');
            return false;
          }
          table.reload('list', {
            page     : {
                curr          : 1
            },
            where    :
            {
              condition_id    : obj.field.condition_id,
              id              : '',
              bankcard        : '',
              user_name       : '',
              real_name       : '',
              idno            : '',
              mobile          : '',
              user_purpose    : '',
              member_id       : ''
            }
          });
          return false;
        });  

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
          if (layEvent === 'info')
          {
            xadmin.open('用户详情' , '/user/XFDebt/UserInfo?id='+data.user_id);
          }
        });
      });

        function show_condition(id , name) {
          $("#condition_id").val(id);
          $("#condition_name").val(name);
        }

        /**
        * 重置功能
        */
        function reset_condition()
        {
          // 清空条件id和名称的值
          $("#condition_id").val('');
          $("#condition_name").val('');
        }

        /**
        * 导出功能
        */
        function user_list_excel()
        {
          var id            = $("input[name='id']").val();
          var member_id     = $("input[name='member_id']").val();
          var user_name     = $("input[name='user_name']").val();
          var real_name     = $("input[name='real_name']").val();
          var mobile        = $("input[name='mobile']").val();
          var idno          = $("input[name='idno']").val();
          var bankcard      = $("input[name='bankcard']").val();
          var user_purpose  = $("select[name='user_purpose']").val();
          if ((id == undefined || id == '') && 
              (member_id == undefined || member_id == '') &&
              (user_name == undefined || user_name == '') &&
              (real_name == undefined || real_name == '') &&
              (mobile == undefined || mobile == '') &&
              (idno == undefined || idno == '') &&
              (bankcard == undefined || bankcard == '')) {
            layer.msg('除账户类型外, 请至少选择一个查询条件');
            return false;  
          }
          layer.confirm('确认要根据当前筛选条件进行导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/XFDebt/UserListExcel?id="+id+"&member_id="+member_id
              +"&user_name="+user_name+"&real_name="+real_name
              +"&mobile="+mobile+"&idno="+idno
              +"&bankcard="+bankcard
              +"&user_purpose="+user_purpose+"&search_type=1" , "_blank");
          });
        }

        /**
        * 指定上传条件导出功能
        */
        function batch_user_list_excel()
        {
          var condition_id = $("#condition_id").val();
          if (condition_id == '') {
            layer.msg('缺少查询条件，请先上传文件！');
          } else {
            layer.confirm('确认要根据当前上传的批量条件导出吗？',
            function(index) {
              layer.close(index);
              window.open("/user/XFDebt/UserListExcel?condition_id="+condition_id+"&search_type=2" , "_blank");
              reset_condition();
            });
          }
        }
    </script>
    <script type="text/html" id="toolbar">
    </script>
    <script type="text/html" id="operate">
      {{# if(d.info_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="用户详情" lay-event="info"><i class="layui-icon">&#xe6b2;</i>查看</button>
      {{# } }}
    </script>
</html>