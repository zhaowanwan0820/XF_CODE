<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>债权列表</title>
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
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">

        <div class="layui-col-md12">

            <div class="layui-card">
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
          var id = <{$_GET['id']}>;
        table.render({
          elem           : '#list',
          toolbar        : '#toolbar',
          defaultToolbar : ['filter'],
            where: {
                id: id,
            },
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'id',
              title : '投资记录ID',
              fixed : 'left',
              width : 100
            },
              {
                  field : 'platform_name',
                  title : '平台',
                  width : 70
              },
            {
              field : 'deal_id',
              title : '借款编号',
              width : 90
            },
            {
              field : 'deal_name',
              title : '借款标题',
              width : 140
            },
              {
                  field : 'project_id',
                  title : '项目ID',
                  width : 80
              },
            {
              field : 'project_name',
              title : '项目名称',
              width : 140
            },
              {
                  field : 'project_product_class',
                  title : '产品大类',
                  width : 80
              },
              {
                  field : 'displace_amount',
                  title : '置换金额',
                  width : 130,
                  align : 'right'
              } ,
              {
                  field : 'deal_user_id',
                  title : '借款方ID',
                  width : 90
              },
              {
                  field : 'deal_user_real_name',
                  title : '借款方名称',
                  width : 180
              },
              {
                  field : 'user_type_name',
                  title : '借款人类型',
                  width : 90
              },
              {
                  field : 'province_name_name',
                  title : '归属地（省）',
                  width : 110
              },
              {
                  field : 'card_address',
                  title : '详细地址',
                  width : 120
              },
            {
              field : 'deal_advisory_name',
              title : '融资经办机构名称',
              width : 200
            },
            {
              field : 'deal_agency_name',
              title : '担保机构名称',
              width : 220
            } ,
              {
                  field : 'user_id',
                  title : '出借人ID',
                  width : 100
              },
              {
                  field : 'real_name',
                  title : '出借人姓名',
                  width : 140
              }
          ]],
          url      : '/user/Loan/displaceDetail',
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
            },
            page:{curr:1}
          });
          return false;
        });
        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         

        });

      });
    </script>
</html>