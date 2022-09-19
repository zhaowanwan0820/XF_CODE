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
                  title : '产品名称',
                  width : 90
              },
              {
                  field : 'deal_name',
                  title : '订单编号',
                  width : 140
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
                  field : 'wait_capital',
                  title : '债权金额（在途）',
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
              },
              {
                  field : 'xf_id',
                  title : '先锋投资记录ID',
                  width : 130
              }
          ]],
          url      : '/user/Loan/GetLoanList',
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
              deal_type         : obj.field.deal_type,
                xf_status         : obj.field.xf_status,
              deal_load_id      : obj.field.deal_load_id,
                xf_deal_load_id      : obj.field.xf_deal_load_id,
              user_id           : obj.field.user_id,
                real_name:obj.field.real_name,
              deal_id           : obj.field.deal_id,
              name              : obj.field.name,
              project_id        : obj.field.project_id,
              project_name      : obj.field.project_name,
              jys_record_number : obj.field.jys_record_number,
              company           : obj.field.company,
                company_id           : obj.field.company_id,
              advisory_name     : obj.field.advisory_name,
                province_name     : obj.field.province_name,
              agency_name       : obj.field.agency_name,
              debt_type         : obj.field.debt_type,
              condition_id      : ''
            },
            page:{curr:1}
          });
          return false;
        });

        form.on('submit(sreach_a)', function(obj){

          if (obj.field.type == 0) {
            layer.msg('请选择查询类型');
          } else if (obj.field.type == 1) {
            xadmin.open('通过上传出借人ID查询','/user/Loan/addLoanListCondition?type=1');
          } else if (obj.field.type == 2) {
            xadmin.open('通过上传借款编号查询','/user/Loan/addLoanListCondition?type=2');
          } else if (obj.field.type == 3) {
            xadmin.open('通过上传借款标题查询','/user/Loan/addLoanListCondition?type=3');
          } else if (obj.field.type == 4) {
            xadmin.open('通过上传项目名称查询','/user/Loan/addLoanListCondition?type=4');
          } else if (obj.field.type == 5) {
            xadmin.open('通过上传交易所备案编号查询','/user/Loan/addLoanListCondition?type=5');
          } else if (obj.field.type == 6) {
              xadmin.open('通过上传借款方ID查询','/user/Loan/addLoanListCondition?type=6');
          }
          return false;
        });

        form.on('submit(sreach_b)', function(obj){

          if (obj.field.condition_id == '') {
            layer.msg('缺少查询条件，请先上传文件！');
          } else {
            table.reload('list', {
              where    :
              {
                deal_type         : '',
                  xf_status         : '',
                deal_load_id      : '',
                  xf_deal_load_id      : '',
                user_id           : '',
                  real_name:'',
                deal_id           : '',
                name              : '',
                project_id        : '',
                project_name      : '',
                jys_record_number : '',
                company           : '',
                  company_id           : '',
                advisory_name     : '',
                  province_name     : '',
                agency_name       : '',
                debt_type         : '',
                condition_id      : obj.field.condition_id
              },
              page:{curr:1}
            });
          }
          return false;
        });

        table.on('tool(list)' , function(obj){
          var layEvent  = obj.event;
          var data      = obj.data;
         

        });

      });



      function reset_condition() {
        $("#condition_id").val('');
        $("#condition_name").val('');
      }

      function show_condition(id , name) {
        $("#condition_id").val(id);
        $("#condition_name").val(name);
      }

      function LoanList2ExcelA()
      {
        var deal_type         = $("#deal_type").val();
          var xf_status         = $("#xf_status").val();
        var deal_load_id      = $("#deal_load_id").val();
          var xf_deal_load_id      = $("#xf_deal_load_id").val();
        var user_id           = $("#user_id").val();
        var real_name = $("#real_name").val();
        var deal_id           = $("#deal_id").val();
        var name              = $("#name").val();
        var project_id        = $("#project_id").val();
        var project_name      = $("#project_name").val();
        var jys_record_number = $("#jys_record_number").val();
        var company           = $("#company").val();
          var company_id           = $("#company_id").val();
        var advisory_name     = $("#advisory_name").val();
          var province_name     = $("#province_name").val();
        var agency_name       = $("#agency_name").val();
        var debt_type         = $("#debt_type").val();
        if (deal_type == '' && xf_deal_load_id == '' && province_name == '' && xf_status == '' && deal_load_id == '' && user_id == '' && real_name== '' && deal_id == '' && name == '' && project_id == '' && project_name == '' && jys_record_number == '' && company == '' && company_id == ''&& advisory_name == '' && agency_name == '' && debt_type == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/LoanList2Excel?real_name="+real_name+"&xf_deal_load_id="+xf_deal_load_id+"&company_id="+company_id+"&province_name="+province_name+"&deal_type="+deal_type+"&deal_load_id="+deal_load_id+"&user_id="+user_id+"&deal_id="+deal_id+"&name="+name+"&project_id="+project_id+"&project_name="+project_name+"&company="+company+"&advisory_name="+advisory_name+"&agency_name="+agency_name , "_blank");
          });
        }
      }

      function LoanList2ExcelB()
      {
        var condition_id = $("#condition_id").val();
        if (condition_id == '') {
          layer.msg('缺少查询条件，请先上传文件！');
        } else {
          layer.confirm('确认要根据当前上传的批量条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/LoanList2Excel?condition_id="+condition_id , "_blank");
            reset_condition();
          });
        }
      }
    </script>
</html>