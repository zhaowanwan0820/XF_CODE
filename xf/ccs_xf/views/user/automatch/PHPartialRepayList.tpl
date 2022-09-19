<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>普惠匹配债权还本列表</title>
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
                <a href="">普惠还款管理</a>
                <a><cite>普惠匹配债权还本列表</cite></a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
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
                            <h2 class="layui-colla-title">条件筛选（实时筛选）<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" action="">
                                    <div class="layui-form-item">

                                        <div class="layui-inline">
                                            <label class="layui-form-label">序号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="id" id="id" placeholder="请输入序号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">状态</label>
                                          <div class="layui-input-inline">
                                            <select name="status" id="status" lay-search="">
                                              <option value="">全部</option>
                                              <option value="1">待审核</option>
                                              <option value="2">审核已通过</option>
                                              <option value="3">审核未通过</option>
                                              <option value="4">还款成功</option>
                                              <option value="5">还款失败</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">计划还款日期</label>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="开始时间" name="start" id="start" readonly>
                                          </div>
                                          <div class="layui-input-inline">
                                            <input class="layui-input" placeholder="截止时间" name="end" id="end" readonly>
                                          </div>
                                        </div>
                                        
                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
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
    
</script>
    <script>
      layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;
        laydate = layui.laydate;

        laydate.render({
            elem: '#start'
        });

        laydate.render({
            elem: '#end'
        });
                 
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
              field : 'id',
              title : '序号',
              fixed : 'left',
              width : 100
            },
            {
              field : 'total_repayment',
              title : '还款总额',
              width : 150
            },
            {
              field : 'success_number',
              title : '导入成功',
              width : 150
            },
            {
              field : 'total_successful_amount',
              title : '成功金额合计',
              width : 150
            },
            {
              field : 'fail_number',
              title : '导入失败',
              width : 150
            },
            {
              field : 'total_fail_amount',
              title : '失败金额合计',
              width : 150
            },
            {
              field : 'admin_user',
              title : '录入方',
              width : 150
            },
            {
              field : 'pay_user',
              title : '付款方',
              width : 150
            },
            {
              field : 'pay_plan_time',
              title : '计划还款时间',
              width : 150
            },
            {
              field : 'addtime',
              title : '录入时间',
              width : 150
            },
            {
              field : 'proof_url',
              title : '还款凭证',
              width : 150
            },
            {
              field : 'status_name',
              title : '状态',
              width : 150
            },
            {
              field : 'task_success_time',
              title : '完成时间',
              width : 150
            },
            {
              field : 'remark',
              title : '备注',
              width : 200
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 310
            }
          ]],
          url      : '/user/Automatch/PHPartialRepayList',
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
            where    :
            {
              id     : obj.field.id,
              status : obj.field.status,
              start  : obj.field.start,
              end    : obj.field.end
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('toolbar(list)' , function(obj){
          switch(obj.event){
            case 'add':
              xadmin.open('普惠匹配债权还本录入','/user/Automatch/AddPHPartialRepay');
              break;
          };
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         
          if (layEvent === 'info') {
            xadmin.open('详情','/user/Automatch/PHPartialRepayDetail?id='+data.id);
          } else if (layEvent === 'edit') {
            xadmin.open('编辑','/user/Automatch/EditPHPartialRepay?id='+data.id);
          } else if (layEvent === 'pass') {
            setPartial(data.id , 2);
          } else if (layEvent === 'refuse') {
            layer.prompt({
              formType: 2,
              title: '请输入拒绝原因'
            }, function(value, index, elem){
              layer.close(index);
              RefusePartial(data.id , value);
            });
          } else if (layEvent === 'delete') {
            setPartial(data.id , 6);
          }
        });

      });

      function RefusePartial(id , value){
        $.ajax({
            url: '/user/Automatch/RefusePHPartialRepay' ,
            type:"post",
            data:{'id':id , 'remark':value},
            dataType:'json',
            success: function (res) {
                if(res.code === 0){
                    layer.msg(res.info,{time:1000,icon:1},function(){
                        location.reload();
                    });
                }else{
                    layer.alert(res.info);
                }
            }
        });
      }

      function setPartial(id , status){
        if(status == 2){
            var str = '只对导入成功的数据进行还款，确认要审核通过吗？';
            var url = "/user/Automatch/AllowedPHPartialRepay";
        }else if(status == 6){
            var str = '移除后将不再展示，确认要移除吗？';
            var url = "/user/Automatch/DeletePHPartialRepay";
        }
        layer.confirm(str,function(index){
            $.ajax({
                url: url ,
                type:'post',
                data:{'id':id},
                dataType:'json',
                success: function (res) {
                    if(res.code === 0){
                        layer.msg(res.info,{time:1000,icon:1},function(){
                            location.reload();
                        });
                    }else{
                        layer.alert(res.info);
                    }
                }
            })
        });
      }

    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
        <{if $add_status == 1 }>
        <button class="layui-btn layui-btn-xs" title="普惠匹配债权还本录入" lay-event="add">普惠匹配债权还本录入</button>
        <{/if}>
      </div > 
    </script>
    <script type="text/html" id="operate">
      <button class="layui-btn layui-btn-xs layui-btn-primary" title="详情" lay-event="info">详情</button>
      {{# if(d.edit_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-normal" title="编辑" lay-event="edit">编辑</button>
      {{# } }}
      {{# if(d.pass_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="通过" lay-event="pass">通过</button>
      {{# } }}
      {{# if(d.refuse_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-warm" title="拒绝" lay-event="refuse">拒绝</button>
      {{# } }}
      {{# if(d.delete_status == 1){ }}
      <button class="layui-btn layui-btn-xs layui-btn-danger" title="移除" lay-event="delete">移除</button>
      {{# } }}
    </script>
</html>