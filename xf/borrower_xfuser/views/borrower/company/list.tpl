<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>第三方公司列表</title>
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
                <a href="">第三方管理</a>
                <a>
                    <cite>第三方公司列表</cite></a>
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
                                <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                                <div class="layui-colla-content layui-show">
                                  <form class="layui-form" action="">

                                      <div class="layui-form-item">

                                        <!--div class="layui-inline">
                                            <label for="title" class="layui-form-label">公司名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="name" id="name" placeholder="请输入公司名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div-->

                                          <div class="layui-inline">
                                              <label for="title" class="layui-form-label">公司名称</label>
                                              <div class="layui-input-inline" style="width: 190px">
                                                  <select name="company_id"  lay-verify="company_id" id="company_id" style="width:20px">
                                                      <option value="">全部</option>
                                                      <{foreach $company_list as $key => $v}>
                                                      <option value="<{$v['id']}>"><{$v['name']}></option>
                                                      <{/foreach}>
                                                  </select>
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label for="title" class="layui-form-label">税号</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="tax_number" id="tax_number" placeholder="请输入税号" autocomplete="off" class="layui-input">
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label for="title" class="layui-form-label">企业联系人</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="contract_name" id="contract_name" placeholder="请输入企业联系人" autocomplete="off" class="layui-input">
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label for="title" class="layui-form-label">联系电话</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="contract_mobile" id="contract_mobile" placeholder="请输入联系电话" autocomplete="off" class="layui-input">
                                              </div>
                                          </div>

                                          <div class="layui-inline">
                                              <label for="title" class="layui-form-label">联系邮箱</label>
                                              <div class="layui-input-inline">
                                                  <input type="text" name="contract_email" id="contract_email" placeholder="请输入联系邮箱" autocomplete="off" class="layui-input">
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
          cols:[[
            {
              field : 'id',
              title : 'ID',
              width : 80
            },
            {
              field : 'name',
              title : '公司名称',
              width : 300
            },
            {
              field : 'tax_number',
              title : '税号',
              width : 200
            },
            {
              field : 'business_license',
              title : '企业营业执照',
              width : 120
            },
            {
              field : 'contract_name',
              title : '企业联系人',
              width : 150
            },
            {
              field : 'contract_mobile',
              title : '联系电话',
              width : 150
            },
            {
              field : 'contract_email',
              title : '联系邮箱',
              width : 200
            },
              {
                  field : 'add_time',
                  title : '添加时间',
                  width : 150
              },
              {
                  field : 'update_time',
                  title : '最后编辑时间',
                  width : 150
              },
            {
              title   : '操作',
              toolbar : '#operate',
              fixed   : 'right',
              width   : 150
            }
          ]],
          url      : '/borrower/company/list',
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
                company_id  : obj.field.company_id,
                tax_number  : obj.field.tax_number,
                contract_name  : obj.field.contract_name,
                contract_mobile    : obj.field.contract_mobile,
                contract_email : obj.field.contract_email
            },
            page:{curr:1}
          });
          return false;
        });

        table.on('toolbar(list)' , function(obj){
          switch(obj.event){
            case 'add_notcice':
              xadmin.open('新增','/borrower/company/addCompany');
              break;
          };
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;

          if (layEvent === 'edit') {
            xadmin.open('编辑' , '/borrower/company/EditCompany?id='+data.id);
          }
        });

      });
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
          <button class="layui-btn layui-btn-xs" title="新增" lay-event="add_notcice">新增</button>
      </div > 
    </script>
    <script type="text/html" id="operate">
        <button class="layui-btn layui-btn-xs layui-btn-normal" title="编辑" lay-event="edit">编辑</button>
    </script>
</html>