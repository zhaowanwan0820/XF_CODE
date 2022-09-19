<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>核心担保企业列表</title>
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
                <a href="">核心担保企业管理</a>
                <a><cite>核心担保企业列表</cite></a>
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
                                            <label for="title" class="layui-form-label">核心担保企业名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name" id="name"   autocomplete="off" class="layui-input">
                                            </div>
                                        </div>
                                          <div class="layui-inline">
                                              <label for="title" class="layui-form-label">联系电话</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="contract_mobile" id="contract_mobile"   autocomplete="off" class="layui-input">
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label class="layui-form-label">企业状态</label>
                                              <div class="layui-input-inline">
                                                  <select name="company_user_status" id="company_user_status" lay-search="">
                                                      <option value="0">全部</option>
                                                      <{foreach $company_user_status as $key=>$val }>
                                                      <option value="<{$val}>"><{$val}></option>
                                                      <{/foreach}>
                                                  </select>
                                              </div>
                                          </div>
                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $can_export == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger"  onclick="Download2Excel()" >导出</button>
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
        laydate = layui.laydate;

        laydate.render({
            elem: '#start'
            ,type: 'datetime'
        });

        laydate.render({
            elem: '#end'
            ,type: 'datetime'
        });

        table.render({
          elem           : '#list',
          toolbar        : '#toolbar',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[{
              field : 'id',
              title : '核心担保企业ID',
              width : 120
          },
            {
              field : 'name',
              title : '核心担保企业名称 '
            },
              {
                  field : 'deal_count',
                  title : '关联标的数量',
                  width : 120
              },
            {
              field : 'deal_user_count',
              title : '关联借款方数量',
              width : 120
            },
            {
              field : 'agency_amount',
              title : '总担保金额',
              width : 120
            },
            {
              field : 'agency_status',
              title : '企业状态',
              width : 150
            },
            {
              field : 'mobile',
              title : '联系电话',
              width : 120
            },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 100
            }
          ]],
          url      : '/borrower/Borrower/agencyList',
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
            where :
            {
                name  : obj.field.name,
                company_user_status  : obj.field.company_user_status,
                contract_mobile    : obj.field.contract_mobile,
            },
            page:{curr:1}
          });
          return false;
        });



        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

          if (layEvent === 'detail') {
            xadmin.open('详情' , '/borrower/Borrower/AgencyCompanyIndex?agency_id='+data.id);
          }
        });

      });


      function Download2Excel()
      {
          var name = $("#name").val();
          var company_user_status = $("#company_user_status").val();
          var contract_mobile = $("#contract_mobile").val();
          if (  contract_mobile == '' && company_user_status == '' && name == '' ) {
              layer.msg('请输入至少一个查询条件');
          } else {
              layer.confirm('确认要根据当前筛选条件导出吗？',
                  function(index) {
                      layer.close(index);
                      window.open("/borrower/borrower/AgencyList2Excel?name="+name+"&company_user_status="+company_user_status+"&contract_mobile="+contract_mobile , "_blank");
                  });
          }
      }

    </script>

    <script type="text/html" id="operate">
        <button class="layui-btn layui-btn-xs layui-btn-normal" title="详情" lay-event="detail">详情</button>
    </script>
</html>